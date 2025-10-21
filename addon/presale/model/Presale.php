<?php
// +---------------------------------------------------------------------+
// | NiuCloud | [ WE CAN DO IT JUST NiuCloud ]                |
// +---------------------------------------------------------------------+
// | Copy right 2019-2029 www.niucloud.com                          |
// +---------------------------------------------------------------------+
// | Author | NiuCloud <niucloud@outlook.com>                       |
// +---------------------------------------------------------------------+
// | Repository | https://github.com/niucloud/framework.git          |
// +---------------------------------------------------------------------+

namespace addon\presale\model;

use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\member\MemberLevel;
use app\model\system\Cron;
use think\facade\Db;

/**
 * 预售活动
 */
class Presale extends BaseModel
{

    private $status = [
        0 => '未开始',
        1 => '活动进行中',
        2 => '活动已结束',
        3 => '已关闭'
    ];

    /**
     * 获取预售活动状态
     * @return array
     */
    public function getPresaleStatus()
    {
        return $this->success($this->status);
    }

    /**
     * 添加预售
     * @param $presale_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function addPresale($presale_data, $goods, $sku_list)
    {
        if (empty($goods['sku_ids'])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        $presale_data['create_time'] = time();

        //查询该商品是否存在预售
        $presale_info = model('promotion_presale_goods')->getInfo(
            [
                ['ppg.site_id', '=', $presale_data['site_id']],
                ['pp.status', 'in', '0,1'],
                ['ppg.goods_id', 'in', $goods['goods_ids']],
                ['', 'exp', Db::raw('not ( (`start_time` > ' . $presale_data['end_time'] . ' and `start_time` > ' . $presale_data['start_time'] . ' )  or (`end_time` < ' . $presale_data['start_time'] . ' and `end_time` < ' . $presale_data['end_time'] . '))')]
            ], 'ppg.id', 'ppg', [['promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'left']]
        );
        if (!empty($presale_info)) {
            return $this->error('', "当前商品在当前时间段内已经存在预售活动");
        }

        if (time() > $presale_data['end_time']) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($presale_data['start_time'] <= time()) {
            $presale_data['status'] = 1;
            $presale_data['status_name'] = $this->status[1];
        } else {
            $presale_data['status'] = 0;
            $presale_data['status_name'] = $this->status[0];
        }
        model("promotion_presale")->startTrans();
        try {

            foreach ($goods['goods_ids'] as $goods_id) {

                //添加预售活动
                $presale_data['goods_id'] = $goods_id;
                $presale_id = model("promotion_presale")->add($presale_data);

                $presale_stock = 0;

                $sku_list_data = [];
                foreach ($sku_list as $k => $sku) {

                    if ($sku['goods_id'] == $goods_id) {

                        $presale_stock += $sku['presale_stock'];//总库存
                        $sku_list_data[] = [
                            'site_id' => $presale_data['site_id'],
                            'presale_id' => $presale_id,
                            'goods_id' => $goods_id,
                            'sku_id' => $sku['sku_id'],
                            'presale_stock' => $sku['presale_stock'],
                            'presale_deposit' => $sku['presale_deposit'],
                            'presale_price' => $sku['presale_price'],
                        ];
                    }
                }
                array_multisort(array_column($sku_list_data, 'presale_deposit'), SORT_ASC, $sku_list_data);
                model('promotion_presale_goods')->addList($sku_list_data);

                model('promotion_presale')->update(
                    [
                        'presale_stock' => $presale_stock,
                        'presale_deposit' => $sku_list_data[0]['presale_deposit'],
                        'presale_price' => $sku_list_data[0]['presale_price'],
                        'sku_id' => $sku_list_data[0]['sku_id']
                    ],
                    [['presale_id', '=', $presale_id]]
                );

                $cron = new Cron();
                if ($presale_data['status'] == 1) {
                    $goods = new Goods();
                    $goods->modifyPromotionAddon($goods_id, ['presale' => $presale_id]);
                    $cron->addCron(1, 0, "预售活动关闭", "ClosePresale", $presale_data['end_time'], $presale_id);
                } else {
                    $cron->addCron(1, 0, "预售活动开启", "OpenPresale", $presale_data['start_time'], $presale_id);
                    $cron->addCron(1, 0, "预售活动关闭", "ClosePresale", $presale_data['end_time'], $presale_id);
                }
            }

            model('promotion_presale')->commit();
            return $this->success();

        } catch (\Exception $e) {
            model('promotion_presale')->rollback();
            return $this->error('', $e->getMessage());
        }
    }


    /**
     * 编辑预售
     * @param $presale_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function editPresale($presale_data, $goods, $sku_list)
    {
        if (empty($goods['sku_ids'])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        //查询该商品是否存在预售
        $presale_info = model('promotion_presale_goods')->getInfo(
            [
                ['ppg.site_id', '=', $presale_data['site_id']],
                ['pp.status', 'in', '0,1'],
                ['ppg.presale_id', '<>', $presale_data['presale_id']],
                ['ppg.sku_id', 'in', $goods['sku_ids']],
                ['', 'exp', Db::raw('not ( (`start_time` > ' . $presale_data['end_time'] . ' and `start_time` > ' . $presale_data['start_time'] . ' )  or (`end_time` < ' . $presale_data['start_time'] . ' and `end_time` < ' . $presale_data['end_time'] . '))')]
            ], 'ppg.id', 'ppg', [['promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'left']]
        );
        if (!empty($presale_info)) {
            return $this->error('', "当前商品在当前时间段内已经存在预售活动");
        }

        $presale_count = model("promotion_presale")->getCount([['presale_id', '=', $presale_data['presale_id']], ['site_id', '=', $presale_data['site_id']]]);
        if ($presale_count == 0) {
            return $this->error('', '该预售活动不存在');
        }

        $cron = new Cron();
        if (time() > $presale_data['end_time']) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($presale_data['start_time'] <= time()) {
            $presale_data['status'] = 1;
            $presale_data['status_name'] = $this->status[1];
        } else {
            $presale_data['status'] = 0;
            $presale_data['status_name'] = $this->status[0];
        }

        $presale_data['modify_time'] = time();
        model('promotion_presale')->startTrans();
        try {
            $presale_stock = 0;
            $sku_list_data = [];
            foreach ($sku_list as $k => $sku) {

                $count = model('promotion_presale_goods')->getCount([['sku_id', '=', $sku['sku_id']], ['presale_id', '=', $presale_data['presale_id']]]);
                $is_delete = $sku['is_delete'];
                unset($sku['is_delete']);
                if ($is_delete == 2) {//是否参与  1参与  2不参与
                    if ($count) {
                        model('promotion_presale_goods')->delete([['sku_id', '=', $sku['sku_id']], ['presale_id', '=', $presale_data['presale_id']]]);
                    }
                } else {

                    $presale_stock += $sku['presale_stock'];//总库存
                    $sku_data = [
                        'site_id' => $presale_data['site_id'],
                        'presale_id' => $presale_data['presale_id'],
                        'goods_id' => $goods['goods_id'],
                        'sku_id' => $sku['sku_id'],
                        'presale_stock' => $sku['presale_stock'],
                        'presale_deposit' => $sku['presale_deposit'],
                        'presale_price' => $sku['presale_price'],
                    ];

                    if ($count > 0) {
                        model('promotion_presale_goods')->update($sku_data, [['sku_id', '=', $sku['sku_id']], ['presale_id', '=', $presale_data['presale_id']]]);
                    } else {
                        model('promotion_presale_goods')->add($sku_data);
                    }
                    $sku_list_data[] = $sku_data;
                }
            }
            array_multisort(array_column($sku_list_data, 'presale_deposit'), SORT_ASC, $sku_list_data);
            model("promotion_presale")->update(
                array_merge($presale_data, [
                    'presale_stock' => $presale_stock,
                    'presale_deposit' => $sku_list_data[0]['presale_deposit'],
                    'presale_price' => $sku_list_data[0]['presale_price'],
                    'sku_id' => $sku_list_data[0]['sku_id']
                ]),
                [['presale_id', '=', $presale_data['presale_id']]]
            );

            if ($presale_data['start_time'] <= time()) {

                $goods_model = new Goods();
                $goods_model->modifyPromotionAddon($goods['goods_id'], ['presale' => $presale_data['presale_id']]);
                //活动商品启动
                $this->cronOpenPresale($presale_data['presale_id']);
                $cron->deleteCron([['event', '=', 'OpenPresale'], ['relate_id', '=', $presale_data['presale_id']]]);
                $cron->deleteCron([['event', '=', 'ClosePresale'], ['relate_id', '=', $presale_data['presale_id']]]);

                $cron->addCron(1, 0, "预售活动关闭", "ClosePresale", $presale_data['end_time'], $presale_data['presale_id']);
            } else {
                $cron->deleteCron([['event', '=', 'OpenPresale'], ['relate_id', '=', $presale_data['presale_id']]]);
                $cron->deleteCron([['event', '=', 'ClosePresale'], ['relate_id', '=', $presale_data['presale_id']]]);

                $cron->addCron(1, 0, "预售活动开启", "OpenPresale", $presale_data['start_time'], $presale_data['presale_id']);
                $cron->addCron(1, 0, "预售活动关闭", "ClosePresale", $presale_data['end_time'], $presale_data['presale_id']);
            }

            #todo  查看是否有已生成订单  已生成订单的支付时间会被同步改变

            $where = [ 'presale_id' => $presale_data['presale_id'] ];
            $temp = model('promotion_presale_order')->getColumn($where,'id');
            if($temp){
                model('promotion_presale_order')->update(
                    [
                        'pay_start_time'=>$presale_data['pay_start_time'],
                        'pay_end_time'=>$presale_data['pay_end_time']
                    ],[
                    ['id ','in', implode(',',$temp)]
                ]);
            }

            model('promotion_presale')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_presale')->rollback();
            return $this->error('', $e->getMessage());
        }
    }
    /**
     * 查询当前用户是否存在此预售商品订单

     * @param array $condition
     * @return array
     */
    public function getpresaleorderCount($condition = [])
    {
        $res = model('promotion_presale_order')->getCount($condition);
        return $this->success($res);
    }
    /**
     * 增加预售组人数及购买人数
     * @param array $data
     * @param array $condition
     * @return array
     */
    public function editPresaleNum($data = [], $condition = [])
    {
        $res = model('promotion_presale')->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除预售活动
     * @param $presale_id
     * @param $site_id
     * @return array|\multitype
     */
    public function deletePresale($presale_id, $site_id)
    {
        //预售信息
        $presale_info = model('promotion_presale')->getInfo([['presale_id', '=', $presale_id], ['site_id', '=', $site_id]], 'status');
        if ($presale_info) {

            if ($presale_info['status'] != 1) {
                $res = model('promotion_presale')->delete([['presale_id', '=', $presale_id], ['site_id', '=', $site_id]]);
                if ($res) {
                    //删除商品
                    model('promotion_presale_goods')->delete([['presale_id', '=', $presale_id], ['site_id', '=', $site_id]]);

                    $cron = new Cron();
                    $cron->deleteCron([['event', '=', 'OpenPresale'], ['relate_id', '=', $presale_id]]);
                    $cron->deleteCron([['event', '=', 'ClosePresale'], ['relate_id', '=', $presale_id]]);
                }
                return $this->success($res);
            } else {
                return $this->error('', '预售活动进行中,请先关闭该活动');
            }

        } else {
            return $this->error('', '预售活动不存在');
        }
    }

    /**
     * 关闭预售活动
     * @param $presale_id
     * @param $site_id
     * @return array
     */
    public function finishPresale($presale_id, $site_id)
    {
        //预售信息
        $presale_info = model('promotion_presale')->getInfo([['presale_id', '=', $presale_id], ['site_id', '=', $site_id]], 'status,goods_id');
        if (!empty($presale_info)) {

            if ($presale_info['status'] != 3) {
                $res = model('promotion_presale')->update(['status' => 3, 'status_name' => $this->status[3]], [['presale_id', '=', $presale_id], ['site_id', '=', $site_id]]);

                $cron = new Cron();
                $cron->deleteCron([['event', '=', 'OpenPresale'], ['relate_id', '=', $presale_id]]);
                $cron->deleteCron([['event', '=', 'ClosePresale'], ['relate_id', '=', $presale_id]]);

                $goods = new Goods();
                $goods->modifyPromotionAddon($presale_info['goods_id'], ['presale' => $presale_id], true);

                return $this->success($res);
            } else {
                $this->error('', '该预售活动已关闭');
            }
        } else {
            $this->error('', '该预售活动不存在');
        }
    }


    /**
     * 获取预售信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPresaleInfo($condition = [], $field = '*')
    {
        $presale_info = model("promotion_presale")->getInfo($condition, $field);
        return $this->success($presale_info);
    }

    /**
     * 获取预售详细信息
     * @param $presale_id
     * @param $site_id
     * @return array
     */
    public function getPresaleDetail($presale_id, $site_id)
    {
        //预售信息
        $alias = 'p';
        $join = [
            [
                'goods g',
                'g.goods_id = p.goods_id',
                'inner'
            ]
        ];
        $presale_info = model("promotion_presale")->getInfo(
            [
                ['p.presale_id', '=', $presale_id], ['p.site_id', '=', $site_id],
                ['g.goods_state', '=', 1], ['g.is_delete', '=', 0]
            ], 'p.*', $alias, $join
        );
        if (!empty($presale_info)) {
            //商品sku信息
            $goods_list = model('goods_sku')->getList(
                [['goods_id', '=', $presale_info['goods_id']]],
                'goods_id,sku_id,sku_name,price,sku_images,stock,sku_image'
            );
            foreach ($goods_list as $k => $v) {

                $presale_goods = model('promotion_presale_goods')->getInfo(
                    [['presale_id', '=', $presale_id], ['sku_id', '=', $v['sku_id']]],
                    'presale_stock,presale_deposit,presale_price'
                );
                if (empty($presale_goods)) {
                    $presale_goods = [
                        'presale_stock' => 0,
                        'presale_deposit' => 0,
                        'presale_price' => 0
                    ];
                }
                $goods_list[$k] = array_merge($v, $presale_goods);
            }
            array_multisort(array_column($goods_list, 'presale_price'), SORT_DESC, $goods_list);
            $presale_info['sku_list'] = $goods_list;
        }
        return $this->success($presale_info);
    }

    /**
     * 获取预售详细信息
     * @param $presale_id
     * @param $site_id
     * @return array
     */
    public function getPresaleJoinGoodsList($presale_id, $site_id)
    {
        //预售信息
        $alias = 'p';
        $join = [
            ['goods g', 'g.goods_id = p.goods_id', 'inner']
        ];
        $presale_info = model("promotion_presale")->getInfo(
            [
                ['p.presale_id', '=', $presale_id], ['p.site_id', '=', $site_id],
                ['g.goods_state', '=', 1], ['g.is_delete', '=', 0]
            ], 'p.*', $alias, $join
        );
        if (!empty($presale_info)) {

            $goods_list = model('promotion_presale_goods')->getList(
                [['ppg.presale_id', '=', $presale_info['presale_id']]],
                'ppg.presale_stock,ppg.presale_deposit,ppg.presale_price,sku.sku_id,sku.sku_name,sku.price,sku.sku_image,sku.stock',
                '', 'ppg', [['goods_sku sku', 'sku.sku_id = ppg.sku_id', 'inner']]
            );

            $presale_info['sku_list'] = $goods_list;
        }
        return $this->success($presale_info);
    }

    /**
     * 预售商品详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPresaleGoodsDetail($condition = [], $field = 'ppg.id,ppg.presale_id,ppg.goods_id,ppg.sku_id,ppg.presale_stock,ppg.presale_stock as stock,ppg.presale_deposit,ppg.presale_price,pp.sale_num,pp.presale_name,pp.presale_num,pp.start_time,pp.end_time,pp.pay_start_time,pp.pay_end_time,pp.deliver_type,pp.deliver_time,sku.site_id,sku.sku_name,sku.sku_spec_format,sku.price,sku.promotion_type,sku.click_num,g.sale_num  as goods_sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.site_id,sku.goods_content,sku.goods_state,sku.is_virtual,sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,sku.video_url,sku.evaluate,g.goods_image,g.goods_stock,g.goods_name')
    {
        $alias = 'ppg';
        $join = [
            ['goods_sku sku', 'ppg.sku_id = sku.sku_id', 'inner'],
            ['goods g', 'g.goods_id = sku.goods_id', 'inner'],
            ['promotion_presale pp', 'ppg.presale_id = pp.presale_id', 'inner'],
        ];
        $presale_goods_info = model('promotion_presale_goods')->getInfo($condition, $field, $alias, $join);
        return $this->success($presale_goods_info);
    }

    /**
     * 获取预售列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getPresaleList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_presale')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取预售分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getPresalePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {
        $field = 'p.*,g.goods_name,g.goods_image,g.price';
        $alias = 'p';
        $join = [
            [
                'goods g',
                'p.goods_id = g.goods_id',
                'inner'
            ]
        ];
        $list = model('promotion_presale')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }


    /**
     * 获取预售商品列表
     * @param $presale_id
     * @param $site_id
     * @return array
     */
    public function getPresaleGoodsList($presale_id, $site_id)
    {
        $field = 'pbg.*,sku.sku_name,sku.price,sku.sku_image,sku.stock';
        $alias = 'pbg';
        $join = [
            [
                'goods g',
                'g.goods_id = pbg.goods_id',
                'inner'
            ],
            [
                'goods_sku sku',
                'sku.sku_id = pbg.sku_id',
                'inner'
            ]
        ];
        $condition = [
            ['pbg.presale_id', '=', $presale_id], ['pbg.site_id', '=', $site_id],
            ['g.is_delete', '=', 0], ['g.goods_state', '=', 1]
        ];

        $list = model('promotion_presale_goods')->getList($condition, $field, '', $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取预售商品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getPresaleGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'pp.presale_id desc', $field = 'pp.*,g.price,g.goods_name,g.goods_image,g.sale_num  as goods_sale_num,g.unit,g.goods_stock,g.recommend_way')
    {
        $alias = 'pp';
        $join = [
            ['goods g', 'pp.goods_id = g.goods_id', 'inner']
        ];
        $list = model('promotion_presale')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 开启预售活动
     * @param $presale_id
     * @return array|\multitype
     */
    public function cronOpenPresale($presale_id)
    {
        $presale_info = model('promotion_presale')->getInfo([['presale_id', '=', $presale_id]], 'status,goods_id');
        if (!empty($presale_info)) {

            if ($presale_info['status'] == 0) {
                $res = model('promotion_presale')->update(['status' => 1, 'status_name' => $this->status[1]], [['presale_id', '=', $presale_id]]);

                $goods = new Goods();
                $goods->modifyPromotionAddon($presale_info['goods_id'], ['presale' => $presale_id]);

                return $this->success($res);
            } else {
                return $this->error("", "预售活动已开启或者关闭");
            }

        } else {
            return $this->error("", "预售活动不存在");
        }

    }

    /**
     * 关闭预售活动
     * @param $presale_id
     * @return array|\multitype
     */
    public function cronClosePresale($presale_id)
    {
        $presale_info = model('promotion_presale')->getInfo([['presale_id', '=', $presale_id]], 'status,goods_id');
        if (!empty($presale_info)) {

            if ($presale_info['status'] == 1) {
                $res = model('promotion_presale')->update(['status' => 2, 'status_name' => $this->status[2]], [['presale_id', '=', $presale_id]]);

                $goods = new Goods();
                $goods->modifyPromotionAddon($presale_info['goods_id'], ['presale' => $presale_id], true);

                return $this->success($res);
            } else {
                return $this->error("", "该活动已结束");
            }
        } else {
            return $this->error("", "预售活动不存在");
        }
    }


    /**
     * 判断规格值是否禁用
     * @param $presale_id
     * @param $site_id
     * @param $goods
     * @return false|string
     */
    public function getGoodsSpecFormat($presale_id, $site_id, $goods_spec_format = '', $sku_id = 0)
    {
        //获取活动参与的商品sku_ids
        $sku_ids = model('promotion_presale_goods')->getColumn([['presale_id', '=', $presale_id], ['site_id', '=', $site_id]], 'sku_id');
        $goods_model = new Goods();
        if ($sku_id == 0) {
            $res = $goods_model->getGoodsSpecFormat($sku_ids, $goods_spec_format);
        } else {
            $res = $goods_model->getEmptyGoodsSpecFormat($sku_ids, $sku_id);
        }
        return $res;
    }


    /**
     * 判断sku是否参与预售
     * @param $sku_id
     * @return array
     */
    public function isJoinPresaleBySkuId($sku_id)
    {
        $condition = [
            ['ppg.sku_id', '=', $sku_id],
            ['pp.status', '=', 1]
        ];
        $alias = 'ppg';
        $join = [
            ['promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'inner']
        ];
        $info = model('promotion_presale_goods')->getInfo($condition, 'ppg.presale_id,ppg.sku_id', $alias, $join);
        if (empty($info)) {
            return $this->error();
        } else {
            return $this->success(['promotion_type' => 'presale', 'presale_id' => $info['presale_id'], 'sku_id' => $sku_id]);
        }
    }

}