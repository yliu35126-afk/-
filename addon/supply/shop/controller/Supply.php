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

use addon\supply\model\Supplier;

/**
 * 供应商信息
 * Class Order
 * @package app\shop\controller
 */
class Supply extends BaseSupplyshop
{

    /**
     * 供应商专页
     */
    public function index()
    {
        $supply_id = input('supply_id', 0);
        //供应商信息
        $supply_model = new Supplier();
        $supply_info = $supply_model->getSupplierInfo([ [ 'supplier_site_id', '=', $supply_id ] ], '*');
        $this->assign('detail', $supply_info[ 'data' ] ?? []);
        return $this->fetch("supply/index", [], $this->replace);
    }


}