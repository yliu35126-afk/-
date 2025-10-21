<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\api\controller;

use addon\topic\model\Poster;
use addon\topic\model\Topic as TopicModel;
use addon\topic\model\TopicGoods as TopicGoodsModel;
use app\api\controller\BaseApi;
use app\model\shop\Shop as ShopModel;

/**
 * 专题活动商品
 */
class Topicgoods extends BaseApi
{

    /**
     * 详情信息
     */
    public function detail()
    {
        $id = isset($this->params[ 'topic_id' ]) ? $this->params[ 'topic_id' ] : 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $topic_goods_model = new TopicGoodsModel();
        $condition = [
            [ 'ptg.id', '=', $id ],
            [ 'pt.status', '=', 2 ]
        ];
        $goods_sku_detail = $topic_goods_model->getTopicGoodsDetail($condition);
        $goods_sku_detail = $goods_sku_detail[ 'data' ];
        $res[ 'goods_sku_detail' ] = $goods_sku_detail;

        if (empty($goods_sku_detail)) return $this->response($this->error($res));

        if (!empty($goods_sku_detail[ 'goods_spec_format' ])) {
            $goods_spec_format = $topic_goods_model->getGoodsSpecFormat($goods_sku_detail[ 'topic_id' ], $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        }

        //店铺信息
        $shop_model = new ShopModel();
        $shop_info = $shop_model->getShopInfo([ [ 'site_id', '=', $goods_sku_detail[ 'site_id' ] ] ], 'site_id,site_name,is_own,logo,avatar,banner,seo_description,qq,ww,telephone,shop_desccredit,shop_servicecredit,shop_deliverycredit,shop_baozh,shop_baozhopen,shop_baozhrmb,shop_qtian,shop_zhping,shop_erxiaoshi,shop_tuihuo,shop_shiyong,shop_shiti,shop_xiaoxie,shop_sales,sub_num');
        $shop_info = $shop_info[ 'data' ];
        $res[ 'shop_info' ] = $shop_info;
        return $this->response($this->success($res));

    }

    /**
     * 获取商品基本信息
     * @return false|string
     */
    public function info()
    {
        $sku_id = isset($this->params[ 'sku_id' ]) ? $this->params[ 'sku_id' ] : 0;
        $topic_id = isset($this->params[ 'topic_id' ]) ? $this->params[ 'topic_id' ] : 0;
        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_SKU_ID'));
        }
        if (empty($topic_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $topic_goods_model = new TopicGoodsModel();
        $condition = [
            [ 'ptg.topic_id', '=', $topic_id ],
            [ 'ptg.sku_id', '=', $sku_id ],
            [ 'pt.status', '=', 2 ]
        ];
        $goods_sku_detail = $topic_goods_model->getTopicGoodsDetail($condition);
        $goods_sku_detail = $goods_sku_detail[ 'data' ];
        $res[ 'goods_sku_detail' ] = $goods_sku_detail;
        if (!empty($goods_sku_detail)) {
            $goods_spec_format = $topic_goods_model->getGoodsSpecFormat($goods_sku_detail[ 'topic_id' ], $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        } else {
            $sku_id = $topic_goods_model->getGoodsSpecFormat($topic_id, '', $sku_id);
            $res = [ 'type' => 'again', 'sku_id' => $sku_id ];
        }

        return $this->response($this->success($res));
    }

    /**
     * 列表信息
     */
    public function page()
    {
        $topic_id = isset($this->params[ 'topic_id' ]) ? $this->params[ 'topic_id' ] : 0;
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $site_id = isset($this->params[ 'site_id' ]) ? $this->params[ 'site_id' ] : 0;
        $website_id = isset($this->params[ 'website_id' ]) ? $this->params[ 'website_id' ] : 0;
        if (empty($topic_id)) {
            return $this->response($this->error('', 'REQUEST_TOPIC_ID'));
        }
        $condition = [
            [ 'nptg.topic_id', '=', $topic_id ],
            [ 'ngs.goods_state', '=', 1 ],
            [ 'ngs.verify_state', '=', 1 ],
            [ 'ngs.is_delete', '=', 0 ],
            [ 'nptg.default', '=', 1 ],
            [ 's.shop_status', '=', 1 ]
        ];
        //增加site_id筛选
        if ($site_id > 0) {
            $condition[] = [ 'ngs.site_id', '=', $site_id ];
        }
        if ($website_id > 0) {
            $condition[] = [ 'g.website_id', '=', $website_id ];
        }
        $order = 'g.sort desc,g.create_time desc';
        $field = 'nptg.id,nptg.topic_id,nptg.start_time,nptg.end_time,nptg.site_id,ngs.sku_id,ngs.sku_name,ngs.price,ngs.discount_price,ngs.sku_image,ngs.goods_name,nptg.topic_price,ngs.is_free_shipping,ngs.introduction,ngs.is_virtual,g.sale_num,ngs.unit,g.recommend_way';
        $topic_goods_model = new TopicGoodsModel();

        $topic_model = new TopicModel();
        $info = $topic_model->getTopicInfo([ [ "topic_id", "=", $topic_id ] ], 'bg_color,topic_adv,topic_name');
        $info = $info[ 'data' ];

        $res = $topic_goods_model->getTopicGoodsPageList($condition, $page, $page_size, $order, $field);
        $res[ 'data' ][ 'bg_color' ] = $info[ 'bg_color' ];
        $res[ 'data' ][ 'topic_adv' ] = $info[ 'topic_adv' ];
        $res[ 'data' ][ 'topic_name' ] = $info[ 'topic_name' ];

        return $this->response($res);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        if (!empty($qrcode_param)) return $this->response($this->error('', '缺少必须参数qrcode_param'));

        $promotion_type = 'topic';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'suid' ] = $qrcode_param[ 'suid' ] ?? 0;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type);
        return $this->response($res);
    }

}