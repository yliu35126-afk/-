<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wholesale\model;

use app\model\BaseModel;
use app\model\goods\Goods;

/**
 * 批发活动
 */
class Wholesale extends BaseModel
{


    /**
     * 添加批发商品
     * @param $data
     */
    public function addGoodsWholesale($data)
    {
        $condition = [ [ 'goods_id', 'in', $data[ 'goods_ids' ] ], [ 'site_id', '=', $data[ 'site_id' ] ] ];
        $count = model('wholesale_goods')->getCount($condition);
        if ($count > 0) {
            return $this->error([], '有商品已存在批发!');
        }
        return $this->setGoodsWholesale($data);
    }

    /**
     * 编辑批发商品
     * @param $data
     * @return array|\multitype
     */
    public function editGoodsWholesale($data)
    {
        return $this->setGoodsWholesale($data);
    }

    /**
     * 设置批发商品
     * @param $data
     * @return array|\multitype
     */
    public function setGoodsWholesale($data)
    {
        $goods_ids = explode(',', $data[ 'goods_ids' ]);
        $price_array = json_decode($data[ 'price_json' ], true);
        //循环同步各sku规格项
        $all_temp_array = [];

        model("wholesale_goods")->startTrans();
        //循环生成多个订单
        try {
            $goods = new Goods();
            foreach ($goods_ids as $goods_id) {

                foreach ($price_array as $sku_item) {

                    if ($goods_id == $sku_item[ 'goods_id' ]) {

                        //不参与
                        if (isset($sku_item[ 'is_delete' ]) && $sku_item[ 'is_delete' ] == 2) {
                            model('wholesale_goods_sku')->delete([ 'goods_id' => $goods_id, 'sku_id' => $sku_item[ 'sku_id' ] ]);
                            continue;
                        }

                        $temp_array = $sku_item[ 'sku_data' ];
                        array_multisort(array_column($temp_array, 'num'), SORT_ASC, $temp_array);
                        $price_json = json_encode($temp_array);
                        $num_column = array_column($temp_array, 'num');
                        $price_column = array_column($temp_array, 'price');
                        if (count($num_column) != count(array_unique($num_column))) {
                            model("order")->rollback();
                            return $this->error([], '规格阶梯价格的数量不能重复!');
                        }
                        $min_price = min($price_column);
                        $max_price = max($price_column);
                        $min_num = min($num_column);
                        $item_data = array (
                            'sku_id' => $sku_item[ 'sku_id' ],
                            'min_price' => $min_price,
                            'max_price' => $max_price,
                            'min_num' => $min_num,
                            'site_id' => $data[ 'site_id' ],
                            'site_name' => $data[ 'site_name' ],
                            'price_json' => $price_json
                        );
                        $item_result = $this->setSkuWholesale($item_data);
                        if ($item_result[ 'code' ]){
                            model("order")->rollback();
                            return $item_result;
                        }
                        $all_temp_array = array_merge($all_temp_array, $temp_array);
                    }
                }

                //比对
                $all_min_price = min(array_column($all_temp_array, 'price'));
                $all_max_price = max(array_column($all_temp_array, 'price'));
                $all_min_num = min(array_column($all_temp_array, 'num'));
                //同步批发商品主表(通过比对全部订单项)
                $sync_result = $this->syncWholesaleGoods($data[ 'site_id' ], $data[ 'site_name' ], $goods_id, $all_max_price, $all_min_price, $all_min_num);
                if ($sync_result[ 'code' ] < 0) {
                    model("order")->rollback();
                    return $sync_result;
                }

                $goods->modifyPromotionAddon($goods_id, [ 'wholesale' => $sync_result[ 'data' ] ]);
            }

            model("wholesale_goods")->commit();
            return $this->success();
        } catch (\Exception $e) {
            model("wholesale_goods")->rollback();
            return $this->error('', $e->getMessage());
        }
    }


    /**
     * 添加批发
     * @param $wholesale_data
     * @return array|\multitype
     */
    public function setSkuWholesale($data)
    {
        $wholesale_condition = array (
            [ 'sku_id', '=', $data[ 'sku_id' ] ],
            [ 'site_id', '=', $data[ 'site_id' ] ]
        );
        $wholesale_sku_info = model('wholesale_goods_sku')->getInfo($wholesale_condition, 'wholesale_sku_id, goods_id');
        if (!empty($wholesale_sku_info)) {
            $data[ 'update_time' ] = time();

            model('wholesale_goods_sku')->update($data, $wholesale_condition);
            $data[ 'goods_id' ] = $wholesale_sku_info[ 'goods_id' ];
        } else {
            $goods_model = new Goods();
            $sku_condition = array (
                [ 'sku_id', '=', $data[ 'sku_id' ] ],
                [ 'site_id', '=', $data[ 'site_id' ] ]
            );
            $sku_info_result = $goods_model->getGoodsSkuInfo($sku_condition, 'goods_id, sku_name,sku_image');
            $sku_info = $sku_info_result[ 'data' ] ?? [];
            if (empty($sku_info))
                return $this->error([], '');

            $data[ 'goods_id' ] = $sku_info[ 'goods_id' ];
            $data[ 'sku_name' ] = $sku_info[ 'sku_name' ];
            $data[ 'sku_image' ] = $sku_info[ 'sku_image' ];
            $data[ 'create_time' ] = time();
            model('wholesale_goods_sku')->add($data);
        }

        return $this->success();
    }

