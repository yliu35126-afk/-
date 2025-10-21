<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace addon\supply\model\goods;

use addon\supply\model\order\Order;
use app\model\BaseModel;
use app\model\goods\Config;
use app\model\goods\Goods as GoodsModel;
use app\model\goods\GoodsCategory;
use app\model\system\Stat;

/**
 * 商品迁移
 */
class Move extends BaseModel
{

    /**
     * 保存迁移一部分商品(将供应商商品转化为特殊的结构用于普通商品的添加)
     * @param $condition
     */
    public function move($condition, $site_id)
    {

//        $goods_state  传入的商品状态  可以选择直接上架或是到仓库
        $order_model = new Order();
        $order_list = $order_model->getOrderList($condition)[ 'data' ] ?? [];
        if (empty($order_list))
            return $this->error([], 'ORDER_EMPTY');

        $temp_list = [];
        $goods_id_array = [];
        foreach ($order_list as $order_k => $order_v) {
            $order_goods_condition = array (
                [ 'order_id', '=', $order_v[ 'order_id' ] ],
            );
            $order_goods_list = $order_model->getOrderGoodsList($order_goods_condition)[ 'data' ] ?? [];
            if (!empty($order_goods_list)) {
                foreach ($order_goods_list as $order_goods_k => $order_goods_v) {
                    if (!isset($temp_list[ $order_goods_v[ 'goods_id' ] ])) {
                        $temp_list[ $order_goods_v[ 'goods_id' ] ] = [];
                    }
                    $goods_id_array[] = $order_goods_v[ 'goods_id' ];
                    if (!isset($temp_list[ $order_goods_v[ 'goods_id' ] ][ $order_goods_v[ 'sku_id' ] ])) {
                        $temp_list[ $order_goods_v[ 'goods_id' ] ][ $order_goods_v[ 'sku_id' ] ] = 0;
                    }
                    $temp_list[ $order_goods_v[ 'goods_id' ] ][ $order_goods_v[ 'sku_id' ] ] += $order_goods_v[ 'num' ];
                }
            }
        }
        if (empty($temp_list))
            return $this->error([], 'ORDER_EMPTY');

        $temp_goods_condition = [
            [ 'goods_id', 'in', $goods_id_array ]
        ];
        //查询到可迁移的商品
        $supply_goods_model = new Goods();
        $goods_list = $supply_goods_model->getGoodsList($temp_goods_condition, '*')[ 'data' ] ?? [];
        if (empty($goods_list)) {
            return $this->error([], '找不到可迁移的商品');
        }
        $goods_model = new GoodsModel();
        model('goods')->startTrans();
        try {

            //将供应商商品转化为普通的商品
            //店铺信息
            $shop_info = model('shop')->getInfo([ [ 'site_id', '=', $site_id ] ], 'site_id, site_name, website_id, is_own,cert_id, shop_status');

            $goods_config = new Config();
            $goods_verify_config = $goods_config->getVerifyConfig();
            $goods_verify_config = $goods_verify_config[ 'data' ][ 'value' ];
            $verify_state = 1;
            if (!empty($goods_verify_config[ 'is_open' ])) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }

            // 店铺未认证、审核中的状态下，商品需要审核
            if (empty($shop_info[ 'cert_id' ]) || $shop_info[ 'shop_status' ] == 0 || $shop_info[ 'shop_status' ] == 2) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }

