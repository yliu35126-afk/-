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
 * 订单申请维权
 */
class OrderRefundApply
{

    public function handle($data)
    {
        $res = (new Order())->refundApply($data);
        return $res;
    }
}