    /**
     * 同步批发商品主表
     * @param $goods_id
     * @param $max_price
     * @param $min_price
     * @param $min_num
     */
    public function syncWholesaleGoods($site_id, $site_name, $goods_id, $max_price, $min_price, $min_num)
    {
        $condition = [ [ 'goods_id', '=', $goods_id ], [ 'site_id', '=', $site_id ] ];
        $wholesale_sku_info = model('wholesale_goods')->getInfo($condition, 'wholesale_goods_id, max_price, min_price, min_num');

        if (empty($wholesale_sku_info)) {
            $goods_model = new Goods();
            $goods_condition = array (
                [ 'goods_id', '=', $goods_id ]
            );
            $goods_info_result = $goods_model->getGoodsInfo($goods_condition, 'goods_name, goods_image');
            $goods_info = $goods_info_result[ 'data' ] ?? [];
            if (empty($goods_info))
                return $this->error([], '商品不存在!');


            $data = array (
                'max_price' => $max_price,
                'goods_id' => $goods_id,
                'min_price' => $min_price,
                'min_num' => $min_num,
                'goods_name' => $goods_info[ 'goods_name' ],
                'goods_image' => $goods_info[ 'goods_image' ],
                'create_time' => time(),
                'site_id' => $site_id,
                'site_name' => $site_name
            );
            $wholesale_goods_result = model('wholesale_goods')->add($data);
            if ($wholesale_goods_result === false)
                return $this->error();

            $wholesale_goods_id = $wholesale_goods_result;

        } else {
//            $new_max_price = max($wholesale_sku_info['max_price'], $max_price);
//            $new_min_price = min($wholesale_sku_info['min_price'], $min_price);
//            $new_min_num = min($wholesale_sku_info['min_num'], $min_num);
            $data = array (
                'max_price' => $max_price,
                'min_price' => $min_price,
                'min_num' => $min_num,
            );
            $wholesale_goods_result = model('wholesale_goods')->update($data, $condition);
            if ($wholesale_goods_result === false)
                return $this->error();

            $wholesale_goods_id = $wholesale_sku_info[ 'wholesale_goods_id' ];
        }
        return $this->success($wholesale_goods_id);
    }

    /**
     * 商品参与批发
     * @param $goods_id
     */
    public function delete($condition)
    {

        $list = model('wholesale_goods')->getList($condition, 'wholesale_goods_id,goods_id');
        if (empty($list))
            return $this->error();

        $result = model('wholesale_goods')->delete($condition);
        if ($result === false)
            return $this->error();

        $sku_result = model('wholesale_goods_sku')->delete($condition);
        if ($sku_result === false)
            return $this->error();

        $goods = new Goods();
        foreach ($list as $k => $v) {
            $goods->modifyPromotionAddon($v[ 'goods_id' ], [ 'wholesale' => $v[ 'wholesale_goods_id' ] ], true);
        }

        return $this->success();
    }


