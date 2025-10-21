<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\pintuan\event;

use addon\pintuan\model\Pintuan;
use app\model\goods\Goods as GoodsModel;

/**
 * 商品营销活动信息
 */
class GoodsPromotion
{

    /**
     * 商品营销活动信息
     * @param $param
     * @return array
     */
    public function handle($param)
    {
        if (empty($param['goods_id'])) return [];
        $goods_model = new GoodsModel();
        $goods_info  = $goods_model->getGoodsInfo([['goods_id', '=', $param['goods_id']]], 'promotion_addon,goods_stock');
        $goods_info  = $goods_info['data'];
        if (!empty($goods_info['promotion_addon'])) {
            $promotion_addon = json_decode($goods_info['promotion_addon'], true);
            if (!empty($promotion_addon['pintuan'])) {
                $pintuan_model = new Pintuan();
                $condition     = [
                    ['ppg.pintuan_id', '=', $promotion_addon['pintuan']],
                    ['pp.status', '=', 1],
                    ['g.goods_state', '=', 1],
                    ['g.is_delete', '=', 0]
                ];
                $field         = 'ppg.id,ppg.pintuan_id,ppg.goods_id,ppg.sku_id,ppg.pintuan_price,ppg.promotion_price,pp.pintuan_name,sku.site_id';
                $goods_detail  = $pintuan_model->getPintuanGoodsDetail($condition, $field);
                $goods_detail  = $goods_detail['data'];
                if (!empty($goods_detail)) {
                    $goods_detail['stock'] = $goods_info['goods_stock'];
                    $goods_detail['promotion_type'] = 'pintuan';
                    $goods_detail['promotion_name'] = '拼团';
                    return $goods_detail;
                }
            }
        }
        return [];
    }
}