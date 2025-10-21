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

namespace addon\seckill\api\controller;

use addon\seckill\model\Poster;
use addon\seckill\model\Seckill as SeckillModel;
use addon\seckill\model\SeckillOrderCreate;
use app\api\controller\BaseApi;
use app\model\shop\Shop as ShopModel;
use think\facade\Db;

/**
 * 秒杀商品
 */
class Seckillgoods extends BaseApi
{

    /**
     * 详情信息
     */
    public function detail()
    {

        $seckill_id = isset($this->params[ 'seckill_id' ]) ? $this->params[ 'seckill_id' ] : 0;
        if (empty($seckill_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $seckill_model = new SeckillModel();
        $order = new SeckillOrderCreate();
        $condition = [
            [ 'ps.id', '=', $seckill_id ],
            [ 'psg.status', '=', 1 ],
            [ 'ps.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.verify_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $goods_sku_detail = $seckill_model->getSeckillGoodsInfo($condition);
        $goods_sku_detail = $goods_sku_detail[ 'data' ];

        $num = $order->getGoodsSeckillNum($seckill_id);
        $time_data = $seckill_model->getSeckillInfo($seckill_id);
        $time_data = $time_data[ 'data' ];
        $goods_sku_detail[ 'sale_num' ] = $num;
        $goods_sku_detail[ 'seckill_start_time' ] = $time_data[ 'seckill_start_time' ];
        $goods_sku_detail[ 'seckill_end_time' ] = $time_data[ 'seckill_end_time' ];

        $res[ 'goods_sku_detail' ] = $goods_sku_detail;
        if (empty($goods_sku_detail)) return $this->response($this->error($res));

        if (!empty($goods_sku_detail[ 'goods_spec_format' ])) {
            //判断商品规格项
            $goods_spec_format = $seckill_model->getGoodsSpecFormat($seckill_id, $goods_sku_detail[ 'goods_spec_format' ]);
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
     * 基础信息
     */
    public function info()
    {
        $seckill_id = isset($this->params[ 'seckill_id' ]) ? $this->params[ 'seckill_id' ] : 0;
        $sku_id = isset($this->params[ 'sku_id' ]) ? $this->params[ 'sku_id' ] : 0;
        if (empty($seckill_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $seckill_model = new SeckillModel();
        $order = new SeckillOrderCreate();
        $condition = [
            [ 'ps.id', '=', $seckill_id ],
            [ 'psg.sku_id', '=', $sku_id ],
            [ 'psg.status', '=', 1 ],
            [ 'ps.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.verify_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ]
        ];
        $goods_sku_detail = $seckill_model->getSeckillGoodsInfo($condition);
        $goods_sku_detail = $goods_sku_detail[ 'data' ];

        $res[ 'goods_sku_detail' ] = $goods_sku_detail;
        if (!empty($goods_sku_detail)) {

            $num = $order->getGoodsSeckillNum($seckill_id);
            $time_data = $seckill_model->getSeckillInfo($seckill_id);
            $time_data = $time_data[ 'data' ];
            $goods_sku_detail[ 'sale_num' ] = $num;
            $goods_sku_detail[ 'seckill_start_time' ] = $time_data[ 'seckill_start_time' ];
            $goods_sku_detail[ 'seckill_end_time' ] = $time_data[ 'seckill_end_time' ];
            //判断商品规格项
            $goods_spec_format = $seckill_model->getGoodsSpecFormat($seckill_id, $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);

        } else {
            $sku_id = $seckill_model->getGoodsSpecFormat($seckill_id, '', $sku_id);
            $res = [ 'type' => 'again', 'sku_id' => $sku_id ];
        }
        return $this->response($this->success($res));
    }

    public function page()
    {
        $seckill_id = isset($this->params[ 'seckill_id' ]) ? $this->params[ 'seckill_id' ] : 0;
        $site_id = isset($this->params[ 'site_id' ]) ? $this->params[ 'site_id' ] : 0;
        $website_id = isset($this->params[ 'website_id' ]) ? $this->params[ 'website_id' ] : 0;
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        if (empty($seckill_id)) {
            return $this->response($this->error('', 'REQUEST_SECKILL_ID'));
        }
        $seckill_model = new SeckillModel();

        $condition = [
            [ 'nps.seckill_time_id','like', '%,'.$seckill_id.',%' ],
            [ 'g.goods_stock', '>', 0 ],
            [ 'nps.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.verify_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 's.shop_status', '=', 1 ]
        ];

        $order = 'g.sort desc,g.create_time';

        $field = 'nps.id,nps.site_id,nps.seckill_name,nps.status,nps.remark,nps.start_time,nps.end_time,
            nps.goods_id,nps.seckill_time_id,nps.seckill_price,
            g.goods_name,g.goods_image,g.goods_stock,g.price,g.sale_num,g.recommend_way';
        $alias = 'nps';
        $join = [
            [ 'goods g', 'nps.goods_id = g.goods_id', 'inner' ],
            [ 'shop s', 'nps.site_id = s.site_id', 'inner' ]
        ];
        //增加site_id筛选
        if ($site_id > 0) {
            $condition[] = [ 'g.site_id', '=', $site_id ];
        }
        if ($website_id > 0) {
            $condition[] = [ 'g.website_id', '=', $website_id ];
        }
        $res = $seckill_model->getSeckillGoodsPageList($condition, $page, $page_size, $order, $field, $alias, $join);

        $list = $res[ 'data' ][ 'list' ];
        foreach ($list as $key => $val) {
            if ($val[ 'price' ] != 0) {
                $discount_rate = floor($val[ 'seckill_price' ] / $val[ 'price' ] * 100);
            } else {
                $discount_rate = 100;
            }
            $list[ $key ][ 'discount_rate' ] = $discount_rate;
        }
        return $this->response($res);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        if (!empty($qrcode_param)) return $this->response($this->error('', '缺少必须参数qrcode_param'));

        $promotion_type = 'seckill';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'suid' ] = $qrcode_param[ 'suid' ] ?? 0;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type);
        return $this->response($res);
    }

}