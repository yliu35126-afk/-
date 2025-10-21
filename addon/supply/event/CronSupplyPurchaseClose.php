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

use addon\supply\model\Purchase;

/**
 * 求购单自动关闭
 */
class CronSupplyPurchaseClose
{
	// 行为扩展的执行入口必须是run
	public function handle($data)
	{
        $purchase = new Purchase();
        $result = $purchase->closePurchase([["purchase_id", "=", $data["relate_id"]]]);
        return $result;
	}
	
}