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

use addon\supply\model\order\OrderCommon;
/**
 * 订单自动收货
 */
class CronSupplyOrderTakeDelivery
{
	// 行为扩展的执行入口必须是run
	public function handle($data)
	{

        $order = new OrderCommon();
        $order_info_result = $order->getOrderInfo([["order_id", "=", $data["relate_id"]]], "order_status");
        if(!empty($order_info_result) && $order_info_result["data"]["order_status"] != 10) {

            $result = $order->orderCommonTakeDelivery($data["relate_id"]);//订单自动收货
            return $result;
        }
	}
	
}