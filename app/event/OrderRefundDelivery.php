<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace app\event;

use app\model\order\Config;
use app\model\system\Cron;

/**
 * 订单维权 会员退货发货后事件
 */
class OrderRefundDelivery
{
	// 行为扩展的执行入口必须是run
	public function handle($data)
	{
        //todo 自动通过维权  卖家收货时间
        $order_config_model = new Config();
        $config = $order_config_model->getOrderEventTimeConfig()['data']['value'] ?? [];
        $auto_refund_take_delivery = $config['auto_refund_take_delivery'] ?? 0;
        $cron_model = new Cron();
        $now_time = time();//当前时间
        $execute_time = $now_time + $auto_refund_take_delivery * 86400;//自动收货时间
        $result = $cron_model->addCron(1, 1, "维权自动通过卖家收货", "CronOrderRefundTakeDelivery", $execute_time, $data['order_goods_id']);
        return $result;

    }
	
}