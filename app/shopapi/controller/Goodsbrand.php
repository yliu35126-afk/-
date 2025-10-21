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

namespace app\shopapi\controller;

use app\model\goods\GoodsBrand as GoodsBrandModel;

/**
 * 商品品牌
 * Class Goodsbrand
 * @package app\shopapi\controller
 */
class Goodsbrand extends BaseApi
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
    }

    /**
     * 获取品牌
     * @return false|string
     */
    public function getBrandList()
    {
        $goods_brand_model = new GoodsBrandModel();
        $brand_list = $goods_brand_model->getBrandList([ [ 'site_id', 'in', ( "0,$this->site_id" ) ] ], "brand_id, brand_name");
        return $this->response($brand_list);
    }


}
