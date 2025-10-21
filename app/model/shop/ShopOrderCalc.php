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

namespace app\model\shop;

use app\model\BaseModel;

/**
 * 订单计算与结算
 */
class ShopOrderCalc extends BaseModel
{
    /**
     * 店铺订单计算(支付完成后计算)
     * @param unknown $order_id
     */
    public function calculate($order_id)
    {
        $order_info = model("order")->getInfo([ [ 'order_id', '=', $order_id ] ], 'promotion_money, coupon_money, goods_money, delivery_money, adjust_money');
        $order_goods_list = model("order_goods")->getList([ [ 'order_id', '=', $order_id ] ], 'order_goods_id, goods_money, commission_rate,real_goods_money');
        $shop_money_total = 0;
        $platform_money_total = 0;

        foreach ($order_goods_list as $k => $v) {
            //实际总商品金额
            $goods_money = $v[ 'real_goods_money' ];
            $platform_money = round(floor($goods_money * $v[ 'commission_rate' ]) / 100, 2);
            $shop_money = round(floor(( $goods_money - $platform_money ) * 100) / 100, 2);

            $data = [
                'shop_money' => $shop_money,
                'platform_money' => $platform_money
            ];
            model("order_goods")->update($data, [ [ 'order_goods_id', '=', $v[ 'order_goods_id' ] ] ]);
            $shop_money_total += $shop_money;
            $platform_money_total += $platform_money;
        }
        //todo  将订单发票的金额加入  店铺获取结算佣金字段中  平台优惠券金额(平台优惠券  平台承担部分 需要在结算的时候,讲平台承担的金额部分累加到店铺)
        model("order")->update([ 'shop_money' => $shop_money_total + $order_info[ 'delivery_money' ], 'platform_money' => $platform_money_total ], [ [ 'order_id', '=', $order_id ] ]);
        return $this->success();
    }

    /**
     * 订单退款金额累加计算
     * @param $order_money
     */
    public function refundCalculate($order_goods_info)
    {
        $order_id = $order_goods_info[ "order_id" ];
        $order_goods_id = $order_goods_info[ "order_goods_id" ];
        //订单项信息
        $order_goods_info = model("order_goods")->getInfo([ [ 'order_goods_id', '=', $order_goods_id ] ], 'refund_real_money, platform_money,shop_money,commission_rate,platform_coupon_money');
        if (empty($order_goods_info))
            return $this->error([], "ORDER_GOODS_EMPTY");

        //订单信息
        $order_info = model("order")->getInfo([ [ 'order_id', '=', $order_id ] ], 'shop_money, platform_money, refund_money, refund_shop_money, refund_platform_money, commission, refund_platform_coupon_money, refund_platform_coupon_total_money, refund_platform_coupon_shop_money, platform_coupon_total_money,platform_coupon_money, platform_coupon_shop_money');
        if (empty($order_info))
            return $this->error([], "ORDER_EMPTY");

        $refund_money = $order_info[ "refund_money" ];//订单总退款
        $refund_shop_money = $order_info[ "refund_shop_money" ];//订单退款 店铺金额
        $refund_platform_money = $order_info[ "refund_platform_money" ];//订单退款  平台金额

        $item_refund_money = $order_goods_info[ "refund_real_money" ];
        $item_refund_platform_money = $order_goods_info[ "platform_money" ];//订单项平台退款金额
        $item_refund_shop_money = $item_refund_money - $item_refund_platform_money;//订单项平台退款金额

        $refund_shop_money += $item_refund_shop_money;
        $refund_platform_money += $item_refund_platform_money;
        $refund_money += $item_refund_money;

        $order_data = array (
            "refund_money" => $refund_money,
            "refund_shop_money" => $refund_shop_money,
            "refund_platform_money" => $refund_platform_money
        );

        if($order_info['platform_coupon_total_money'] > 0){
            //平台优惠券金额, 收到退款的影响,累加平台优惠券的退款金额
            $platform_coupon_total_money = $order_info[ "platform_coupon_total_money" ];
            $refund_platform_coupon_total_money = $order_info[ "refund_platform_coupon_total_money" ];//订单总退款(平台优惠券金额部分)
            $refund_platform_coupon_shop_money = $order_info[ "refund_platform_coupon_shop_money" ];//订单退款 店铺金额(平台优惠券金额部分)
            $refund_platform_coupon_money = $order_info[ "refund_platform_coupon_money" ];//订单退款  平台金额(平台优惠券金额部分)
            //逆推 平台优惠券订单项承担平台优惠券部分比率
            $item_refund_platform_coupon_total_money = $order_goods_info[ "platform_coupon_money" ];
            $platform_coupon_rate = round(floor($order_info['platform_coupon_shop_money'] / $order_info['platform_coupon_total_money'] * 100) / 100, 2);

            $item_refund_platform_coupon_shop_money =  round(floor($item_refund_platform_coupon_total_money * $platform_coupon_rate * 100) / 100, 2);//订单项平台退款金额
            $item_refund_platform_coupon_money = $item_refund_platform_coupon_total_money - $item_refund_platform_coupon_shop_money;//订单项平台退款金额

            $refund_platform_coupon_total_money += $item_refund_platform_coupon_total_money;//订单总退款(平台优惠券金额部分)
            $refund_platform_coupon_shop_money += $item_refund_platform_coupon_shop_money;//订单退款 店铺金额(平台优惠券金额部分)
            $refund_platform_coupon_money += $item_refund_platform_coupon_money;//订单退款  平台金额(平台优惠券金额部分)

            $order_data['refund_platform_coupon_total_money'] = $refund_platform_coupon_total_money;
            $order_data['refund_platform_coupon_shop_money'] = $refund_platform_coupon_shop_money;
            $order_data['refund_platform_coupon_money'] = $refund_platform_coupon_money;
        }
        $result = model("order")->update($order_data, [ [ 'order_id', '=', $order_id ] ]);
        return $this->success();

    }

    /**
     * 整体计算订单
     * @param unknown $out_trade_no
     * @return multitype:string
     */
    public function orderCalculate($out_trade_no)
    {
        $order_list = model("order")->getList([ [ 'out_trade_no', '=', $out_trade_no ] ], 'order_id');
        foreach ($order_list as $k => $v) {
            $this->calculate($v[ 'order_id' ]);
        }
        return $this->success();
    }
}