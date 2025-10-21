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

namespace addon\bargain\api\controller;

use addon\bargain\model\Poster;
use app\api\controller\BaseApi;
use addon\bargain\model\Bargain as BargainModel;
use app\model\shop\Shop as ShopModel;

/**
 * 砍价商品
 */
class Goods extends BaseApi
{

    /**
     * 获取砍价活动列表
     */
    public function page()
    {
        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $id_arr = isset($this->params[ 'id_arr' ]) ? $this->params[ 'id_arr' ] : '';
        $site_id = $this->params[ 'site_id' ] ?? 0; //站点id
        $website_id = $this->params[ 'website_id' ] ?? 0; //城市分站站点id
        $bargain = new BargainModel();

        $condition = [
            [ 'pb.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.verify_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 's.shop_status', '=', 1 ] //只查看处于开启状态的店铺
        ];
        //增加site_id筛选
        if ($site_id > 0) {
            $condition[] = [ 'pb.site_id', '=', $site_id ];
        }
        if($website_id > 0){
            $condition[] = ['g.website_id', '=', $website_id];
        }
        if (!empty($id_arr)) {
            $condition[] = [ 'pb.goods_id', 'in', $id_arr ];
        } else {
            $token = $this->checkToken();
            if ($token[ 'code' ] == 0) {
                $goods_id = $bargain->getBargainingGoodsId($this->member_id);
                if (!empty($goods_id)) $condition[] = [ 'g.goods_id', 'not in', $goods_id ];
            }
        }

        $data = $bargain->getBargainPageList($condition, $page, $page_size, 'g.sort desc');
        return $this->response($data);
    }

    /**
     * 获取砍价中的商品列表
     * @return false|string
     */
    public function bargainingList()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $join = [
            [ 'goods g', 'g.goods_id = pbl.goods_id', 'left' ]
        ];
        $condition = [
            [ 'pbl.member_id', '=', $this->member_id ],
            [ 'pbl.status', '=', 0 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.verify_state', '=', 1 ]
        ];
        $field = 'pbl.launch_id,pbl.bargain_id,pbl.sku_id,pbl.goods_id,pbl.site_id,pbl.start_time,pbl.end_time,pbl.member_id,pbl.curr_price,pbl.price,g.goods_name,g.goods_image';
        $bargain = new BargainModel();
        $list = $bargain->getBargainLaunchList($condition, $field, 'pbl.start_time desc', 'pbl', $join);
        return $this->response($this->success($list));
    }

    /**
     * 商品详情
     */
    public function detail()
    {
        $bargain_id = isset($this->params[ 'bargain_id' ]) ? $this->params[ 'bargain_id' ] : 0;
        if (empty($bargain_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $bargain = new BargainModel();
        $condition = [
            [ 'pb.bargain_id', '=', $bargain_id ],
            [ 'pbg.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.verify_state', '=', 1 ]
        ];
        $goods_sku_detail = $bargain->getBargainGoodsDetail($condition);
        $goods_sku_detail = $goods_sku_detail[ 'data' ];

        $res[ 'goods_sku_detail' ] = $goods_sku_detail;
        if (empty($goods_sku_detail)) return $this->response($this->error($res));

        if (!empty($goods_sku_detail[ 'goods_spec_format' ])) {
            //判断商品规格项
            $goods_spec_format = $bargain->getGoodsSpecFormat($bargain_id, $goods_sku_detail[ 'goods_spec_format' ]);
            $res[ 'goods_sku_detail' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        }

        $token = $this->checkToken();
        if ($token[ 'code' ] == 0) {
            $launch_info = $bargain->getBargainLaunchDetail([
                [ 'bargain_id', '=', $goods_sku_detail[ 'bargain_id' ] ],
                [ 'sku_id', '=', $goods_sku_detail[ 'sku_id' ] ],
                [ 'member_id', '=', $this->member_id ],
                [ 'status', '=', 0 ]
            ], 'launch_id');
            if (!empty($launch_info[ 'data' ])) $res[ 'goods_sku_detail' ][ 'launch_info' ] = $launch_info[ 'data' ];
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
        $sku_id = isset($this->params[ 'sku_id' ]) ? $this->params[ 'sku_id' ] : 0;
        $bargain_id = isset($this->params[ 'bargain_id' ]) ? $this->params[ 'bargain_id' ] : 0;
        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_SKU_ID'));
        }
        if (empty($bargain_id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }
        $goods = new BargainModel();
        $condition = [
            [ 'pbg.sku_id', '=', $sku_id ],
            [ 'pbg.bargain_id', '=', $bargain_id ],
            [ 'pbg.status', '=', 1 ],
            [ 'g.goods_state', '=', 1 ],
            [ 'g.is_delete', '=', 0 ],
            [ 'g.verify_state', '=', 1 ]
        ];
        $info = $goods->getBargainGoodsDetail($condition);
        if (!empty($info[ 'data' ])) {
            $goods_spec_format = $goods->getGoodsSpecFormat($bargain_id, $info[ 'data' ][ 'goods_spec_format' ]);
            $info[ 'data' ][ 'goods_spec_format' ] = json_encode($goods_spec_format);
        } else {
            $sku_id = $goods->getGoodsSpecFormat($bargain_id, '', $sku_id);
            $info = $this->success([ 'type' => 'again', 'sku_id' => $sku_id ]);
        }
        return $this->response($info);
    }

    /**
     * 商品海报
     * @return false|string
     */
    public function poster()
    {
        if (!empty($qrcode_param)) return $this->response($this->error('', '缺少必须参数qrcode_param'));

        $promotion_type = 'bargain';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);
        $qrcode_param[ 'suid' ] = $qrcode_param[ 'suid' ] ?? 0;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type);
        return $this->response($res);
    }
}