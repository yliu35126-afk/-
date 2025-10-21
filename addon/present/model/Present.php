<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\present\model;

use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\goods\GoodsStock;
use app\model\system\Cron;
use think\facade\Db;

/**
 * 赠品活动
 */
class Present extends BaseModel
{
    /**
     * 添加赠品
     * @param $params
     * @return array
     */
    public function addPresent($params)
    {
        if (empty($params['sku_ids'])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        $condition = [
            ['ppg.site_id', '=', $params['site_id']],
            ['pp.status', 'in', '1,2'],
            ['ppg.sku_id', 'in', $params['sku_ids']],
            ['', 'exp', Db::raw('not ( (`start_time` > ' . $params['end_time'] . ' and `start_time` > ' . $params['start_time'] . ' )  or (`end_time` < ' . $params['start_time'] . ' and `end_time` < ' . $params['end_time'] . '))')]//todo  修正  所有的优惠都要一样
        ];
        //查询该商品是否存在赠品
        $join = [['promotion_present pp', 'pp.present_id = ppg.present_id', 'inner']];

        $present_info = model('promotion_present_goods')->getInfo($condition, 'ppg.present_id', 'ppg', $join);
        if (!empty($present_info)) {
            return $this->error('', "当前时间段内商品已存在赠品活动");
        }

        // 当前时间
        $time = time();
        if ($time > $params['start_time'] && $time < $params['end_time']) {
            $status = 2;
        } else {
            $status = 1;
        }

        model('promotion_present')->startTrans();
        try {

            foreach ($params[ 'goods_ids' ] as $goods_id) {
                //添加赠品
                $present_data = [
                    'site_id' => $params['site_id'],
                    'site_name' => $params['site_name'],
                    'goods_id' => $goods_id,
                    'start_time' => $params['start_time'],
                    'end_time' => $params['end_time'],
                    'create_time' => $time,
                    'status' => $status
                ];
                $present_id = model("promotion_present")->add($present_data);

                $sku_list_data = [];
                foreach ($params['sku_list'] as $k => $sku) {
                    if ($sku[ 'goods_id' ] == $goods_id) {

                        $sku_list_data[] = [
                            'site_id' => $params[ 'site_id' ],
                            'present_id' => $present_id,
                            'goods_id' => $goods_id,
                            'sku_id' => $sku[ 'sku_id' ]
                        ];
                    }
                }
                model('promotion_present_goods')->addList($sku_list_data);

                $cron = new Cron();
                if ($status == 2) {
                    $goods_model = new Goods();
                    $goods_model->modifyPromotionAddon($goods_id, [ 'present' => $present_id ]);
                    $cron->addCron(1, 0, "赠品活动关闭", "ClosePresent", $params[ 'end_time' ], $present_id);
                } else {
                    $cron->addCron(1, 0, "赠品活动开启", "OpenPresent", $params[ 'start_time' ], $present_id);
                    $cron->addCron(1, 0, "赠品活动关闭", "ClosePresent", $params[ 'end_time' ], $present_id);
                }
            }

            model('promotion_present')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('promotion_present')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 编辑赠品
     * @param $params
     * @return array
     */
    public function editPresent($params)
    {
        //查询赠品活动
        if (empty($params['sku_ids'])) {
            return $this->error('', '该活动至少需要一个商品参与');
        }
        $condition = [
            ['ppg.site_id', '=', $params['site_id']],
            ['pp.status', 'in', '1,2'],
            ['ppg.present_id','<>',$params['present_id']],
            ['ppg.sku_id', 'in', $params['sku_ids']],
            ['', 'exp', Db::raw('not ( (`start_time` > ' . $params['end_time'] . ' and `start_time` > ' . $params['start_time'] . ' )  or (`end_time` < ' . $params['start_time'] . ' and `end_time` < ' . $params['end_time'] . '))')]//todo  修正  所有的优惠都要一样
        ];
        //查询该商品是否存在赠品
        $join = [['promotion_present pp', 'pp.present_id = ppg.present_id', 'inner']];

        $present_info = model('promotion_present_goods')->getInfo($condition, 'ppg.present_id', 'ppg', $join);
        if (!empty($present_info)) {
            return $this->error('', "当前时间段内商品已存在赠品活动");
        }

        // 当前时间
        $time = time();
        if ($time > $params[ 'start_time' ] && $time < $params[ 'end_time' ]) {
            $status = 2;
        } else {
            $status = 1;
        }

        model('promotion_present')->startTrans();
        try {

            $present_data = [
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'status' => $status,
                'modify_time' => time()
            ];
            model('promotion_present')->update($present_data,[['present_id','=',$params['present_id']]]);

            foreach ($params['sku_list'] as $k => $sku) {

                if($sku['is_delete'] == 2){//不参与
                    model('promotion_present_goods')->delete([['present_id','=',$params['present_id']],['sku_id','=',$sku['sku_id']]]);
                }else{
                    $count = model('promotion_present_goods')->getCount([['present_id','=',$params['present_id']],['sku_id','=',$sku['sku_id']]]);
                    if($count == 0){

                        $sku_data = [
                            'site_id' => $params['site_id'],
                            'present_id' => $params['present_id'],
                            'goods_id' => $params['goods_ids'],
                            'sku_id' => $sku['sku_id']
                        ];
                        model('promotion_present_goods')->add($sku_data);
                    }
                }
            }
            $cron = new Cron();
            $cron->deleteCron([['event', '=', 'OpenPresent'], ['relate_id', '=', $params[ 'present_id' ]]]);
            $cron->deleteCron([['event', '=', 'ClosePresent'], ['relate_id', '=', $params[ 'present_id' ]]]);
            if ($status == 2) {
                //活动商品启动
                $this->cronOpenPresent($params[ 'present_id' ]);
                $cron->addCron(1, 0, "赠品活动关闭", "ClosePresent", $params[ 'end_time' ], $params[ 'present_id' ]);
            } else {
                $cron->addCron(1, 0, "赠品活动开启", "OpenPresent", $params[ 'start_time' ], $params[ 'present_id' ]);
                $cron->addCron(1, 0, "赠品活动关闭", "ClosePresent", $params[ 'end_time' ], $params[ 'present_id' ]);
            }

            model('promotion_present')->commit();
            return $this->success();
        }catch(\Exception $e){

            model('promotion_present')->rollback();
            return $this->error('',$e->getMessage().$e->getFile().$e->getLine());
        }
    }

    /**
     * 更新赠品库存
     */
    public function modifyPresentStock($condition, $stock){
        $result = model('promotion_present')->update(['stock' => $stock], $condition);
        if($result !== false){
            return $this->error();
        }
        return $this->success($result);
    }
    /**
     * 删除赠品活动
     * @param $present_id
     * @param $site_id
     * @return array|\multitype
     */
    public function deletePresent($condition)
    {
        //赠品信息
        $present_info = model('promotion_present')->getInfo($condition, 'present_id,status');
        if ($present_info) {
            $present_id = $present_info[ 'present_id' ];
            if (in_array($present_info[ 'status' ], [1, 3])) {
                $res = model('promotion_present')->delete([['present_id', '=', $present_id]]);
                if ($res) {
                    $cron = new Cron();
                    $cron->deleteCron([['event', '=', 'OpenPresent'], ['relate_id', '=', $present_id]]);
                    $cron->deleteCron([['event', '=', 'ClosePresent'], ['relate_id', '=', $present_id]]);
                }
                return $this->success($res);
            } else {
                return $this->error('', '赠品活动进行中或已结束');
            }

        } else {
            return $this->error('', '赠品活动不存在');
        }
    }

    /**
     * 结束赠品活动
     * @param $present_id
     * @param $site_id
     * @return array
     */
    public function finishPresent($condition)
    {
        //赠品信息
        $present_info = model('promotion_present')->getInfo($condition, 'present_id,status,goods_id');
        if ($present_info) {
            $present_id = $present_info['present_id'];
            if ($present_info[ 'status' ] != 3) {
                $res = model('promotion_present')->update(['status' => 3], [['present_id', '=', $present_id]]);
                if ($res) {
                    $cron = new Cron();

                    $cron->deleteCron([['event', '=', 'OpenPresent'], ['relate_id', '=', $present_id]]);
                    $cron->deleteCron([['event', '=', 'ClosePresent'], ['relate_id', '=', $present_id]]);

                    $goods_model = new Goods();
                    $goods_model->modifyPromotionAddon($present_info['goods_id'], [ 'present' => $present_id ], true);
                }
                return $this->success($res);
            } else {
                $this->error('', '该赠品活动已结束');
            }

        } else {
            $this->error('', '该赠品活动不存在');
        }
    }

    /**
     * 获取赠品信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPresentInfo($condition = [], $field = '*')
    {
        //赠品信息
        $present_info = model("promotion_present")->getInfo($condition, $field);
        return $this->success($present_info);
    }

    /**
     * 获取赠品详细信息
     * @param $present_id
     * @return array
     */
    public function getPresentDetail($present_id)
    {

        //赠品信息
        $alias = 'p';
        $join = [
            ['goods g', 'g.goods_id = p.goods_id', 'inner']

        ];
        $present_info = model("promotion_present")->getInfo(
            [
                [ 'p.present_id', '=', $present_id ],
                [ 'g.goods_state', '=', 1 ], [ 'g.is_delete', '=', 0 ]
            ], 'p.*', $alias, $join
        );
        if (!empty($present_info)) {
            //商品sku信息
            $goods_list = model('goods_sku')->getList(
                [ [ 'goods_id', '=', $present_info[ 'goods_id' ] ] ],
                'goods_id,sku_id,sku_name,price,sku_images,stock,sku_image'
            );
            foreach ($goods_list as $k => $v) {

                $count = model('promotion_present_goods')->getCount(
                    [ [ 'present_id', '=', $present_id ], [ 'sku_id', '=', $v[ 'sku_id' ] ] ]
                );
                if ($count == 0) {
                    $goods_list[$k]['is_delete'] = 2;
                }else{
                    $goods_list[$k]['is_delete'] = 1;
                }
            }
            array_multisort(array_column($goods_list, 'is_delete'), SORT_ASC, $goods_list);
            $present_info[ 'sku_list' ] = $goods_list;
        }
        return $this->success($present_info);
    }

    /**
     * 获取赠品参与sku详细信息
     * @param $present_id
     * @return array
     */
    public function getPresentJoinGoodsList($present_id)
    {
        //赠品信息
        $alias = 'p';
        $join = [['goods g', 'g.goods_id = p.goods_id', 'inner']];
        $present_info = model("promotion_present")->getInfo(
            [
                ['p.present_id', '=', $present_id],
                ['g.goods_state', '=', 1], ['g.is_delete', '=', 0]
            ], 'p.*', $alias, $join
        );
        if (!empty($present_info)) {

            $goods_list = model('promotion_present_goods')->getList(
                [['ppg.present_id', '=', $present_info['present_id']]],
                'ppg.sale_num,sku.sku_id,sku.sku_name,sku.price,sku.sku_image,sku.stock',
                '','ppg',[['goods_sku sku','sku.sku_id = ppg.sku_id','inner']]
            );

            $present_info['sku_list'] = $goods_list;
        }
        return $this->success($present_info);
    }

    /**
     * 获取赠品列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getPresentList($condition = [], $field = '*', $order = '', $limit = null)
    {

        $list = model('promotion_present')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取赠品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getPresentPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $field = 'p.*,g.goods_name,g.goods_image,g.price';
        $alias = 'p';
        $join = [
            ['goods g', 'p.goods_id = g.goods_id', 'inner']
        ];
        $list = model('promotion_present')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取拼团商品列表
     * @param $bargain_id
     * @return array
     */
    public function getPresentGoodsList($present_id)
    {
        $field = 'ppg.sale_num,sku.sku_name,sku.price,sku.sku_image,sku.stock';
        $alias = 'ppg';
        $join = [
            ['goods g', 'g.goods_id = ppg.goods_id', 'inner'],
            ['goods_sku sku', 'sku.sku_id = ppg.sku_id', 'inner']
        ];
        $condition = [
            [ 'ppg.present_id', '=', $present_id ],
            [ 'g.is_delete', '=', 0 ], [ 'g.goods_state', '=', 1 ]
        ];

        $list = model('promotion_present_goods')->getList($condition, $field, '', $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取赠品商品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getPresentGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'pg.start_time desc', $field = 'pg.present_id,pg.sku_price,pg.sale_num,pg.site_id,sku.sku_id,sku.price,sku.sku_name,sku.sku_image,g.goods_id,g.goods_name,g.recommend_way', $alias = '', $join = '')
    {
        $list = model('promotion_present_goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 开启赠品活动
     * @param $present_id
     * @return array|\multitype
     */
    public function cronOpenPresent($present_id)
    {
        $present_info = model('promotion_present')->getInfo([
                ['present_id', '=', $present_id]]
            , 'start_time,status'
        );
        if (!empty($present_info)) {
            if ($present_info[ 'start_time' ] <= time() && $present_info[ 'status' ] == 1) {
                $res = model('promotion_present')->update(['status' => 2], [['present_id', '=', $present_id]]);

                return $this->success($res);
            } else {
                return $this->error("", "赠品活动已开启或者关闭");
            }

        } else {
            return $this->error("", "赠品活动不存在");
        }

    }

    /**
     * 关闭赠品活动
     * @param $present_id
     * @return array|\multitype
     */
    public function cronClosePresent($present_id)
    {
        $present_info = model('promotion_present')->getInfo([
                ['present_id', '=', $present_id]]
            , 'start_time,status'
        );
        if (!empty($present_info)) {
            if ($present_info[ 'status' ] != 3) {
                $res = model('promotion_present')->update(['status' => 3], [['present_id', '=', $present_id]]);
                return $this->success($res);
            } else {
                return $this->error("", "该活动已结束");
            }
        } else {
            return $this->error("", "赠品活动不存在");
        }
    }


    /**
     * 赠品商品详情
     * @param array $condition
     * @return array
     */
    public function getPresentGoodsDetail($condition = [])
    {
        $field = 'pg.present_id,pp.status,pp.start_time,pp.end_time,pg.sale_num,sku.sku_id,sku.is_delete,sku.site_id,sku.sku_name,sku.price,sku.sku_spec_format,sku.promotion_type,sku.stock,sku.click_num,sku.sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.goods_id,sku.site_id,sku.goods_content,sku.goods_state,sku.verify_state,sku.is_virtual,sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,sku.video_url,sku.evaluate,sku.goods_id';
        $alias = 'pg';
        $join = [
            ['goods_sku sku', 'pg.sku_id = sku.sku_id', 'inner'],
            ['promotion_present pp', 'pp.present_id = pg.present_id', 'inner'],
        ];

        $goods_info = model('promotion_present_goods')->getInfo($condition, $field, $alias, $join);
        return $this->success($goods_info);
    }

    /**
     * 获取赠品商品信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPresentGoodsInfo($condition = [], $field = '*')
    {
        //赠品信息
        $info = model("promotion_present_goods")->getInfo($condition, $field);
        return $this->success($info);
    }

    /****************************************************************************** 发放赠品 start ******************************************************************/

    /**
     * 发放赠品
     * @param $param
     */
    public function givingPresent($param){

        $sku_id = $param['sku_id'] ?? 0;
        $member_id = $param['member_id'] ?? 0;
        $present_id = $param['present_id'] ?? 0;
        $num = $param['num'] ?? 0;
        $goods_stock_model = new GoodsStock();
        if($present_id > 0 && $member_id > 0 && $num > 0){
            $present_info = model('promotion_present')->getInfo([['present_id', '=', $present_id]]);
            if(empty($present_info)){
                return $this->error([], '赠品不存在');
            }
            if($present_info['status'] != 2){
                return $this->error([], '当前赠品已过期或还未开始');
            }
            $stock_result = $goods_stock_model->decStock(["sku_id" => $sku_id, "num" => $num]);
            if($stock_result['code'] < 0)
                return $stock_result;

            //如果发放成功, 增加以发放数量
            $result = model('promotion_present')->setInc([['present_id', '=', $present_id]], 'sale_num', $num);
            //发放赠品商品的发放量
            $result = model('promotion_present_goods')->setInc([['present_id', '=', $present_id], ['sku_id', '=', $sku_id]], 'sale_num', $num);
            return $stock_result;
        }else{
            return $this->error([], '缺少发放赠品必要参数');
        }

    }
    /****************************************************************************** 发放赠品 end ******************************************************************/
}