<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 杭州牛之云科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\shopcomponent\event;

use app\model\system\Pay as PayCommon;

/**
 * 视频号异步回调
 */
class shopcomponentNotify
{

    public function handle($params)
    {
        if (!empty($params)) {
            if (isset($params[ 'order_info' ])) {
                $pay_common = new PayCommon();
                $order_info = model("order")->getInfo([ [ 'order_id', '=', $params[ 'order_info' ][ 'out_order_id' ] ] ]);
                $pay_common->onlinePay($order_info[ 'out_trade_no' ], "wechatpay", $params[ 'order_info' ][ "transaction_id" ], "wechatpay");
            }
        }
    }
}