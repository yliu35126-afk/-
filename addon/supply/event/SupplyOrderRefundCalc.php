<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\supply\event;

use addon\supply\model\SupplyOrderCalc as SupplyOrderCalcModel;
/**
 * 订单项退款后后店铺订单计算
 */
class SupplyOrderRefundCalc
{
	/**
	 * 传入订单信息
	 * @param unknown $data
	 */
	public function handle($data)
	{
	    $supply_order_calc = new SupplyOrderCalcModel();
	    $res = $supply_order_calc->refundCalculate($data);
	    return $res;
	}
	
}