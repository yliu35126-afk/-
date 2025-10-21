<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace app\event;

use app\model\order\OrderCommon;

/**
 * 创建订单后添加订单日志
 */
class CreateOrderLog
{
    // 行为扩展的执行入口必须是run
    public function handle($params)
    {
        if (isset($params['order_id']) && $params['order_id'] > 0) {

            $order_model = new OrderCommon();
            //获取订单信息
            $order_info = $order_model->getOrderInfo([['order_id', '=', $params['order_id']]], 'member_id,name,order_status,order_status_name');
            $order_info = $order_info['data'];
            if (!empty($order_info)) {

                $order_log = [
                    'uid' => $order_info['member_id'] ?? 0,
                    'nick_name' => $order_info['name'] ?? '',
                    'action' => '订单创建',
                    'order_status' => $order_info['order_status'],
                    'order_status_name' => $order_info['order_status_name'],
                    'module' => 'member',
                    'action_way' => 'create',
                    'order_id' => $params['order_id']
                ];
                $res = $order_model->addOrderLog($order_log);
                return $res;
            }
        }

    }
}