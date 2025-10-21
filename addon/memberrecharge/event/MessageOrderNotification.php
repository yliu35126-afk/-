<?php


namespace addon\memberrecharge\event;


use addon\memberrecharge\model\OrderNotification;

/**
 * 会员充值推送
 */
class MessageOrderNotification
{
    /**
     * @param $param
     */
    public function handle($param)
    {
        // 发送订单消息

        if($param["keywords"] == "RECHARGE_SUCCESS"){
            //发送订单消息
            $model = new OrderNotification();
            return $model->messageOrderNotification($param);
        }
    }
}