    /**
     * 获取批发信息（所有sku）
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getWholesaleGoodsDetail($goods_id)
    {
        $condition = [
            [ 'wg.goods_id', '=', $goods_id ],
            [ 'g.goods_state', '=', 1 ], [ 'g.is_delete', '=', 0 ]
        ];
        $join = [ [ 'goods g', 'g.goods_id = wg.goods_id', 'inner' ] ];
        $wholesale_info = model('wholesale_goods')->getInfo($condition, 'wg.*,g.price', 'wg', $join);
        if (!empty($wholesale_info)) {

            //商品sku信息
            $sku_list = model('goods_sku')->getList(
                [ [ 'goods_id', '=', $goods_id ] ],
                'goods_id,sku_id,sku_name,price,sku_images,stock,sku_image'
            );

            foreach ($sku_list as $k => $v) {

                $wholesale_goods = model('wholesale_goods_sku')->getInfo([ [ 'goods_id', '=', $goods_id ], [ 'sku_id', '=', $v[ 'sku_id' ] ] ]);
                if (empty($wholesale_goods)) {
                    $wholesale_goods = [
                        'price_array' => [],
                        'min_price' => '0.00',
                        'max_price' => '0.00',
                        'min_num' => 0,
                        'is_delete' => 2
                    ];
                } else {
                    $wholesale_goods[ 'price_array' ] = json_decode($wholesale_goods[ 'price_json' ], true);
                    $wholesale_goods[ 'is_delete' ] = 1;
                }
                $sku_list[ $k ] = array_merge($v, $wholesale_goods);
            }

            $wholesale_info[ 'sku_list' ] = $sku_list;
        }
        return $this->success($wholesale_info);
    }

    /**
     * 获取批发信息（参与sku）
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getWholesaleJoinGoodsDetail($goods_id)
    {
        $condition = [
            [ 'wg.goods_id', '=', $goods_id ],
            [ 'g.goods_state', '=', 1 ], [ 'g.is_delete', '=', 0 ]
        ];
        $join = [ [ 'goods g', 'g.goods_id = wg.goods_id', 'inner' ] ];
        $wholesale_info = model('wholesale_goods')->getInfo($condition, 'wg.*,g.price', 'wg', $join);
        if (!empty($wholesale_info)) {

            //商品sku信息
            $sku_list = model('wholesale_goods_sku')->getList(
                [ [ 'wgs.goods_id', '=', $goods_id ] ],
                'wgs.*,sku.sku_id,sku.sku_name,sku.price,sku.sku_image,sku.stock',
                '', 'wgs', [ [ 'goods_sku sku', 'sku.sku_id = wgs.sku_id', 'inner' ] ]
            );
            if (!empty($sku_list)) {

                foreach ($sku_list as $k => $v) {

                    $sku_list[ $k ][ 'price_array' ] = json_decode($v[ 'price_json' ], true);
                }
            }
            $wholesale_info[ 'sku_list' ] = $sku_list;
        }
        return $this->success($wholesale_info);
    }


    /**
     * 获取批发信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getWholesaleSkuDetail($condition = [], $field = 'gs.*,wgs.max_price, wgs.min_price, wgs.min_num,wgs.wholesale_sku_id,wgs.price_json,g.goods_image')
    {
        $alias = 'gs';
        $join = [
            [ 'wholesale_goods_sku wgs', 'gs.sku_id = wgs.sku_id', 'left' ],
            [ 'goods g', 'g.goods_id = gs.goods_id', 'inner' ],
        ];
        $info = model('goods_sku')->getInfo($condition, $field, $alias, $join);
        if (!empty($info)) {
            $info[ 'price_array' ] = empty($info[ 'price_json' ]) ? [] : json_decode($info[ 'price_json' ], true);
        }
        return $this->success($info);
    }


    /**
     * 获取批发分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getWholesalePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $alias = 'wgk';
        $join = [
            [ 'goods_sku sku', 'wgk.sku_id = sku.sku_id', 'inner' ]
        ];
        $list = model('wholesale_goods_sku')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取批发商品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getWholesaleGoodsViewPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'g.create_time desc', $field = 'g.*,wg.wholesale_goods_id, wg.max_price, wg.min_price, wg.min_num, wg.status,sku.sku_id,sku.price,sku.sku_name,sku.sku_image', $alias = '', $join = '')
    {
        $list = model('goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }


    /**
     * 获取批发商品规格列表
     * @param $bargain_id
     * @return array
     */
    public function getWholesaleGoodsSkuList($goods_id)
    {
        $field = 'wgs.*,sku.sku_name,sku.price,sku.sku_image,sku.stock';
        $alias = 'wgs';
        $join = [
            [ 'goods g', 'g.goods_id = wgs.goods_id', 'inner' ],
            [ 'goods_sku sku', 'sku.sku_id = wgs.sku_id', 'inner' ]
        ];
        $condition = [
            [ 'wgs.goods_id', '=', $goods_id ],
            [ 'g.is_delete', '=', 0 ], [ 'g.goods_state', '=', 1 ]
        ];

        $list = model('wholesale_goods_sku')->getList($condition, $field, 'wgs.min_price asc', $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取批发商品规格分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getWholesaleSkuViewPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'g.create_time desc', $field = '*')
    {
        $alias = 'wgs';
        $join = [
            [ 'goods_sku gs', 'gs.sku_id = wgs.sku_id', 'inner' ]
        ];
        $list = model('wholesale_goods_sku')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }


    /**
     * 判断规格值是否禁用
     * @param $bargain_id
     * @param $goods
     * @return false|string
     */
    public function getGoodsSpecFormat($goods_id, $goods_spec_format = '', $sku_id = 0)
    {
        //获取活动参与的商品sku_ids
        $sku_ids = model('wholesale_goods_sku')->getColumn([ [ 'goods_id', '=', $goods_id ] ], 'sku_id');
        $goods_model = new Goods();
        if ($sku_id == 0) {
            $res = $goods_model->getGoodsSpecFormat($sku_ids, $goods_spec_format);
        } else {
            $res = $goods_model->getEmptyGoodsSpecFormat($sku_ids, $sku_id);
        }
        return $res;
    }
}