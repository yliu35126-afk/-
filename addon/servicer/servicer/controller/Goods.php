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

namespace addon\servicer\servicer\controller;

use addon\servicer\model\Dialogue;
use app\model\goods\Goods as GoodsModel;
use app\model\goods\GoodsBrowse as GoodsBrowseModel;

/**
 * 商品相关
 * Class Goods
 * @package addon\servicer\servicer\controller
 */
class Goods extends BaseServicer
{

    /**
     * 商品列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page        = input('page', 1);
            $page_size   = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $goods_state = input('goods_state', '');
            $goods_class = input('goods_class', '');

            $condition = [['is_delete', '=', 0]];
            if (!empty($this->site_id)) {
                $condition[] = ['site_id', '=', $this->site_id];
            }
            if (!empty($search_text)) {
                $condition[] = ['goods_name|keywords', 'like', '%' . $search_text . '%'];
            }
            if ($goods_state !== '') {
                $condition[] = ['goods_state', '=', $goods_state];
            }
            if (!empty($goods_class)) {
                $condition[] = ['goods_class', '=', $goods_class];
            }
            $order_by = 'create_time desc, goods_id desc';
            $field    = [
                'goods_id, goods_name, goods_stock, sale_num, price, goods_state, sku_id, goods_image'
            ];

            $goods_model = new GoodsModel();
            $res         = $goods_model->getGoodsPageList($condition, $page, $page_size, $order_by, $field);
            return $res;
        }
    }

    /**
     * 商品详情
     */
    public function detail()
    {
        if (request()->isAjax()) {
            $sku_id           = input('sku_id', 0);
            $dialogue_id      = input('dialogue_id', 0);
            $relate_promotion = [];

            $goods_model = new GoodsModel();
            if (empty($dialogue_id) && empty($sku_id)) {
                return $goods_model->error('', 'PARAMETER_ERROR');
            }

            if (!empty($dialogue_id)) {
                $dialogue_model = new Dialogue();
                $dialogue_info  = $dialogue_model->getInfo([
                    ['id', '=', $dialogue_id],
                    ['content_type', '=', Dialogue::CONTENTTYPE_GOODSKU],
                ], 'goods_sku_id, relate_data')['data'];
                if (empty($dialogue_info)) {
                    return $goods_model->error('', '消息记录不存在');
                }
                $sku_id           = $dialogue_info['goods_sku_id'];
                $relate_promotion = $dialogue_info['relate_data_parse'];
            }

            if (empty($sku_id)) {
                return $goods_model->error('', 'PARAMETER_ERROR');
            }

            $goods_model = new GoodsModel();
            $detail      = $goods_model->getGoodsSkuDetail($sku_id)['data'];

            // 查询当前商品参与的营销活动信息
            $goods_promotion = event('GoodsPromotion', ['goods_id' => $detail['goods_id'], 'sku_id' => $sku_id]);
            // 当前咨询的商品活动信息
            $curr_promotion  = [];

            if (!empty($goods_promotion) && !empty($relate_promotion)) {
                foreach ($goods_promotion as $val) {
                    if ($val['promotion_type'] === $relate_promotion['promotion_name']) {
                        $curr_promotion = $val;
                    }
                }
            }
            $detail['goods_promotion']  = $goods_promotion;
            $detail['curr_promotion']   = $curr_promotion;
            $detail['relate_promotion'] = $relate_promotion;

            return $goods_model->success($detail);
        }
    }


    /**
     * 会员商品浏览记录
     */
    public function memberGoodsBrowse()
    {
        if (request()->isAjax()) {
            $page        = input('page', 1);
            $page_size   = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $member_id   = input('member_id', 0);

            $condition = [['gb.member_id', '=', $member_id]];
            if (!empty($this->site_id)) {
                $condition[] = ['gb.site_id', '=', $this->site_id];
            }
            if (!empty($search_text)) {
                $condition[] = ['gs.sku_name|gs.keywords', 'like', '%' . $search_text . '%'];
            }

            $order              = 'gb.browse_time desc, gb.id desc';
            $field              = [
                'gb.browse_time',
                'gs.sku_id, gs.goods_id, gs.sku_name, gs.sku_image, gs.price, gs.goods_state, gs.stock, gs.sale_num'
            ];
            $alias              = 'gb';
            $join               = [
                ['goods_sku gs', 'gs.sku_id = gb.sku_id', 'left']
            ];
            $goods_browse_model = new GoodsBrowseModel();
            return $goods_browse_model->getBrowsePageList($condition, $page, $page_size, $order, $field, $alias, $join);
        }
    }

}