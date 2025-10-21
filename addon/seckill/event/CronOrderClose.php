<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\seckill\event;

use addon\seckill\model\SeckillOrderCreate;
/**
 * 订单营销活动类型
 */
class CronOrderClose
{

    /**
     * 订单关闭
     * @return multitype:number unknown
     */
    public function handle($params)
    {
        $seckill = new SeckillOrderCreate();
        $res      = $seckill->CronOrderClose($params['order_id']);
        return $res;
    }
}