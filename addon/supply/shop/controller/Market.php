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

namespace addon\supply\shop\controller;

use addon\supply\model\web\Adv as AdvModel;
use addon\supply\model\web\AdvPosition as AdvPositionModel;
use app\model\goods\GoodsCategory;

/**
 * 市场
 */
class Market extends BaseSupplyshop
{
    /**
     * 市场选货
     */
    public function index()
    {
        //商品分类
        $goods_category_model = new GoodsCategory();
        $condition = [
            [ 'is_show', '=', 1 ],
            [ 'level', '<=', 3 ]
        ];
        $field = "category_id,category_name,short_name,pid,level,image,category_id_1,category_id_2,category_id_3,image_adv,sort";
        $order = "sort desc,category_id desc";
        $list = $goods_category_model->getCategoryTree($condition, $field, $order);


        $this->assign('category_list', $list[ 'data' ] ?? []);

        //广告图
        $this->adv('NS_SUPPLY_SHOP_INDEX');
        return $this->fetch("market/index", [], $this->replace);
    }

    /**
     * 广告位管理
     */
    public function adv($keyword)
    {
        $adv_position_model = new AdvPositionModel();
        $adv_model = new AdvModel();
        $info = $adv_position_model->getAdvPositionInfo([ [ 'keyword', '=', $keyword ] ]);
        $info = $info[ 'data' ];
        $res = [
            'adv_position' => $info
        ];
        $list = $adv_model->getAdvList(
            [ [ 'ap_id', '=', $info[ 'ap_id' ] ] ],
            $field = 'adv_id, adv_title, ap_id, adv_url, adv_image, slide_sort, background'
        );
        $list = $list[ 'data' ];
        $res[ 'adv_list' ] = $list;

        $this->assign('adv', $res);
    }
}
