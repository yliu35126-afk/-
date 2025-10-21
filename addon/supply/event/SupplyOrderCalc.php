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

use addon\supply\model\SupplyOrderCalc as SupplyOrderCalcModel;
use app\model\system\Stat;

/**
 * 订单支付后供应商点单计算
 */
class SupplyOrderCalc
{
    /**
     * 传入订单信息
     * @param unknown $data
     */
    public function handle($data)
    {
        $supply_order_calc = new SupplyOrderCalcModel();
        $res = $supply_order_calc->calculate($data[ 'order_id' ]);
        //添加统计
        $stat = new Stat();
        $data = [
            'site_id' => $data[ 'site_id' ],
            'order_total' => $data[ 'order_money' ] - $data[ 'adjust_money' ],
            'shipping_total' => $data[ 'delivery_money' ],
            'order_pay_count' => 1,
            'goods_pay_count' => $data[ 'goods_num' ],
        ];
        $stat->addShopStat($data);
        return $res;
    }

}