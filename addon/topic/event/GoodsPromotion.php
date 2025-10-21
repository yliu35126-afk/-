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

namespace addon\topic\event;

use addon\topic\model\TopicGoods;
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
            if (!empty($promotion_addon['topic'])) {
                $topic_model  = new TopicGoods();
                $condition    = [
                    ['pt.topic_id', '=', $promotion_addon['topic']],
                    ['sku.sku_id', '=', $param['sku_id']],
                    ['pt.status', '=', 2]
                ];
                $field        = 'sku.goods_id,sku.sku_id,sku.price,sku.site_id,ptg.id,ptg.topic_id,ptg.topic_price,pt.topic_name';
                $goods_detail = $topic_model->getTopicGoodsDetail($condition, $field);
                $goods_detail = $goods_detail['data'];
                if (!empty($goods_detail)) {
                    $goods_detail['stock'] = $goods_info['goods_stock'];
                    $goods_detail['promotion_type'] = 'topic';
                    $goods_detail['promotion_name'] = '专题活动';
                    return $goods_detail;
                }
            }
        }
        return [];
    }
}













