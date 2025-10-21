<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\store\model;

use app\model\BaseModel;


class StoreGoods extends BaseModel
{

    /**
     * 商品编辑之后
     */
    public function goodsEditAfter($goods_id, $site_id){

        $store_list = model('store')->getList([ ['site_id', '=', $site_id] ], 'store_id');
        if (!empty($store_list)) {
            $goods_sku = model('goods_sku')->getColumn([ ['goods_id', '=', $goods_id] ], 'sku_id');
            model('store_goods_sku')->startTrans();
            try {
                foreach ($store_list as $v) {
                    $store_id = $v['store_id'];
                    $store_goods_sku = model('store_goods_sku')->getColumn([ ['store_id', '=', $store_id ], ['goods_id', '=', $goods_id] ], 'sku_id');
                    if (!empty($store_goods_sku)) {

                            model('store_goods_sku')->delete([ ['store_id', '=', $store_id ], ['goods_id', '=', $goods_id], ['sku_id', 'not in', $goods_sku ] ]);
                            $stock = model('store_goods_sku')->getSum([ ['store_id', '=', $store_id ],['goods_id', '=', $goods_id]], 'store_stock');
                            model('store_goods')->update(['store_goods_stock' => $stock], [ ['store_id', '=', $store_id ], ['goods_id', '=', $goods_id]]);
                    }
                }

                model('store_goods_sku')->commit();
                return $this->success();
            } catch (\Exception $e) {
                model('store_goods_sku')->rollback();
                return $this->error('', $e->getMessage());
            }
        }
    }


    /**
	 * 门店详情
	 * @param $condition
	 * @param string $fields
	 * @return array
	 */
	public function getStoreGoodsInfo($condition, $fields = '*')
	{
		$res = model('store_goods')->getInfo($condition, $fields);
		return $this->success($res);
	}
	
	/**
	 * 获取门店商品列表
	 * @param array $condition
	 * @param string $field
	 * @param string $order
	 * @param string $limit
	 */
	public function getStoreGoodsList($condition = [], $field = '*', $order = '', $limit = null)
	{
		
		$list = model('store_goods')->getList($condition, $field, $order, '', '', '', $limit);
		foreach ($list as &$v) {
			$v['store_goods_skus'] = model('store_goods_sku')->getList([
				[ 'store_id', '=', $v['store_id'] ],
				[ 'goods_id', '=', $v['goods_id'] ]
			], 'sku_id,goods_id,store_stock,store_sale_num');
		}
		return $this->success($list);
	}
	
	
	/**
	 * 获取商品分页列表
	 * @param array $condition
	 * @param number $page
	 * @param string $page_size
	 * @param string $order
	 * @param string $field
	 */
	public function getGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $store_id, $order = 'g.create_time desc', $field = '*')
	{
		
		$alias = 'g';
		$join = [
			[
				'store_goods sg',
				'sg.goods_id = g.goods_id and (sg.store_id is null or sg.store_id = ' . $store_id . ')',
				'left'
			]
		];
		$field = 'g.*,sg.store_goods_stock,sg.store_sale_num';
		
		$list = model('goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
		return $this->success($list);
	}
}