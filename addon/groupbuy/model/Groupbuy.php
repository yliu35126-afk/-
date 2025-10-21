<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\groupbuy\model;

use addon\sitemessage\model\MemberMessage;
use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\system\Cron;
use think\facade\Db;

/**
 * 团购活动
 */
class Groupbuy extends BaseModel
{
    /**
     * 添加团购
     * @param $groupbuy_data
     * @param $goods_list
     * @param $goods_ids
     * @return array
     */
    public function addGroupbuy($groupbuy_data, $goods_list, $goods_ids)
    {
        //查询该商品是否存在团购
        $count = model('promotion_groupbuy')->getCount(
            [
                [ 'site_id', '=', $groupbuy_data[ 'site_id' ] ],
                [ 'status', 'in', '1,2' ],
                [ 'goods_id', 'in', $goods_ids ],
                [ '', 'exp', Db::raw('not ( (`start_time` > ' . $groupbuy_data[ 'end_time' ] . ' and `start_time` > ' . $groupbuy_data[ 'start_time' ] . ' )  or (`end_time` < ' . $groupbuy_data[ 'start_time' ] . ' and `end_time` < ' . $groupbuy_data[ 'end_time' ] . '))') ]
            ]
        );
        if ($count > 0) {
            return $this->error('', "当前时间段内有商品存在团购活动");
        }

        // 当前时间
        $time = time();
        if ($time > $groupbuy_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($time > $groupbuy_data[ 'start_time' ] && $time < $groupbuy_data[ 'end_time' ]) {
            $groupbuy_data[ 'status' ] = 2;
        } else {
            $groupbuy_data[ 'status' ] = 1;
        }

        model('promotion_groupbuy')->startTrans();
        try {

            $groupbuy_data[ 'create_time' ] = $time;
            foreach ($goods_list as $v) {

                $groupbuy_id = model("promotion_groupbuy")->add(array_merge($v, $groupbuy_data));
                $cron = new Cron();
                if ($groupbuy_data[ 'status' ] == 2) {
                    $goods = new Goods();
                    $goods->modifyPromotionAddon($v[ 'goods_id' ], [ 'groupbuy' => $groupbuy_id ]);
                    $cron->addCron(1, 0, "团购活动关闭", "CloseGroupbuy", $groupbuy_data[ 'end_time' ], $groupbuy_id);
                    if(addon_is_exit('sitemessage')) {
                        $member_message = new MemberMessage();
                        $member_message->promotionMessageCreate($groupbuy_id, 'groupbuy');
                    }
                } else {
                    $cron->addCron(1, 0, "团购活动开启", "OpenGroupbuy", $groupbuy_data[ 'start_time' ], $groupbuy_id);
                    $cron->addCron(1, 0, "团购活动关闭", "CloseGroupbuy", $groupbuy_data[ 'end_time' ], $groupbuy_id);
                }
            }
            model('promotion_groupbuy')->commit();
            return $this->success();

        } catch (\Exception $e) {

            model('promotion_groupbuy')->rollback();
            return $this->error();
        }

    }

    /**
     * 编辑团购
     * @param $groupbuy_id
     * @param $groupbuy_data
     * @return array|\multitype
     */
    public function editGroupbuy($groupbuy_id, $groupbuy_data)
    {
        //查询该商品是否存在团购
        $count = model('promotion_groupbuy')->getCount(
            [
                [ 'site_id', '=', $groupbuy_data[ 'site_id' ] ],
                [ 'status', 'in', '1,2' ],
                [ 'groupbuy_id', '<>', $groupbuy_id ],
                [ 'goods_id', '=', $groupbuy_data[ 'goods_id' ] ],
                [ '', 'exp', Db::raw('not ( (`start_time` > ' . $groupbuy_data[ 'end_time' ] . ' and `start_time` > ' . $groupbuy_data[ 'start_time' ] . ' )  or (`end_time` < ' . $groupbuy_data[ 'start_time' ] . ' and `end_time` < ' . $groupbuy_data[ 'end_time' ] . '))') ]
            ]
        );
        if ($count > 0) {
            return $this->error('', "当前时间段内该商品存在团购活动");
        }
        // 当前时间
        $time = time();
        if ($time > $groupbuy_data[ 'end_time' ]) {
            return $this->error('', '当前时间不能大于结束时间');
        }
        if ($time > $groupbuy_data[ 'start_time' ] && $time < $groupbuy_data[ 'end_time' ]) {
            $groupbuy_data[ 'status' ] = 2;
        } else {
            $groupbuy_data[ 'status' ] = 1;
        }

        $groupbuy_data[ 'modify_time' ] = time();

        $res = model("promotion_groupbuy")->update($groupbuy_data, [ [ 'groupbuy_id', '=', $groupbuy_id ] ]);

        $cron = new Cron();
        if ($groupbuy_data[ 'status' ] == 2) {
            //活动商品启动
            $this->cronOpenGroupbuy($groupbuy_id);
            $cron->deleteCron([ [ 'event', '=', 'OpenGroupbuy' ], [ 'relate_id', '=', $groupbuy_id ] ]);
            $cron->deleteCron([ [ 'event', '=', 'CloseGroupbuy' ], [ 'relate_id', '=', $groupbuy_id ] ]);

            $cron->addCron(1, 0, "团购活动关闭", "CloseGroupbuy", $groupbuy_data[ 'end_time' ], $groupbuy_id);
            if(addon_is_exit('sitemessage')) {
                $member_message = new MemberMessage();
                $member_message->promotionMessageCreate($groupbuy_id, 'groupbuy');
            }
        } else {
            $cron->deleteCron([ [ 'event', '=', 'OpenGroupbuy' ], [ 'relate_id', '=', $groupbuy_id ] ]);
            $cron->deleteCron([ [ 'event', '=', 'CloseGroupbuy' ], [ 'relate_id', '=', $groupbuy_id ] ]);

            $cron->addCron(1, 0, "团购活动开启", "OpenGroupbuy", $groupbuy_data[ 'start_time' ], $groupbuy_id);
            $cron->addCron(1, 0, "团购活动关闭", "CloseGroupbuy", $groupbuy_data[ 'end_time' ], $groupbuy_id);
        }
        return $this->success($res);
    }

    /**
     * 删除团购活动
     * @param $groupbuy_id
     * @return array|\multitype
     */
    public function deleteGroupbuy($groupbuy_id)
    {
        //团购信息
        $groupbuy_info = model('promotion_groupbuy')->getInfo([ [ 'groupbuy_id', '=', $groupbuy_id ] ], 'groupbuy_id,status,goods_id');
        if ($groupbuy_info) {

            if (in_array($groupbuy_info[ 'status' ], [ 1, 3 ])) {
                $res = model('promotion_groupbuy')->delete([ [ 'groupbuy_id', '=', $groupbuy_id ] ]);
                if ($res) {
                    $goods = new Goods();
                    $goods->modifyPromotionAddon($groupbuy_info[ 'goods_id' ], [ 'groupbuy' => $groupbuy_id ], true);
                    $cron = new Cron();
                    $cron->deleteCron([ [ 'event', '=', 'OpenGroupbuy' ], [ 'relate_id', '=', $groupbuy_id ] ]);
                    $cron->deleteCron([ [ 'event', '=', 'CloseGroupbuy' ], [ 'relate_id', '=', $groupbuy_id ] ]);
                }
                return $this->success($res);
            } else {
                return $this->error('', '团购活动进行中或已结束');
            }

        } else {
            return $this->error('', '团购活动不存在');
        }
    }

    /**
     * 结束团购活动
     * @param $groupbuy_id
     * @return array
     */
    public function finishGroupbuy($groupbuy_id)
    {
        //团购信息
        $groupbuy_info = model('promotion_groupbuy')->getInfo([ [ 'groupbuy_id', '=', $groupbuy_id ] ], 'groupbuy_id,status,goods_id');
        if (!empty($groupbuy_info)) {
            $goods = new Goods();
            $goods->modifyPromotionAddon($groupbuy_info[ 'goods_id' ], [ 'groupbuy' => $groupbuy_id ], true);
            if ($groupbuy_info[ 'status' ] != 3) {
                $res = model('promotion_groupbuy')->update([ 'status' => 3 ], [ [ 'groupbuy_id', '=', $groupbuy_id ] ]);
                if ($res) {
                    $cron = new Cron();
                    $cron->deleteCron([ [ 'event', '=', 'OpenGroupbuy' ], [ 'relate_id', '=', $groupbuy_id ] ]);
                    $cron->deleteCron([ [ 'event', '=', 'CloseGroupbuy' ], [ 'relate_id', '=', $groupbuy_id ] ]);
                }
                return $this->success($res);
            } else {
                $this->error('', '该团购活动已结束');
            }

        } else {
            $this->error('', '该团购活动不存在');
        }
    }

    /**
     * 获取团购信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getGroupbuyInfo($condition = [], $field = 'pg.groupbuy_id,pg.site_id,pg.goods_id,pg.groupbuy_price,pg.buy_num,pg.create_time,pg.start_time,pg.end_time,pg.sell_num,pg.status,g.goods_name,g.goods_image,g.price,g.goods_stock')
    {
        $alias = 'pg';
        $join = [
            [ 'goods g', 'g.goods_id = pg.goods_id', 'inner' ]
        ];
        $groupbuy_info = model("promotion_groupbuy")->getInfo($condition, $field, $alias, $join);
        return $this->success($groupbuy_info);
    }

    /**
     * 团购商品详情
     * @param array $condition
     * @return array
     */
    public function getGroupbuyGoodsDetail($condition = [])
    {
        $field = 'pg.groupbuy_id,pg.groupbuy_price,pg.buy_num,pg.start_time,pg.end_time,pg.sell_num,pg.status,
        sku.sku_id,sku.sku_name,sku.price,sku.sku_spec_format,
        sku.promotion_type,sku.stock,sku.click_num,g.sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.goods_id,sku.site_id,sku.goods_content,
        sku.goods_state,sku.is_virtual,sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,sku.video_url,sku.evaluate,sku.goods_id,
        g.goods_image,g.goods_stock,g.goods_name,g.verify_state';
        $alias = 'pg';
        $join = [
            [ 'goods_sku sku', 'pg.goods_id = sku.goods_id', 'inner' ],
            [ 'goods g', 'g.goods_id = sku.goods_id', 'inner' ],
        ];

        $goods_info = model('promotion_groupbuy')->getInfo($condition, $field, $alias, $join);
        return $this->success($goods_info);
    }

    /**
     * 获取团购列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getGroupbuyList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('promotion_groupbuy')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取团购分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getGroupbuyPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {

        $field = 'pg.groupbuy_id,pg.site_id,pg.goods_id,pg.groupbuy_price,pg.buy_num,pg.create_time,
        pg.start_time,pg.end_time,pg.sell_num,pg.status,
        g.goods_name,g.goods_image,g.price,g.goods_stock';
        $alias = 'pg';
        $join = [
            [ 'goods g', 'g.goods_id = pg.goods_id', 'inner' ],
        ];
        $list = model('promotion_groupbuy')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取团购商品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getGroupbuyGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'pg.groupbuy_id desc')
    {
        $field = 'pg.groupbuy_id,pg.groupbuy_price,pg.sell_num,pg.site_id,pg.buy_num,
        sku.sku_id,sku.price,sku.sku_name,sku.sku_image,g.goods_id,g.goods_name,g.goods_image,g.goods_stock,g.recommend_way';
        $alias = 'pg';
        $join = [
            [ 'goods g', 'pg.goods_id = g.goods_id', 'inner' ],
            [ 'goods_sku sku', 'g.sku_id = sku.sku_id', 'inner' ],
            [ 'shop s', 's.site_id = pg.site_id', 'inner' ]
        ];
        $list = model('promotion_groupbuy')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 开启团购活动
     * @param $groupbuy_id
     * @return array|\multitype
     */
    public function cronOpenGroupbuy($groupbuy_id)
    {
        $groupbuy_info = model('promotion_groupbuy')->getInfo([ [ 'groupbuy_id', '=', $groupbuy_id ] ], 'start_time,status,goods_id');
        if (!empty($groupbuy_info)) {
            $goods = new Goods();
            $goods->modifyPromotionAddon($groupbuy_info[ 'goods_id' ], [ 'groupbuy' => $groupbuy_id ]);
            if ($groupbuy_info[ 'start_time' ] <= time() && $groupbuy_info[ 'status' ] == 1) {
                $res = model('promotion_groupbuy')->update([ 'status' => 2 ], [ [ 'groupbuy_id', '=', $groupbuy_id ] ]);
                if(addon_is_exit('sitemessage')) {
                    $member_message = new MemberMessage();
                    $member_message->promotionMessageCreate($groupbuy_id, 'groupbuy');
                }
                return $this->success($res);
            } else {
                return $this->error("", "团购活动已开启或者关闭");
            }

        } else {
            return $this->error("", "团购活动不存在");
        }

    }

    /**
     * 关闭团购活动
     * @param $groupbuy_id
     * @return array|\multitype
     */
    public function cronCloseGroupbuy($groupbuy_id)
    {
        $groupbuy_info = model('promotion_groupbuy')->getInfo([ [ 'groupbuy_id', '=', $groupbuy_id ] ], 'start_time,status,goods_id');
        if (!empty($groupbuy_info)) {
            $goods = new Goods();
            $goods->modifyPromotionAddon($groupbuy_info[ 'goods_id' ], [ 'groupbuy' => $groupbuy_id ], true);
            if ($groupbuy_info[ 'status' ] != 3) {
                $res = model('promotion_groupbuy')->update([ 'status' => 3 ], [ [ 'groupbuy_id', '=', $groupbuy_id ] ]);
                return $this->success($res);
            } else {
                return $this->error("", "该活动已结束");
            }
        } else {
            return $this->error("", "团购活动不存在");
        }
    }


    /**
     * 订单支付
     * @param $param
     * @return mixed
     */
    public function orderPay($param)
    {
        $order_goods = model('order_goods')->getInfo([ [ 'order_id', '=', $param[ 'order_id' ] ] ], 'goods_id,num');
        if (!empty($order_goods)) {
            //获取团购id
            $groupbuy_id = model('promotion_groupbuy')->getValue(
                [ [ 'goods_id', '=', $order_goods[ 'goods_id' ] ], [ 'status', '=', 2 ] ],
                'groupbuy_id'
            );
            if ($groupbuy_id != 0) {
                //增加销售量
                model('promotion_groupbuy')->setInc([ [ 'groupbuy_id', '=', $groupbuy_id ] ], 'sell_num', $order_goods[ 'num' ]);
            }
        }
        return $this->success();
    }

}