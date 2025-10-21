<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pintuan\model;

use addon\sitemessage\model\MemberMessage;
use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\system\Cron;
use think\facade\Db;

/**
 * 拼团活动
 */
class Pintuan extends BaseModel
{
    /**
     * 添加拼团
     * @param $pintuan_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function addPintuan($pintuan_data, $goods, $sku_list)
    {
        if (empty($goods[ 'sku_ids' ])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        $pintuan_data[ 'create_time' ] = time();
        if ($pintuan_data[ 'pintuan_time' ] == 0) {
            return $this->error('', "拼团有效期时长不能为0");
        }

        //查询该商品是否存在拼团
        $pintuan_info = model('promotion_pintuan_goods')->getInfo(
            [
                [ 'ppg.site_id', '=', $pintuan_data[ 'site_id' ] ],
                [ 'pp.status', 'in', '0,1' ],
                [ 'ppg.goods_id', 'in', $goods[ 'goods_ids' ] ],
                [ '', 'exp', Db::raw('not ( (`start_time` > ' . $pintuan_data[ 'end_time' ] . ' and `start_time` > ' . $pintuan_data[ 'start_time' ] . ' )  or (`end_time` < ' . $pintuan_data[ 'start_time' ] . ' and `end_time` < ' . $pintuan_data[ 'end_time' ] . '))') ]
            ], 'ppg.id', 'ppg', [ [ 'promotion_pintuan pp', 'pp.pintuan_id = ppg.pintuan_id', 'left' ] ]
        );
        if (!empty($pintuan_info)) {
            return $this->error('', "当前商品在当前时间段内已经存在拼团活动");
        }

        if (time() > $pintuan_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($pintuan_data[ 'start_time' ] <= time()) {
            $pintuan_data[ 'status' ] = 1;
        } else {
            $pintuan_data[ 'status' ] = 0;
        }
        model("promotion_pintuan")->startTrans();
        try {

            foreach ($goods[ 'goods_ids' ] as $goods_id) {

                //添加拼团活动
                $pintuan_data[ 'goods_id' ] = $goods_id;
                $pintuan_id = model("promotion_pintuan")->add($pintuan_data);

                $sku_list_data = [];
                foreach ($sku_list as $k => $sku) {
                    if ($sku[ 'goods_id' ] == $goods_id) {

                        $promotion_price = $sku[ 'pintuan_price' ];
                        if (isset($pintuan_data[ 'is_promotion' ]) && $pintuan_data[ 'is_promotion' ] == 1) {
                            $promotion_price = $sku[ 'promotion_price' ];
                        }
                        $sku_list_data[] = [
                            'site_id' => $pintuan_data[ 'site_id' ],
                            'pintuan_id' => $pintuan_id,
                            'goods_id' => $pintuan_data[ 'goods_id' ],
                            'sku_id' => $sku[ 'sku_id' ],
                            'pintuan_price' => $sku[ 'pintuan_price' ],
                            'promotion_price' => $promotion_price
                        ];
                    }
                }
                array_multisort(array_column($sku_list_data, 'pintuan_price'), SORT_ASC, $sku_list_data);
                model('promotion_pintuan_goods')->addList($sku_list_data);

                model('promotion_pintuan')->update([ 'pintuan_price' => $sku_list_data[ 0 ][ 'pintuan_price' ] ], [ [ 'pintuan_id', '=', $pintuan_id ] ]);

                $cron = new Cron();
                if ($pintuan_data[ 'status' ] == 1) {
                    $goods = new Goods();
                    $goods->modifyPromotionAddon($goods_id, [ 'pintuan' => $pintuan_id ]);
                    if(addon_is_exit('sitemessage')) {
                        $member_message = new MemberMessage();
                        $member_message->promotionMessageCreate($pintuan_id, 'pintuan');
                    }
                    $cron->addCron(1, 0, "拼团活动关闭", "ClosePintuan", $pintuan_data[ 'end_time' ], $pintuan_id);
                } else {
                    $cron->addCron(1, 0, "拼团活动开启", "OpenPintuan", $pintuan_data[ 'start_time' ], $pintuan_id);
                    $cron->addCron(1, 0, "拼团活动关闭", "ClosePintuan", $pintuan_data[ 'end_time' ], $pintuan_id);
                }
            }

            model('promotion_pintuan')->commit();


            return $this->success();

        } catch (\Exception $e) {
            model('promotion_pintuan')->rollback();
            return $this->error('', $e->getMessage());
        }
    }


    /**
     * 编辑拼团
     * @param $pintuan_data
     * @param $goods
     * @param $sku_list
     * @return array
     */
    public function editPintuan($pintuan_data, $goods, $sku_list)
    {
        if (empty($goods[ 'sku_ids' ])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        //查询该商品是否存在拼团
        $pintuan_info = model('promotion_pintuan_goods')->getInfo(
            [
                [ 'ppg.site_id', '=', $pintuan_data[ 'site_id' ] ],
                [ 'pp.status', 'in', '0,1' ],
                [ 'pp.pintuan_id', '<>', $pintuan_data[ 'pintuan_id' ] ],
                [ 'ppg.sku_id', 'in', $goods[ 'sku_ids' ] ],
                [ '', 'exp', Db::raw('not ( (`start_time` > ' . $pintuan_data[ 'end_time' ] . ' and `start_time` > ' . $pintuan_data[ 'start_time' ] . ' )  or (`end_time` < ' . $pintuan_data[ 'start_time' ] . ' and `end_time` < ' . $pintuan_data[ 'end_time' ] . '))') ]
            ], 'ppg.id', 'ppg', [ [ 'promotion_pintuan pp', 'pp.pintuan_id = ppg.pintuan_id', 'left' ] ]
        );
        if (!empty($pintuan_info)) {
            return $this->error('', "当前商品在当前时间段内已经存在拼团活动");
        }

        $pintuan_count = model("promotion_pintuan")->getCount([ [ 'pintuan_id', '=', $pintuan_data[ 'pintuan_id' ] ], [ 'site_id', '=', $pintuan_data[ 'site_id' ] ] ]);
        if ($pintuan_count == 0) {
            return $this->error('', '该拼团活动不存在');
        }

        $cron = new Cron();
        if (time() > $pintuan_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($pintuan_data[ 'start_time' ] <= time()) {
            $pintuan_data[ 'status' ] = 1;
        } else {
            $pintuan_data[ 'status' ] = 0;
        }

        $pintuan_data[ 'modify_time' ] = time();

        model('promotion_pintuan')->startTrans();
        try {
            $sku_list_data = [];
            foreach ($sku_list as $k => $sku) {
                $count = model('promotion_pintuan_goods')->getCount([ [ 'sku_id', '=', $sku[ 'sku_id' ] ], [ 'pintuan_id', '=', $pintuan_data[ 'pintuan_id' ] ] ]);
                $is_delete = $sku[ 'is_delete' ];
                unset($sku[ 'is_delete' ]);
                if ($is_delete == 2) {//是否参与  1参与  2不参与
                    if ($count) {
                        model('promotion_pintuan_goods')->delete([ [ 'sku_id', '=', $sku[ 'sku_id' ] ], [ 'pintuan_id', '=', $pintuan_data[ 'pintuan_id' ] ] ]);
                    }
                } else {

                    $promotion_price = $sku[ 'pintuan_price' ];
                    if (isset($pintuan_data[ 'is_promotion' ]) && $pintuan_data[ 'is_promotion' ] == 1) {
                        $promotion_price = $sku[ 'promotion_price' ];
                    }
                    $sku_data = [
                        'site_id' => $pintuan_data[ 'site_id' ],
                        'pintuan_id' => $pintuan_data[ 'pintuan_id' ],
                        'goods_id' => $sku[ 'goods_id' ],
                        'sku_id' => $sku[ 'sku_id' ],
                        'pintuan_price' => $sku[ 'pintuan_price' ],
                        'promotion_price' => $promotion_price
                    ];
                    $sku_list_data[] = $sku_data;
                    if ($count > 0) {
                        model('promotion_pintuan_goods')->update($sku_data, [ [ 'sku_id', '=', $sku[ 'sku_id' ] ], [ 'pintuan_id', '=', $pintuan_data[ 'pintuan_id' ] ] ]);
                    } else {
                        model('promotion_pintuan_goods')->add($sku_data);
                    }
                }
            }

            array_multisort(array_column($sku_list_data, 'pintuan_price'), SORT_ASC, $sku_list_data);
            model("promotion_pintuan")->update(
                array_merge($pintuan_data, [ 'pintuan_price' => $sku_list_data[ 0 ][ 'pintuan_price' ] ]),
                [ [ 'pintuan_id', '=', $pintuan_data[ 'pintuan_id' ] ] ]
            );

            if ($pintuan_data[ 'start_time' ] <= time()) {

                $goods = new Goods();
                $goods->modifyPromotionAddon($pintuan_data[ 'goods_id' ], [ 'pintuan' => $pintuan_data[ 'pintuan_id' ] ]);
                //活动商品启动
                $this->cronOpenPintuan($pintuan_data[ 'pintuan_id' ]);
                $cron->deleteCron([ [ 'event', '=', 'OpenPintuan' ], [ 'relate_id', '=', $pintuan_data[ 'pintuan_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'ClosePintuan' ], [ 'relate_id', '=', $pintuan_data[ 'pintuan_id' ] ] ]);

                $cron->addCron(1, 0, "拼团活动关闭", "ClosePintuan", $pintuan_data[ 'end_time' ], $pintuan_data[ 'pintuan_id' ]);
                if(addon_is_exit('sitemessage')) {
                    $member_message = new MemberMessage();
                    $member_message->promotionMessageCreate($pintuan_data['pintuan_id'], 'pintuan');
                }
            } else {
                $cron->deleteCron([ [ 'event', '=', 'OpenPintuan' ], [ 'relate_id', '=', $pintuan_data[ 'pintuan_id' ] ] ]);
                $cron->deleteCron([ [ 'event', '=', 'ClosePintuan' ], [ 'relate_id', '=', $pintuan_data[ 'pintuan_id' ] ] ]);

                $cron->addCron(1, 0, "拼团活动开启", "OpenPintuan", $pintuan_data[ 'start_time' ], $pintuan_data[ 'pintuan_id' ]);
                $cron->addCron(1, 0, "拼团活动关闭", "ClosePintuan", $pintuan_data[ 'end_time' ], $pintuan_data[ 'pintuan_id' ]);
            }

            model('promotion_pintuan')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_pintuan')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 增加拼团组人数及购买人数
     * @param array $data
     * @param array $condition
     * @return array
     */
    public function editPintuanNum($data = [], $condition = [])
    {
        $res = model('promotion_pintuan')->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除拼团
     * @param unknown $pintuan_id
     */
    public function deletePintuan($pintuan_id)
    {
        $pintuan_info = model("promotion_pintuan")->getInfo([ [ 'pintuan_id', '=', $pintuan_id ] ]);
        if ($pintuan_info[ 'status' ] == 1) {
            return $this->error('', "当前活动再进行中，不能删除");
        }
        $res = model("promotion_pintuan")->delete([ [ 'pintuan_id', '=', $pintuan_id ] ]);
        if ($res) {
            //删除拼团商品
            model("promotion_pintuan_goods")->delete([ [ 'pintuan_id', '=', $pintuan_id ] ]);
            //删除拼团组
            model('promotion_pintuan_group')->delete([ [ 'pintuan_id', '=', $pintuan_id ] ]);
            $goods = new Goods();
            $goods->modifyPromotionAddon($pintuan_info[ 'goods_id' ], [ 'pintuan' => $pintuan_id ], true);
            $cron = new Cron();
            $cron->deleteCron([ [ 'event', '=', 'OpenPintuan' ], [ 'relate_id', '=', $pintuan_id ] ]);
            $cron->deleteCron([ [ 'event', '=', 'ClosePintuan' ], [ 'relate_id', '=', $pintuan_id ] ]);
        }
        return $this->success($res);
    }

    /**
     * 拼团失效
     * @param unknown $pintuan_id
     */
    public function invalidPintuan($pintuan_id)
    {
        model('promotion_pintuan')->startTrans();
        try {
            $pintuan_info = model("promotion_pintuan")->getInfo([ [ 'pintuan_id', '=', $pintuan_id ] ]);

            $res = model("promotion_pintuan")->update(
                [ 'status' => 3, 'modify_time' => time() ],
                [ [ 'pintuan_id', '=', $pintuan_id ] ]
            );

            if ($pintuan_info[ 'group_num' ] > 0) {//有人拼团
                //查询所有拼团组
                $group_model = new PintuanGroup();
                $group_info = $group_model->getPintuanGroupList([ [ 'pintuan_id', '=', $pintuan_id ] ], 'group_id');
                $group = $group_info[ 'data' ];

                if (!empty($group)) {
                    foreach ($group as $v) {

                        $result = $group_model->cronClosePintuanGroup($v[ 'group_id' ]);
                        if ($result[ 'code' ] < 0) {
                            model('promotion_pintuan')->rollback();
                            return $result;
                        }
                    }
                }
            }

            $goods = new Goods();
            $goods->modifyPromotionAddon($pintuan_info[ 'goods_id' ], [ 'pintuan' => $pintuan_id ], true);

            model('promotion_pintuan')->commit();
            return $this->success($res);

        } catch (\Exception $e) {

            model('promotion_pintuan')->rollback();
            return $this->error($e->getMessage());
        }

    }

    /**
     * 获取拼团信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPintuanInfo($condition = [], $field = '*')
    {
        $pintuan_info = model("promotion_pintuan")->getInfo($condition, $field);
        return $this->success($pintuan_info);
    }

    /**
     * 获取拼团详细信息
     * @param $pintuan_id
     * @return array
     */
    public function getPintuanDetail($pintuan_id)
    {
        //拼团信息
        $alias = 'p';
        $join = [
            ['goods g', 'g.goods_id = p.goods_id', 'inner']
        ];
        $pintuan_info = model("promotion_pintuan")->getInfo(
            [
                [ 'p.pintuan_id', '=', $pintuan_id ],
                [ 'g.goods_state', '=', 1 ], [ 'g.is_delete', '=', 0 ]
            ], 'p.*', $alias, $join
        );
        if (!empty($pintuan_info)) {
            //商品sku信息
            $goods_list = model('goods_sku')->getList(
                [ [ 'goods_id', '=', $pintuan_info[ 'goods_id' ] ] ],
                'goods_id,sku_id,sku_name,price,sku_images,stock,sku_image'
            );
            foreach ($goods_list as $k => $v) {

                $pintuan_goods = model('promotion_pintuan_goods')->getInfo(
                    [ [ 'pintuan_id', '=', $pintuan_id ], [ 'sku_id', '=', $v[ 'sku_id' ] ] ],
                    'pintuan_price,promotion_price'
                );
                if (empty($pintuan_goods)) {
                    $pintuan_goods = [
                        'pintuan_price' => 0,
                        'promotion_price' => 0
                    ];
                }
                $goods_list[ $k ] = array_merge($v, $pintuan_goods);
            }
            array_multisort(array_column($goods_list, 'pintuan_price'), SORT_DESC, $goods_list);
            $pintuan_info[ 'sku_list' ] = $goods_list;
        }
        return $this->success($pintuan_info);
    }

    /**
     * 获取拼团详细信息
     * @param $pintuan_id
     * @return array
     */
    public function getPintuanJoinGoodsList($pintuan_id)
    {
        //拼团信息
        $alias = 'p';
        $join = [
            ['goods g', 'g.goods_id = p.goods_id', 'inner']
        ];
        $pintuan_info = model("promotion_pintuan")->getInfo(
            [
                ['p.pintuan_id', '=', $pintuan_id],
                ['g.goods_state', '=', 1], ['g.is_delete', '=', 0]
            ], 'p.*', $alias, $join
        );
        if (!empty($pintuan_info)) {

            $goods_list = model('promotion_pintuan_goods')->getList(
                [['ppg.pintuan_id', '=', $pintuan_info['pintuan_id']]],
                'ppg.pintuan_price,ppg.promotion_price,sku.sku_id,sku.sku_name,sku.price,sku.sku_image,sku.stock',
                '','ppg',[['goods_sku sku','sku.sku_id = ppg.sku_id','inner']]
            );

            $pintuan_info['sku_list'] = $goods_list;
        }
        return $this->success($pintuan_info);
    }

    /**
     * 拼团商品详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPintuanGoodsDetail($condition = [], $field = 'pp.order_num,ppg.id,ppg.pintuan_id,ppg.goods_id,ppg.sku_id,ppg.pintuan_price,ppg.promotion_price,pp.pintuan_name,pp.pintuan_num,pp.start_time,pp.end_time,pp.buy_num,pp.is_single_buy,pp.is_promotion,pp.group_num,pp.order_num,sku.site_id,sku.sku_name,sku.sku_spec_format,sku.price,sku.promotion_type,sku.stock,sku.click_num,g.sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.site_id,sku.goods_content,sku.goods_state,sku.is_virtual,sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,sku.video_url,sku.evaluate,g.goods_image,g.goods_stock,g.goods_name,g.verify_state')
    {
        $alias = 'ppg';
        $join = [
            [ 'goods_sku sku', 'ppg.sku_id = sku.sku_id', 'inner' ],
            [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ],
            [ 'promotion_pintuan pp', 'ppg.pintuan_id = pp.pintuan_id', 'inner' ],
        ];
        $pintuan_goods_info = model('promotion_pintuan_goods')->getInfo($condition, $field, $alias, $join);
        return $this->success($pintuan_goods_info);
    }

    /**
     * 获取拼团列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getPintuanList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_pintuan')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取拼团分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getPintuanPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {
        $field = 'p.*,g.goods_name,g.goods_image,g.price';
        $alias = 'p';
        $join = [
            ['goods g', 'p.goods_id = g.goods_id', 'inner']
        ];
        $list = model('promotion_pintuan')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }


    /**
     * 获取拼团商品列表
     * @param $bargain_id
     * @return array
     */
    public function getPintuanGoodsList($pintuan_id)
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
            [ 'pbg.pintuan_id', '=', $pintuan_id ],
            [ 'g.is_delete', '=', 0 ], [ 'g.goods_state', '=', 1 ]
        ];

        $list = model('promotion_pintuan_goods')->getList($condition, $field, '', $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取拼团商品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getPintuanGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'pp.pintuan_id desc', $field = 'pp.order_num,pp.pintuan_id,pp.site_id,pp.pintuan_name,pp.is_virtual_goods,pp.pintuan_num,pp.pintuan_price,pp.pintuan_time,pp.is_recommend,pp.group_num,pp.order_num,g.price,g.goods_id,g.goods_name,g.goods_image,g.sale_num,g.unit,g.goods_stock,g.recommend_way')
    {
        $alias = 'pp';
        $join = [
            [ 'goods g', 'pp.goods_id = g.goods_id', 'inner' ],
            [ 'shop s', 's.site_id = pp.site_id', 'inner' ]
        ];
        $list = model('promotion_pintuan')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 开启拼团活动
     * @param $pintuan_id
     * @return array|\multitype
     */
    public function cronOpenPintuan($pintuan_id)
    {
        $pintuan_info = model('promotion_pintuan')->getInfo([ [ 'pintuan_id', '=', $pintuan_id ] ], 'start_time,status,goods_id');
        if (!empty($pintuan_info)) {
            $goods = new Goods();
            $goods->modifyPromotionAddon($pintuan_info[ 'goods_id' ], [ 'pintuan' => $pintuan_id ]);
            if ($pintuan_info[ 'start_time' ] <= time() && $pintuan_info[ 'status' ] == 0) {
                $res = model('promotion_pintuan')->update([ 'status' => 1 ], [ [ 'pintuan_id', '=', $pintuan_id ] ]);
                if(addon_is_exit('sitemessage')) {
                    $member_message = new MemberMessage();
                    $member_message->promotionMessageCreate($pintuan_id, 'pintuan');
                }
                return $this->success($res);
            } else {
                return $this->error("", "拼团活动已开启或者关闭");
            }
        } else {
            return $this->error("", "拼团活动不存在");
        }

    }

    /**
     * 关闭拼团活动
     * @param $pintuan_id
     * @return array|\multitype
     */
    public function cronClosePintuan($pintuan_id)
    {
        $pintuan_info = model('promotion_pintuan')->getInfo([ [ 'pintuan_id', '=', $pintuan_id ] ], 'site_id,start_time,status');
        if (!empty($pintuan_info)) {
            return $this->invalidPintuan($pintuan_id, $pintuan_info[ 'site_id' ]);
        } else {
            return $this->error("", "拼团活动不存在");
        }
    }


    /**
     * 判断规格值是否禁用
     * @param $bargain_id
     * @param $goods
     * @return false|string
     */
    public function getGoodsSpecFormat($pintuan_id, $goods_spec_format = '',$sku_id = 0)
    {
        //获取活动参与的商品sku_ids
        $sku_ids = model('promotion_pintuan_goods')->getColumn([ [ 'pintuan_id', '=', $pintuan_id ] ], 'sku_id');
        $goods_model = new Goods();
        if($sku_id == 0){
            $res = $goods_model->getGoodsSpecFormat($sku_ids,$goods_spec_format);
        }else{
            $res = $goods_model->getEmptyGoodsSpecFormat($sku_ids,$sku_id);
        }
        return $res;
    }

}