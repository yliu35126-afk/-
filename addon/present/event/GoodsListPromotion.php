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


namespace addon\present\event;

use addon\present\model\present;

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
        if (empty($param[ 'promotion' ]) || $param[ 'promotion' ] != 'present') return [];

        $condition[] = [ 'pg.status', '=', 2 ];
        if (!empty($param[ 'site_id' ])) $condition[] = [ 'pg.site_id', '=', $param[ 'site_id' ] ];
        if (!empty($param[ 'website_id' ])) $condition[] = [ 'g.website_id', '=', $param[ 'website_id' ] ];
        if (isset($param[ 'goods_name' ]) && !empty($param[ 'goods_name' ])) {
            $condition[] =[
                [ 'g.goods_name', 'like', '%' . $param[ 'goods_name' ] . '%' ],
                [ 'g.verify_state', '=', 1]
            ];
        }
        $model = new present();
        $field = 'pg.present_id,pg.present_price,pg.sale_num,pg.site_id,
        sku.sku_id,sku.price,sku.sku_name,sku.sku_image,g.goods_id,g.goods_name,g.goods_image,g.goods_stock';

        $alias = 'pg';
        $join = [
            [ 'goods g', 'pg.sku_id = g.sku_id', 'inner' ],
            [ 'goods_sku sku', 'pg.sku_id = sku.sku_id', 'inner' ]
        ];

        $list = $model->getPresentGoodsPageList($condition, $param[ 'page' ], $param[ 'page_size' ], 'pg.present_id desc', $field, $alias, $join);
        return $list;
    }
}