            foreach ($goods_list as $goods_k => $goods_item) {

                //对比当前商品的商品是否已存在,如果已存在 不重复添加,编辑当前商品的库
//                $original_goods_info = model('goods')->getInfo([['supply_goods_id', '=', $goods_item[ 'goods_id' ]]]);
//                //不存在创建
//                if(empty($original_goods_info)){

                //商品的规格sku项
                $sku_list = $supply_goods_model->getGoodsSkuList([ [ 'goods_id', '=', $goods_item[ 'goods_id' ] ] ], '*')[ 'data' ] ?? [];
                if (empty($sku_list)) {
                    return $this->error([], '商品规格项缺失');
                }

                $first_sku_data = $sku_list[ 0 ];

                //先创建新的商品数据(商品默认没有库存,模拟创建操作)
                $goods_data = $goods_item;

                $stock = array_sum($temp_list[ $goods_item[ 'goods_id' ] ] ?? []);
                $goods_data[ 'goods_stock' ] = $stock;
                $goods_data[ 'price' ] = $first_sku_data[ 'min_price' ];
                $goods_data[ 'market_price' ] = $first_sku_data[ 'market_price' ];
                $goods_data[ 'cost_price' ] = $first_sku_data[ 'cost_price' ];

                unset($goods_data[ 'goods_id' ]);//删除原有的自增长键
                unset($goods_data[ 'max_price' ]);//删除最低价
                unset($goods_data[ 'min_price' ]);//删除最高价
                unset($goods_data[ 'min_num' ]);//删除最低购买数量


                //重置部分字段, 库存都为空, 默认不上架
                $goods_data[ 'sku_id' ] = 0;
                $goods_data[ 'create_time' ] = time();
                $goods_data[ 'modify_time' ] = time();

                $goods_data[ 'sale_num' ] = 0;
                $goods_info[ 'promotion_addon' ] = '';//迁移的商品没有营销活动

                $goods_data[ 'evaluate' ] = 0;
                $goods_data[ 'evaluate_shaitu' ] = 0;
                $goods_data[ 'evaluate_shipin' ] = 0;
                $goods_data[ 'evaluate_zhuiping' ] = 0;
                $goods_data[ 'evaluate_haoping' ] = 0;
                $goods_data[ 'evaluate_zhongping' ] = 0;
                $goods_data[ 'evaluate_chaping' ] = 0;
                $goods_data[ 'is_fenxiao' ] = 0;
                $goods_data[ 'fenxiao_type' ] = 1;
                $goods_data[ 'site_id' ] = $shop_info[ 'site_id' ];
                $goods_data[ 'site_name' ] = $shop_info[ 'site_name' ];
                $goods_data[ 'supplier_id' ] = $goods_item[ 'site_id' ];

//            $goods_data['goods_state'] = 0;
                $goods_data[ 'verify_state' ] = $verify_state;
                $goods_data[ 'verify_state_remark' ] = '';

                //迁移的商品不会有定时上下架
                $goods_id = model('goods')->add($goods_data);
                $sku_arr = [];
                foreach ($sku_list as $k => $v) {
                    $sku_data = $v;

                    $sku_data[ 'price' ] = $sku_data[ 'min_price' ];
                    unset($sku_data[ 'sku_id' ]);
                    unset($sku_data[ 'price_json' ]);//删除价格规格
                    unset($sku_data[ 'min_price' ]);//删除最低价
                    unset($sku_data[ 'max_price' ]);//删除最高价
                    unset($sku_data[ 'min_num' ]);//删除最小数量

                    $sku_stock = $temp_list[ $goods_item[ 'goods_id' ] ][ $v[ 'sku_id' ] ] ?? 0;
                    $sku_data[ 'stock' ] = $sku_stock;

                    $sku_data[ 'goods_id' ] = $goods_id;
//                $sku_data['goods_state'] = 0;
                    $sku_data[ 'verify_state' ] = $verify_state;

                    $sku_data[ 'evaluate' ] = 0;
                    $sku_data[ 'evaluate_shaitu' ] = 0;
                    $sku_data[ 'evaluate_shipin' ] = 0;
                    $sku_data[ 'evaluate_zhuiping' ] = 0;
                    $sku_data[ 'evaluate_haoping' ] = 0;
                    $sku_data[ 'evaluate_zhongping' ] = 0;
                    $sku_data[ 'evaluate_chaping' ] = 0;

                    $sku_data[ 'click_num' ] = 0;
                    $sku_data[ 'sale_num' ] = 0;
                    $sku_data[ 'collect_num' ] = 0;

                    $sku_data[ 'discount_id' ] = 0;
                    $sku_data[ 'seckill_id' ] = 0;
                    $sku_data[ 'topic_id' ] = 0;
                    $sku_data[ 'pintuan_id' ] = 0;
                    $sku_data[ 'bargain_id' ] = 0;
                    $sku_data[ 'sale_num' ] = 0;

                    $sku_data[ 'goods_state' ] = 0;
                    $sku_data[ 'verify_state' ] = 0;
                    $sku_data[ 'verify_state_remark' ] = '';
                    $sku_data[ 'supplier_id' ] = $goods_item[ 'site_id' ];
                    $sku_data[ 'start_time' ] = 0;
                    $sku_data[ 'end_time' ] = 0;
                    $sku_data[ 'promotion_type' ] = 0;
                    $sku_data[ 'discount_price' ] = $sku_data[ 'price' ];
                    $sku_data[ 'start_time' ] = 0;

                    $sku_data[ 'site_id' ] = $shop_info[ 'site_id' ];
                    $sku_data[ 'site_name' ] = $shop_info[ 'site_name' ];
                    $sku_arr[] = $sku_data;
                }
                model('goods_sku')->addList($sku_arr);
                // 赋值第一个商品sku_id
                $first_info = model('goods_sku')->getFirstData([ 'goods_id' => $goods_id ], 'sku_id', 'sku_id asc');
                model('goods')->update([ 'sku_id' => $first_info[ 'sku_id' ] ], [ [ 'goods_id', '=', $goods_id ] ]);
                //添加商品属性关联关系
                $goods_model->refreshGoodsAttribute($goods_id, $goods_item[ 'goods_attr_format' ]);
                if (!empty($goods_data[ 'goods_spec_format' ])) {
                    //刷新SKU商品规格项/规格值JSON字符串
                    $goods_model->dealGoodsSkuSpecFormat($goods_id, $goods_data[ 'goods_spec_format' ]);
                }
                //添加店铺添加统计
                $stat = new Stat();
                $stat->addShopStat([ 'add_goods_count' => 1, 'site_id' => $goods_item[ 'site_id' ] ]);

//                }else{//存在的话  编辑已存在的商品(增加库存)
//
//                }

            }
            model('goods')->commit();
            return $this->success($goods_id);
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage() . $e->getFile() . $e->getLine());
        }

    }

    /**
     * 根据订单来查询商品 并复制
     * @param $condition
     * @param int $site_id
     */
    public function orderMove($condition, $site_id = 0)
    {
        $order_model = new Order();
        $order_list = $order_model->getOrderList($condition)[ 'data' ] ?? [];
        if (empty($order_list))
            return $this->error([], 'ORDER_EMPTY');

        $temp_list = [];
        $goods_id_array = [];
        foreach ($order_list as $order_k => $order_v) {
            $order_goods_condition = array (
                [ 'order_id', '=', $order_v[ 'order_id' ] ],
            );
            $order_goods_list = $order_model->getOrderGoodsList($order_goods_condition)[ 'data' ] ?? [];
            if (!empty($order_goods_list)) {
                foreach ($order_goods_list as $order_goods_k => $order_goods_v) {
                    if (!isset($temp_list[ $order_goods_v[ 'goods_id' ] ])) {
                        $temp_list[ $order_goods_v[ 'goods_id' ] ] = [];
                    }
                    $goods_id_array[] = $order_goods_v[ 'goods_id' ];
                    if (!isset($temp_list[ $order_goods_v[ 'goods_id' ] ][ $order_goods_v[ 'sku_id' ] ])) {
                        $temp_list[ $order_goods_v[ 'goods_id' ] ][ $order_goods_v[ 'sku_id' ] ] = 0;
                    }
                    $temp_list[ $order_goods_v[ 'goods_id' ] ][ $order_goods_v[ 'sku_id' ] ] += $order_goods_v[ 'num' ];
                }
            }
        }
        if (empty($temp_list))
            return $this->error([], 'ORDER_EMPTY');

        $temp_goods_condition = [
            [ 'goods_id', 'in', $goods_id_array ]
        ];
        //查询到可迁移的商品
        $supply_goods_model = new Goods();
        $goods_list = $supply_goods_model->getGoodsList($temp_goods_condition, '*')[ 'data' ] ?? [];
        if (empty($goods_list)) {
            return $this->error([], '找不到可迁移的商品');
        }

        model('goods')->startTrans();
        try {

            //店铺信息
            $shop_info = model('shop')->getInfo([ [ 'site_id', '=', $site_id ] ], 'site_id, site_name, website_id, is_own,cert_id, shop_status');
            //循环创建或补充商品库存
            foreach ($goods_list as $k => $v) {
                $item_result = $this->itemMove($v, $shop_info, $temp_list);
                if ($item_result[ 'code' ] < 0) {
                    return $item_result;
                }
            }

            model('supply_order_goods')->update([ 'is_move' => 1 ], $condition);
            model('goods')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 根据订单项id发货
     * @param $condition
     */
    public function orderGoodsMove($condition, $site_id = 0)
    {
        $order_goods_list = model('supply_order_goods')->getList($condition);
        if (empty($order_goods_list))
            return $this->error();

        $temp_list = [];
        $goods_id_array = [];
        if (!empty($order_goods_list)) {
            foreach ($order_goods_list as $order_goods_k => $order_goods_v) {
                if (!isset($temp_list[ $order_goods_v[ 'goods_id' ] ])) {
                    $temp_list[ $order_goods_v[ 'goods_id' ] ] = [];
                }
                $goods_id_array[] = $order_goods_v[ 'goods_id' ];
                if (!isset($temp_list[ $order_goods_v[ 'goods_id' ] ][ $order_goods_v[ 'sku_id' ] ])) {
                    $temp_list[ $order_goods_v[ 'goods_id' ] ][ $order_goods_v[ 'sku_id' ] ] = 0;
                }
                $temp_list[ $order_goods_v[ 'goods_id' ] ][ $order_goods_v[ 'sku_id' ] ] += $order_goods_v[ 'num' ];
            }
        }
        if (empty($temp_list))
            return $this->error([], 'ORDER_EMPTY');

        $temp_goods_condition = [
            [ 'goods_id', 'in', $goods_id_array ]
        ];
        //查询到可迁移的商品
        $supply_goods_model = new Goods();
        $goods_list = $supply_goods_model->getGoodsList($temp_goods_condition, '*')[ 'data' ] ?? [];
        if (empty($goods_list)) {
            return $this->error([], '找不到可迁移的商品');
        }
        model('goods')->startTrans();
        try {

            //店铺信息
            $shop_info = model('shop')->getInfo([ [ 'site_id', '=', $site_id ] ], 'site_id, site_name, website_id, is_own,cert_id, shop_status');
            //循环创建或补充商品库存
            foreach ($goods_list as $k => $v) {
                $item_result = $this->itemMove($v, $shop_info, $temp_list);
                if ($item_result[ 'code' ] < 0) {
                    return $item_result;
                }
            }

            model('supply_order_goods')->update([ 'is_move' => 1 ], $condition);
            model('goods')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 订单项商品导出
     * @param $order_goods
     */
    public function itemMove($goods_item, $shop_info, $temp_list = [])
    {

        model('goods')->startTrans();
        try {
            $category_model = new GoodsCategory();
            //将供应商商品转化为普通的商品
            $goods_config = new Config();
            $goods_verify_config = $goods_config->getVerifyConfig();
            $goods_verify_config = $goods_verify_config[ 'data' ][ 'value' ];
            $verify_state = 1;
            if (!empty($goods_verify_config[ 'is_open' ])) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }

            // 店铺未认证、审核中的状态下，商品需要审核
            if (empty($shop_info[ 'cert_id' ]) || $shop_info[ 'shop_status' ] == 0 || $shop_info[ 'shop_status' ] == 2) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }

            $goods_model = new GoodsModel();
            $supply_goods_model = new Goods();

            //商品的规格sku项
            $sku_list = $supply_goods_model->getGoodsSkuList([ [ 'goods_id', '=', $goods_item[ 'goods_id' ] ] ], '*')[ 'data' ] ?? [];
            if (empty($sku_list)) {
                return $this->error([], '商品规格项缺失');
            }

            //对比当前商品的商品是否已存在,如果已存在 不重复添加,编辑当前商品的库
            $original_goods_info = model('goods')->getInfo([ [ 'supply_goods_id', '=', $goods_item[ 'goods_id' ] ] ]);
            //不存在创建
            if (empty($original_goods_info)) {
                //查询商品分类
                $category_info = $category_model->getCategoryInfo([ [ 'category_id', '=', $goods_item[ 'category_id' ] ] ], 'supply_commission_rate')[ 'data' ] ?? [];
                $commission_rate = $category_info[ 'supply_commission_rate' ] ?? 0;

                $first_sku_data = $sku_list[ 0 ];

                //先创建新的商品数据(商品默认没有库存,模拟创建操作)
                $goods_data = $goods_item;

                $stock = array_sum($temp_list[ $goods_item[ 'goods_id' ] ] ?? []);
                $goods_data[ 'goods_stock' ] = $stock;
                $goods_data[ 'price' ] = $first_sku_data[ 'min_price' ];
//                $goods_data['market_price'] = $first_sku_data['market_price'];
                $goods_data[ 'cost_price' ] = $first_sku_data[ 'cost_price' ];

                unset($goods_data[ 'goods_id' ]);//删除原有的自增长键
                unset($goods_data[ 'max_price' ]);//删除最低价
                unset($goods_data[ 'min_price' ]);//删除最高价
                unset($goods_data[ 'min_num' ]);//删除最低购买数量


                //重置部分字段, 库存都为空, 默认不上架
                $goods_data[ 'sku_id' ] = 0;
                $goods_data[ 'create_time' ] = time();
                $goods_data[ 'modify_time' ] = time();

                $goods_data[ 'promotion_addon' ] = '';//迁移商品 营销活动为空
                $goods_data[ 'sale_num' ] = 0;

                $goods_data[ 'evaluate' ] = 0;
                $goods_data[ 'evaluate_shaitu' ] = 0;
                $goods_data[ 'evaluate_shipin' ] = 0;
                $goods_data[ 'evaluate_zhuiping' ] = 0;
                $goods_data[ 'evaluate_haoping' ] = 0;
                $goods_data[ 'evaluate_zhongping' ] = 0;
                $goods_data[ 'evaluate_chaping' ] = 0;
                $goods_data[ 'is_fenxiao' ] = 0;

                $goods_data[ 'fenxiao_type' ] = 1;
                $goods_data[ 'site_id' ] = $shop_info[ 'site_id' ];
                $goods_data[ 'site_name' ] = $shop_info[ 'site_name' ];
                $goods_data[ 'supplier_id' ] = $goods_item[ 'site_id' ];//供应商id
                $goods_data[ 'commission_rate' ] = $commission_rate;//商品佣金比率
                $goods_data[ 'verify_state' ] = $verify_state;
                $goods_data[ 'verify_state_remark' ] = '';

                $goods_data[ 'supply_goods_id' ] = $goods_item[ 'goods_id' ];//关联供应商商品的商品id
                $goods_id = model('goods')->add($goods_data);
                $sku_arr = [];
                foreach ($sku_list as $k => $v) {
                    $sku_data = $v;

                    $sku_data[ 'price' ] = $sku_data[ 'min_price' ];
                    unset($sku_data[ 'sku_id' ]);
                    unset($sku_data[ 'price_json' ]);//删除价格规格
                    unset($sku_data[ 'min_price' ]);//删除最低价
                    unset($sku_data[ 'max_price' ]);//删除最高价
                    unset($sku_data[ 'min_num' ]);//删除最小数量

                    $sku_stock = $temp_list[ $goods_item[ 'goods_id' ] ][ $v[ 'sku_id' ] ] ?? 0;
                    $sku_data[ 'stock' ] = $sku_stock;

                    $sku_data[ 'goods_id' ] = $goods_id;
                    //                $sku_data['goods_state'] = 0;
                    $sku_data[ 'verify_state' ] = $verify_state;

                    $sku_data[ 'evaluate' ] = 0;
                    $sku_data[ 'evaluate_shaitu' ] = 0;
                    $sku_data[ 'evaluate_shipin' ] = 0;
                    $sku_data[ 'evaluate_zhuiping' ] = 0;
                    $sku_data[ 'evaluate_haoping' ] = 0;
                    $sku_data[ 'evaluate_zhongping' ] = 0;
                    $sku_data[ 'evaluate_chaping' ] = 0;

                    $sku_data[ 'click_num' ] = 0;
                    $sku_data[ 'sale_num' ] = 0;
                    $sku_data[ 'collect_num' ] = 0;

                    $sku_data[ 'sale_num' ] = 0;

                    $sku_data[ 'goods_state' ] = 0;
                    $sku_data[ 'verify_state' ] = 0;
                    $sku_data[ 'verify_state_remark' ] = '';

                    $sku_data[ 'start_time' ] = 0;
                    $sku_data[ 'end_time' ] = 0;
                    $sku_data[ 'promotion_type' ] = 0;
                    $sku_data[ 'discount_price' ] = $sku_data[ 'price' ];
                    $sku_data[ 'start_time' ] = 0;

                    $sku_data[ 'site_id' ] = $shop_info[ 'site_id' ];
                    $sku_data[ 'site_name' ] = $shop_info[ 'site_name' ];
                    $sku_data[ 'supplier_id' ] = $goods_item[ 'site_id' ];//供应商id

                    //移除供应商sku表中的分佣比率
//                    $sku_data['commission_rate'] = $commission_rate;//商品佣金比率

                    $sku_data[ 'supply_goods_id' ] = $goods_item[ 'goods_id' ];//关联供应商商品的商品id
                    $sku_data[ 'supply_sku_id' ] = $v[ 'sku_id' ];
                    $sku_arr[] = $sku_data;
                }
                model('goods_sku')->addList($sku_arr);
                // 赋值第一个商品sku_id
                $first_info = model('goods_sku')->getFirstData([ 'goods_id' => $goods_id ], 'sku_id', 'sku_id asc');
                model('goods')->update([ 'sku_id' => $first_info[ 'sku_id' ] ], [ [ 'goods_id', '=', $goods_id ] ]);
                //添加商品属性关联关系
                $goods_model->refreshGoodsAttribute($goods_id, $goods_item[ 'goods_attr_format' ]);
                if (!empty($goods_data[ 'goods_spec_format' ])) {
                    //刷新SKU商品规格项/规格值JSON字符串
                    $goods_model->dealGoodsSkuSpecFormat($goods_id, $goods_data[ 'goods_spec_format' ]);
                }
                //添加店铺添加统计
                $stat = new Stat();
                $stat->addShopStat([ 'add_goods_count' => 1, 'site_id' => $goods_item[ 'site_id' ] ]);

            } else {//存在的话  编辑已存在的商品(增加库存)
                //编辑已存在的商品  增加库存
//            [['supply_goods_id', '=', $goods_item['goods_id']]]
                $stock = array_sum($temp_list[ $goods_item[ 'goods_id' ] ] ?? []);
                $new_goods_stock = $stock + $original_goods_info[ 'goods_stock' ];//将要改变的商品库存
                $goods_data = array (
                    'goods_stock' => $new_goods_stock
                );
                model('goods')->update($goods_data, [ [ 'goods_id', '=', $original_goods_info[ 'goods_id' ] ] ]);//编辑商品新库存

                //编辑各sku的库存
                foreach ($sku_list as $k => $v) {
                    $sku_stock = $temp_list[ $goods_item[ 'goods_id' ] ][ $v[ 'sku_id' ] ] ?? 0;//sku购买数量  商品库存变动
                    $original_sku_info = model('goods_sku')->getInfo([ [ 'supply_sku_id', '=', $v[ 'sku_id' ] ] ]);
                    $new_sku_stock = $sku_stock + $original_sku_info[ 'stock' ];
                    $sku_data = array (
                        'stock' => $new_sku_stock
                    );
                    model('goods_sku')->update($sku_data, [ [ 'supply_sku_id', '=', $v[ 'sku_id' ] ] ]);//编辑商品新库存
                }

            }
            model('goods')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }
    }

}