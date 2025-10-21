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
use app\model\order\OrderRefund;

/**
 * 定时通过维权
 * @author Administrator
 *
 */
class CronOrderRefundConfirm
{
    public function handle($param)
    {
        $order_model = new OrderCommon();
        $condition = array (
            [ 'order_goods_id', '=', $param[ 'relate_id' ] ]
        );
        $order_goods_info = $order_model->getOrderGoodsInfo($condition)[ 'data' ] ?? [];
        $order_refund_model = new OrderRefund();
        $data = array (
            'order_goods_id' => $order_goods_info[ 'order_goods_id' ],
            'site_id' => $order_goods_info[ 'site_id' ]
        );
        $result = $order_refund_model->orderRefundConfirm($data);
        return $result;
    }
}
