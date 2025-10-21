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

namespace addon\groupbuy\api\controller;

use addon\groupbuy\model\Groupbuy as GroupbuyModel;
use addon\groupbuy\model\Poster;
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
        $groupbuy_id = isset($this->params[ 'groupbuy_id' ]) ? $this->params[ 'groupbuy_id' ] : 0;
        $sku_id = isset($this->params[ 'sku_id' ]) ? $this->params[ 'sku_id' ] : 0;
        if (empty($groupbuy_id)) {
            return $this->response($this->error('', 'REQUEST_GROUPBUY_ID'));
        }
        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_SKU_ID'));
        }

        $groupbuy_model = new GroupbuyModel();
        $condition = [
            [ 'sku.sku_id', '=', $sku_id ],
            [ 'pg.groupbuy_id', '=', $groupbuy_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.verify_state', '=', 1 ]
        ];
        $info = $groupbuy_model->getGroupbuyGoodsDetail($condition);

        // 查询当前商品参与的营销活动信息
//		$goods_promotion = event('GoodsPromotion', ['goods_id' => $info[ 'data' ][ 'goods_id' ], 'sku_id' => $info[ 'data' ][ 'sku_id' ]]);
//		$info[ 'data' ][ 'goods_promotion' ] = $goods_promotion;
        return $this->response($info);
    }

    /**
     * 团购商品详情信息
     */
    public function detail()
    {
        $groupbuy_id = isset($this->params[ 'groupbuy_id' ]) ? $this->params[ 'groupbuy_id' ] : 0;
        if (empty($groupbuy_id)) {
            return $this->response($this->error('', 'REQUEST_GROUPBUY_ID'));
        }

        $groupbuy_model = new GroupbuyModel();
        $condition = [
            [ 'pg.groupbuy_id', '=', $groupbuy_id ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.verify_state', '=', 1 ]
        ];
        $goods_sku_detail = $groupbuy_model->getGroupbuyGoodsDetail($condition);
        $goods_sku_detail = $goods_sku_detail[ 'data' ];
        $res[ 'goods_sku_detail' ] = $goods_sku_detail;

        if (empty($goods_sku_detail)) return $this->response($this->error($res));

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
            [ 'pg.status', '=', 2 ],// 状态（1未开始  2进行中  3已结束）
            [ 'g.goods_stock', '>', 0 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 's.shop_status', '=', 1 ], //只查看处于开启状态的店铺
            [ 'g.verify_state', '=', 1 ]
        ];
        //增加site_id筛选
        if ($site_id > 0) {
            $condition[] = [ 'pg.site_id', '=', $site_id ];
        }
        if ($website_id > 0) {
            $condition[] = [ 'g.website_id', '=', $website_id ];
        }
        if (!empty($goods_id_arr)) {
            $condition[] = [ 'sku.goods_id', 'in', $goods_id_arr ];
        }

        $groupbuy_model = new GroupbuyModel();
        $list = $groupbuy_model->getGroupbuyGoodsPageList($condition, $page, $page_size, 'g.sort desc,g.create_time desc');

        return $this->response($list);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        if (!empty($qrcode_param)) return $this->response($this->error('', '缺少必须参数qrcode_param'));

        $promotion_type = 'groupbuy';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'suid' ] = $qrcode_param[ 'suid' ] ?? 0;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type);
        return $this->response($res);
    }
}