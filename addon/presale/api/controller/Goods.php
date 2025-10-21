<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\presale\api\controller;

use addon\presale\model\Presale as PresaleModel;
use addon\presale\model\PresaleOrder;
use app\api\controller\BaseApi;
use addon\presale\model\Poster;
use app\model\goods\GoodsService;
use app\model\goods\Goods as GoodsModel;
use app\model\shop\Shop as ShopModel;
use think\Console;

/**
 * 预售商品
 */
class Goods extends BaseApi
{

    /**
     * 基础信息
     */
    public function info()
    {
        $presale_id = isset($this->params['id']) ? $this->params['id'] : 0;
        $sku_id = isset($this->params['sku_id']) ? $this->params['sku_id'] : 0;
        if (empty($presale_id)) {
            return $this->response($this->error('', 'REQUEST_PRESALE_ID'));
        }
        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_SKU_ID'));
        }

        $presale_model = new PresaleModel();
        $condition = [
            ['sku.sku_id', '=', $sku_id],
            ['pp.presale_id', '=', $presale_id],
            ['pp.status', '=', 1],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0]
        ];
        $info = $presale_model->getPresaleGoodsDetail($condition);
        if (empty($info['data'])) {
            return $this->response($this->success());
        }

        // 查询当前商品参与的营销活动信息
//		$goods_promotion = event('GoodsPromotion', ['goods_id' => $info[ 'data' ][ 'goods_id' ], 'sku_id' => $info[ 'data' ][ 'sku_id' ]]);
//		$info[ 'data' ][ 'goods_promotion' ] = $goods_promotion;
        return $this->response($info);
    }


    /**
     * 预售商品详情信息
     */
    public function detail()
    {

        $presale_id = isset($this->params['id']) ? $this->params['id'] : 0;
        if (empty($presale_id)) {
            return $this->response($this->error('', 'REQUEST_PRESALE_ID'));
        }
        $sku_id = isset($this->params['sku_id']) ? $this->params['sku_id'] : 0;

        $token = $this->checkToken();

        $presale_model = new PresaleModel();

        $condition = [
            ['pp.presale_id', '=', $presale_id],
            //  ['pp.site_id', '=', $this->site_id],
            ['pp.status', '=', 1],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0]
        ];
        if ($sku_id > 0) {
            $condition[] = ['ppg.sku_id', '=', $sku_id];
        }

        $goods_sku_detail = $presale_model->getPresaleGoodsDetail($condition);
        //判断是否已存在订单


        $resd['goods_sku_detail'] = $goods_sku_detail;
        if (empty($goods_sku_detail['data'])) return $this->response($this->error($resd));

        $goods_sku_detail['data']['sure_buy_num'] = $goods_sku_detail['data']['presale_num'];
        $goods_sku_detail['data']['is_buy'] = 0;

        if ($this->member_id > 0) {
            $buy_num_condition = [
                ['member_id', '=', $this->member_id],
                ['presale_id', '=', $presale_id],
                ['order_status', '>=', 0],
                ['refund_status', '=', 0]
            ];
            $presale_order = new PresaleOrder();

            $presale_order_count = $presale_order->getBuyNum($buy_num_condition, 'num');

            if ($presale_order_count['data'] > 0) {
                $goods_sku_detail['data']['is_buy'] = 1;
            }

        }

        if (isset($buy_num_condition['member_id'])) {
            unset($buy_num_condition['member_id']);
        } else {

            $presale_order = new PresaleOrder();
            $buy_num_condition = [
                ['presale_id', '=', $presale_id],
                ['order_status', '>=', 0],
                ['refund_status', '=', 0]
            ];
        }

        $presale_num = $presale_order->getPresaleOrderNum($buy_num_condition);

        $res['presale_num'] = $presale_num['data'];

        $goods_sku_detail = $goods_sku_detail['data'];
        $res['goods_sku_detail'] = $goods_sku_detail;
        if (empty($goods_sku_detail)) return $this->response($this->error($res));
        // 查询当前商品参与的营销活动信息
        $goods_promotion = event('GoodsPromotion', ['goods_id' => $goods_sku_detail['goods_id'], 'sku_id' => $goods_sku_detail['sku_id']]);
        $res['goods_sku_detail']['goods_promotion'] = $goods_promotion;
        //店铺信息
        $shop_model = new ShopModel();
        $shop_info = $shop_model->getShopInfo([['site_id', '=', $goods_sku_detail['site_id']]], 'site_id,site_name,is_own,logo,avatar,banner,seo_description,qq,ww,telephone,shop_desccredit,shop_servicecredit,shop_deliverycredit,shop_baozh,shop_baozhopen,shop_baozhrmb,shop_qtian,shop_zhping,shop_erxiaoshi,shop_tuihuo,shop_shiyong,shop_shiti,shop_xiaoxie,shop_sales,sub_num');
        $shop_info = $shop_info['data'];
        $res['shop_info'] = $shop_info;
        return $this->response($this->success($res));
    }

    public function page()
    {
        $page = isset($this->params['page']) ? $this->params['page'] : 1;
        $page_size = isset($this->params['page_size']) ? $this->params['page_size'] : PAGE_LIST_ROWS;
        $goods_id_arr = isset($this->params['goods_id_arr']) ? $this->params['goods_id_arr'] : '';//goods_id数组
        $site_id = $this->params['site_id'] ?? 0;
        $condition = [
            ['pp.status', '=', 1],// 状态（0未开始 1进行中 2已结束）
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0],
        ];
        if ($site_id > 0) {
            $condition[] = ['g.site_id', '=', $site_id];
        }
        if (!empty($goods_id_arr)) {
            $condition[] = ['g.goods_id', 'in', $goods_id_arr];
        }

        $presale_model = new PresaleModel();
        $list = $presale_model->getPresaleGoodsPageList($condition, $page, $page_size);

        return $this->response($list);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        if (!empty($qrcode_param)) return $this->response($this->error('', '缺少必须参数qrcode_param'));

        $promotion_type = 'presale';
        $qrcode_param = json_decode($this->params['qrcode_param'], true);
        $qrcode_param['suid'] = $qrcode_param['suid'] ?? 0;
        $poster = new Poster();
        $res = $poster->goods($this->params['app_type'], $this->params['page'], $qrcode_param, $promotion_type);
        return $this->response($res);
    }
}