<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\pintuan\api\controller;

use addon\pintuan\model\Pintuan as PintuanModel;
use addon\pintuan\model\Poster;
use app\api\controller\BaseApi;
use app\model\shop\Shop as ShopModel;

/**
 * 拼团商品
 */
class Goods extends BaseApi
{

    /**
     * 基础信息
     */
    public function info()
    {
        $sku_id = isset($this->params[ 'sku_id' ]) ? $this->params[ 'sku_id' ] : 0;
        $pintuan_id = isset($this->params[ 'pintuan_id' ]) ? $this->params[ 'pintuan_id' ] : 0;
        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_SKU_ID'));
        }
        if (empty($pintuan_id)) {
            return $this->response($this->error('', 'REQUEST_PINTUAN_ID'));
        }
        $goods = new PintuanModel();
        $condition = [
            [ 'ppg.sku_id', '=', $sku_id ],
            [ 'ppg.pintuan_id', '=', $pintuan_id ],
            [ 'pp.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.verify_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $info = $goods->getPintuanGoodsDetail($condition);
        if (!empty($info[ 'data' ])) {
            if (!empty($info[ 'data' ][ 'goods_spec_format' ])) {
                $goods_spec_format = $goods->getGoodsSpecFormat($pintuan_id, $info[ 'data' ][ 'goods_spec_format' ]);
                $info[ 'data' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
            }
        } else {
            $sku_id = $goods->getGoodsSpecFormat($pintuan_id, '', $sku_id);
            $info = $this->success([ 'type' => 'again', 'sku_id' => $sku_id ]);
        }
        return $this->response($info);
    }

    /**
     * 拼团商品详情信息
     */
    public function detail()
    {
        $pintuan_id = isset($this->params[ 'pintuan_id' ]) ? $this->params[ 'pintuan_id' ] : 0;
        if (empty($pintuan_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $pintuan_model = new PintuanModel();
        $condition = [
            [ 'ppg.pintuan_id', '=', $pintuan_id ],
            [ 'pp.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.verify_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $goods_sku_detail = $pintuan_model->getPintuanGoodsDetail($condition);
        $goods_sku_detail = $goods_sku_detail[ 'data' ];
        $res[ 'goods_sku_detail' ] = $goods_sku_detail;

        if (empty($goods_sku_detail)) return $this->response($this->error($res));

        if (!empty($goods_sku_detail[ 'goods_spec_format' ])) {
            //判断商品规格项
            $goods_spec_format = $pintuan_model->getGoodsSpecFormat($pintuan_id, $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        }

        //店铺信息
        $shop_model = new ShopModel();
        $shop_info = $shop_model->getShopInfo([ [ 'site_id', '=', $goods_sku_detail[ 'site_id' ] ] ], 'site_id,site_name,is_own,logo,avatar,banner,seo_description,qq,ww,telephone,shop_desccredit,shop_servicecredit,shop_deliverycredit,shop_baozh,shop_baozhopen,shop_baozhrmb,shop_qtian,shop_zhping,shop_erxiaoshi,shop_tuihuo,shop_shiyong,shop_shiti,shop_xiaoxie,shop_sales,sub_num');
        $shop_info = $shop_info[ 'data' ];
        $res[ 'shop_info' ] = $shop_info;

        return $this->response($this->success($res));
    }

    public function page()
    {
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $goods_id_arr = isset($this->params[ 'goods_id_arr' ]) ? $this->params[ 'goods_id_arr' ] : '';//goods_id数组
        $site_id = $this->params[ 'site_id' ] ?? 0; //站点id
        $website_id = $this->params[ 'website_id' ] ?? 0; //城市分站站点id
        $condition = [
            [ 'pp.status', '=', 1 ],// 状态（0正常 1活动进行中  2活动已结束  3失效  4删除）
            [ 'g.goods_stock', '>', 0 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.verify_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 's.shop_status', '=', 1 ]
        ];
        //增加site_id筛选
        if ($site_id > 0) {
            $condition[] = [ 'pp.site_id', '=', $site_id ];
        }
        if ($website_id > 0) {
            $condition[] = [ 'g.website_id', '=', $website_id ];
        }
        if (!empty($goods_id_arr)) {
            $condition[] = [ 'g.goods_id', 'in', $goods_id_arr ];
        }

        $pintuan_model = new PintuanModel();
        $list = $pintuan_model->getPintuanGoodsPageList($condition, $page, $page_size, 'g.sort desc,g.create_time desc');

        return $this->response($list);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        if (!empty($qrcode_param)) return $this->response($this->error('', '缺少必须参数qrcode_param'));

        $promotion_type = 'pintuan';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'suid' ] = $qrcode_param[ 'suid' ] ?? 0;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type);
        return $this->response($res);
    }
}