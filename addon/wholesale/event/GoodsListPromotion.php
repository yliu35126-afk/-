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

namespace addon\wholesale\event;

use addon\wholesale\model\Wholesale as WholesaleModel;

/**
 * 商品营销活动信息
 */
class GoodsListPromotion
{

	/**
	 * 商品营销活动信息
	 * @param $param
	 * @return array
	 */
	public function handle($param)
	{
		if (empty($param['promotion']) || $param['promotion'] != 'wholesale') return [];

        $model = new WholesaleModel();
        $condition = [
            [ 'wg.wholesale_goods_id', '>', 0 ],
            [ 'g.goods_state','=',1 ],
            [ 'g.is_delete','=',0 ],
            [ 'g.verify_state', '=', 1]
        ];

        if(!empty($param['site_id'])) $condition[] = ['g.site_id', '=', $param['site_id']];
		if (isset($param['goods_name']) && !empty($param['goods_name'])) {
			$condition[] = ['g.goods_name', 'like', '%' . $param['goods_name'] . '%'];
		}
        //城市分站id
		if(!empty($param['website_id'])){
            $condition[] = ['g.website_id', '=', $param['website_id']];
        }

        $alias = 'g';
        $join = [
            [ 'wholesale_goods wg', 'wg.goods_id = g.goods_id', 'left' ],
            [ 'goods_sku sku', 'g.sku_id = sku.sku_id', 'left' ]
        ];
        $field = 'g.*,wg.wholesale_goods_id, wg.max_price, wg.min_price, wg.min_num, wg.status,sku.sku_id,sku.price,sku.sku_name,sku.sku_image';

        $list = $model->getWholesaleGoodsViewPageList($condition, $param[ 'page' ], $param[ 'page_size' ], 'g.sort desc,g.create_time desc', $field, $alias, $join);
        return $list;
	}
}