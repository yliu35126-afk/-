<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\shopcomponent\event;

use addon\shopcomponent\model\Order;

/**
 * 订单发货完成
 */
class OrderDelivery
{

    public function handle($data)
    {
        $res = (new Order())->delivery($data['order_id']);
        return $res;
    }
}