<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\pointexchange\event;

use addon\pointexchange\model\Order;
/**
 * 积分兑换订单关闭
 */
class CronExchangeOrderClose
{
    
	// 行为扩展的执行入口必须是run
	public function handle($data)
	{
        $order = new Order();
        $condition = array(
            ['order_id', '=', $data['relate_id']]
        );
        $result = $order->closeOrder($condition);
        return $result;
	}
	
}