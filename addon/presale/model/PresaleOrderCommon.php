<?php
// +---------------------------------------------------------------------+
// | NiuCloud | [ WE CAN DO IT JUST NiuCloud ]                |
// +---------------------------------------------------------------------+
// | Copy right 2019-2029 www.niucloud.com                          |
// +---------------------------------------------------------------------+
// | Author | NiuCloud <niucloud@outlook.com>                       |
// +---------------------------------------------------------------------+
// | Repository | https://github.com/niucloud/framework.git          |
// +---------------------------------------------------------------------+

namespace addon\presale\model;

use app\model\BaseModel;

use app\model\order\Order;
use app\model\order\OrderCommon;
use app\model\system\Cron;
use app\model\order\Config;
use addon\coupon\model\Coupon;
use app\model\member\MemberAccount;
use addon\store\model\StoreGoodsSku;
use app\model\system\Pay;

/**
 * 商品预售
 */
class PresaleOrderCommon extends BaseModel
{
    // 订单待付款
    const ORDER_CREATE = 0;
    //等待付尾款
    const WAIT_FINAL_PAY = 1;
    //订单完成(尾款已支付)
    const ORDER_PAY = 2;
    // 订单已关闭
    const ORDER_CLOSE = -1;

    /**
     * 基础订单状态(不同类型的订单可以不使用这些状态，但是不能冲突)
     * @var unknown
     */
    public $order_status = [

        self::ORDER_CREATE => [
            'status' => self::ORDER_CREATE,
            'name' => '待付款',
            'is_allow_refund' => 0,
            'icon' => 'upload/uniapp/order/order-icon-send.png',
            'action' => [
                [
                    'action' => 'orderClose',
                    'title' => '关闭订单',
                    'color' => ''
                ],
//                [
//                    'action' => 'offlinePayDeposit',
//                    'title' => '线下支付定金',
//                    'color' => ''
//                ],
            ],
            'member_action' => [
                [
                    'action' => 'orderClose',
                    'title' => '关闭订单',
                    'color' => ''
                ],
                [
                    'action' => 'orderPayDeposit',
                    'title' => '支付定金',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
        self::WAIT_FINAL_PAY => [
            'status' => self::WAIT_FINAL_PAY,
            'name' => '待付尾款',
            'is_allow_refund' => 0,
            'icon' => 'upload/uniapp/order/order-icon-send.png',
            'action' => [
//                [
//                    'action' => 'offlinePayFinal',
//                    'title' => '线下支付尾款',
//                    'color' => ''
//                ],
            ],
            'member_action' => [
                [
                    'action' => 'refundDeposit',
                    'title' => '退定金',
                    'color' => ''
                ],
                [
                    'action' => 'orderPayFinal',
                    'title' => '支付尾款',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
        self::ORDER_PAY => [
            'status' => self::ORDER_PAY,
            'name' => '已完成',
            'is_allow_refund' => 0,
            'icon' => 'upload/uniapp/order/order-icon-send.png',
            'action' => [],
            'member_action' => [],
            'color' => ''
        ],
        self::ORDER_CLOSE => [
            'status' => self::ORDER_CLOSE,
            'name' => '已关闭',
            'is_allow_refund' => 0,
            'icon' => 'upload/uniapp/order/order-icon-close.png',
            'action' => [
                [
                    'action' => 'deleteOrder',
                    'title' => '删除订单',
                    'color' => ''
                ],
            ],
            'member_action' => [
                [
                    'action' => 'deleteOrder',
                    'title' => '删除订单',
                    'color' => ''
                ],
            ],
            'color' => ''
        ],
    ];

    /**
     * 订单状态
     * @return array
     */
    public function getOrderStatus()
    {
        return $this->success($this->order_status);
    }

    /*********************************************** 订单关闭 start *****************************************************************/

    /**
     *  订单关闭
     * @param array $condition
     */
    public function depositOrderClose($condition = [])
    {
        $info = model('promotion_presale_order')->getInfo($condition,'id,order_status');
        if(empty($info)){
            return $this->error('','订单不存在');
        }
        if($info['order_status'] == self::ORDER_CLOSE){
            return $this->error('','该订单已关闭');
        }
        if($info['order_status'] != self::ORDER_CREATE){
            return $this->error('','该订单已支付或已完成');
        }

        $res = $this->cronDepositOrderClose($info['id']);
        return $res;
    }

    /**
     * 添加定金订单自动关闭
     */
    public function addDepositOrderCronClose($order_id, $site_id)
    {
        //计算订单自动关闭时间
        $config_model = new Config();
        $order_config_result = $config_model->getOrderEventTimeConfig($site_id);
        $order_config = $order_config_result["data"];
        $now_time = time();
        if (!empty($order_config)) {
            $execute_time = $now_time + $order_config["value"]["auto_close"] * 60; //自动关闭时间
        } else {
            $execute_time = $now_time + 3600; //尚未配置  默认一天
        }
        $cron_model = new Cron();
        $cron_model->addCron(1, 0, "预售订单自动关闭", "CronDepositOrderClose", $execute_time, $order_id);
    }

    /**
     * 自动关闭定金订单
     * @param $order_id
     * @return array
     */
    public function cronDepositOrderClose($order_id)
    {
        $order_info = model('promotion_presale_order')->getInfo([['id','=',$order_id]]);
        if(empty($order_info)){
            return $this->error('','订单不存在');
        }
        //订单已关闭
        if($order_info['order_status'] == self::ORDER_CLOSE){
            return $this->success();
        }
        if($order_info['order_status'] != self::ORDER_CREATE){
            return $this->success();
        }
        model('promotion_presale_order')->startTrans();
        try{
            //修改订单状态
            $data = [
                'order_status' => self::ORDER_CLOSE,
                'order_status_name' => $this->order_status[self::ORDER_CLOSE]["name"],
                'order_status_action' => json_encode($this->order_status[self::ORDER_CLOSE], JSON_UNESCAPED_UNICODE),
                'close_time' => time(),
            ];
            model('promotion_presale_order')->update($data,[['id','=',$order_id]]);

            //增加库存
            model('promotion_presale')->setInc([['presale_id','=',$order_info['presale_id']]],'presale_stock',$order_info['num']);

            //增加门店库存
            if($order_info['delivery_store_id'] > 0){

                $store_data = [
                    'delivery_store_id' => $order_info['delivery_store_id'],
                    'num' => $order_info['num'],
                    'sku_id' => $order_info['sku_id']
                ];
                $store_result = $this->incStoreGoodsStock($store_data);
                if($store_result['code'] < 0){
                    return $store_result;
                }
            }

            //返还店铺优惠券
            $coupon_id = $order_info["coupon_id"];
            if ($coupon_id > 0) {
                $coupon_model = new Coupon();
                $coupon_model->refundCoupon($coupon_id, $order_info["member_id"]);
            }
            //平台优惠券

            //平台余额  退还余额
            if ($order_info["balance_deposit_money"] > 0) {
                $member_account_model = new MemberAccount();
                $member_account_model->addMemberAccount($order_info['site_id'], $order_info["member_id"], "balance", $order_info["balance_deposit_money"], "refund", "余额返还", "订单关闭,返还余额:" . $order_info["balance_deposit_money"]);
            }

            model('promotion_presale_order')->commit();
            return $this->success();
        }catch (\Exception $e){

            model('promotion_presale_order')->rollback();
            return $this->error('',$e->getMessage());
        }
    }

    /**
     * 订单关闭增加门店商品库存
     * @param $data
     * @return array
     */
    public function incStoreGoodsStock($data)
    {
        $store_goods_sku_model = new StoreGoodsSku();
        $stock_result = $store_goods_sku_model->incStock([ "store_id" => $data["delivery_store_id"], "sku_id" => $data["sku_id"], "store_stock" => $data["num"] ]);
        if ($stock_result["code"] < 0) {
            return $stock_result;
        }
    }

    /*********************************************** 订单关闭 end ******************************************************/

    /********************************************** 订单线下支付 start *************************************************/

    /**
     * 线下支付定金
     * @param $order_id
     * @param $site_id
     * @return array
     */
    public function offlinePayDeposit($order_id,$site_id)
    {
        $order_info = model("promotion_presale_order")->getInfo(
            [['id', '=', $order_id],['site_id','=',$site_id]],
            'deposit_out_trade_no,order_status'
        );
        if(empty($order_info)){
            return $this->error('','订单不存在');
        }
        if($order_info['order_status'] != self::ORDER_CREATE){
            return $this->error('','订单已经支付或已经关闭');
        }

        $pay_model = new Pay();
        $result = $pay_model->onlinePay($order_info['deposit_out_trade_no'], "OFFLINE_PAY", '', '');
        if ($result["code"] < 0) {
            return $result;
        }
    }


    /**
     * 线下支付尾款
     * @param $order_id
     * @param $site_id
     * @return array
     */
    public function offlinePayFinal($order_id,$site_id)
    {
        $order_info = model("promotion_presale_order")->getInfo(
            [['id', '=', $order_id]],
            'final_out_trade_no,order_status,is_fenxiao'
        );
        if(empty($order_info)){
            return $this->error('','订单不存在');
        }
        if($order_info['order_status'] != self::WAIT_FINAL_PAY){
            return $this->error('','订单已经支付或已经关闭');
        }

        if(empty($order_info['final_out_trade_no'])){

            $pay = new Pay();
            $out_trade_no = $pay->createOutTradeNo();
            model('promotion_presale_order')->startTrans();
            try {

                model('promotion_presale_order')->update(
                    ['final_out_trade_no' => $out_trade_no],
                    [['id', '=', $order_id]]
                );

                //将预售订单信息添加到普通订单中
                $res = $this->finalOrderOnlinePay(['out_trade_no' => $out_trade_no,'pay_type' => 'OFFLINE_PAY']);
                if($res['code'] < 0 ){
                    model('promotion_presale_order')->rollback();
                    return $res;
                }
                model('promotion_presale_order')->commit();
                return $this->success();

            } catch (\Exception $e) {
                model('promotion_presale_order')->rollback();
                return $this->error('', $e->getMessage());
            }

        }else{
            $pay_model = new Pay();
            $result = $pay_model->onlinePay($order_info['final_out_trade_no'], "OFFLINE_PAY", '', '');
            if ($result["code"] < 0) {
                return $result;
            }
        }
    }


    /********************************************** 订单线下支付 end ***************************************************/

    /*********************************************** 订单异步回调 start ************************************************/

    /**
     * 定金订单线上支付回调
     * @param $data
     * @return array
     */
    public function depositOrderOnlinePay($data)
    {
        $out_trade_no = $data["out_trade_no"];
        $order_info = model("promotion_presale_order")->getInfo([['deposit_out_trade_no', '=', $out_trade_no]]);
        if($order_info['order_status'] != self::ORDER_CREATE){
            return $this->success();
        }

        $order_model = new Order();
        $pay_type_list = $order_model->getPayType();
        model('promotion_presale_order')->startTrans();
        try {

            //获取预售活动信息
            $presale_info = model('promotion_presale')->getInfo([['presale_id','=',$order_info['presale_id']]],'deliver_type,deliver_time');
            if($presale_info['deliver_type'] == 0){
                $predict_delivery_time = $presale_info['deliver_time'];
            }else{
                $predict_delivery_time = strtotime('+'.$presale_info['deliver_time'].'days');
            }
            $order_status_action = $this->order_status[self::WAIT_FINAL_PAY];
            if($order_info['is_deposit_back']==1){
                $order_status_action['member_action'][0] = $order_status_action['member_action'][1];
                unset($order_status_action['member_action'][1]);
            }
            //修改订单状态
            $order_data = [
                'order_status' => self::WAIT_FINAL_PAY,
                'order_status_name' => $this->order_status[self::WAIT_FINAL_PAY]["name"],
                'order_status_action' => json_encode($order_status_action, JSON_UNESCAPED_UNICODE),
                'pay_deposit_time' => time(),
                'deposit_pay_type' => $data['pay_type'],
                'deposit_pay_type_name' => $pay_type_list[$data['pay_type']],
                'predict_delivery_time' => $predict_delivery_time
            ];
            model("promotion_presale_order")->update($order_data,[ ['id','=',$order_info['id']] ]);

            if($order_info['final_money'] == 0){
                $this->finalOrderOnlinePay(['out_trade_no' => $order_info['final_out_trade_no'], 'id' => $order_info['id'], 'pay_type' => $data['pay_type'] ]);
            }
            //增加销量
            model('promotion_presale')->setInc([['presale_id','=',$order_info['presale_id']]],'sale_num',$order_info['num']);

            model('promotion_presale_order')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_presale_order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 尾款订单线上支付回调
     * @param $data
     * @return array
     */
    public function finalOrderOnlinePay($data)
    {
        if($data["out_trade_no"] == '' && isset($data['id'])){
            $condition = [['id', '=', $data['id']]];
        }else{
            $condition = [['final_out_trade_no', '=', $data["out_trade_no"]]];
        }

        $order_info = model("promotion_presale_order")->getInfo($condition);
        if($order_info['order_status'] != self::WAIT_FINAL_PAY){
            return $this->success();
        }
        $order_model = new Order();
        $pay_type_list = $order_model->getPayType();
        model('promotion_presale_order')->startTrans();
        try {
            //修改预售订单状态
            $order_data = [
                'order_status' => self::ORDER_PAY,
                'order_status_name' => $this->order_status[self::ORDER_PAY]["name"],
                'order_status_action' => json_encode($this->order_status[self::ORDER_PAY], JSON_UNESCAPED_UNICODE),
                'pay_final_time' => time(),
                'final_pay_type' => $data['pay_type'],
                'final_pay_type_name' => $pay_type_list[$data['pay_type']]
            ];
            model("promotion_presale_order")->update($order_data,[ ['id','=',$order_info['id']] ]);

            //将预售订单信息添加到普通订单中
            $res = $this->presaleOrderToOrder($order_info);
            if($res['code'] < 0 ){
                model('promotion_presale_order')->rollback();
                return $res;
            }

            model('promotion_presale_order')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('promotion_presale_order')->rollback();
            return $this->error('', $e->getMessage());
        }
    }


    /**
     * 将预售订单信息添加到普通订单中
     * @param $presale_order
     * @return array
     */
    public function presaleOrderToOrder($presale_order)
    {
        model('order')->startTrans();
        try{
            //添加订单记录
            $order_data = [
                'order_no' => $presale_order['order_no'],
                'site_id' => $presale_order['site_id'],
                'site_name' => $presale_order['site_name'],
                'order_name' => $presale_order['sku_name'],
                'order_from' => $presale_order['order_from'],
                'order_from_name' => $presale_order['order_from_name'],
                'order_type' => $presale_order['order_type'],
                'order_type_name' => $presale_order['order_type_name'],
                'out_trade_no' => $presale_order['deposit_out_trade_no'],
                'out_trade_no_2' => $presale_order['final_out_trade_no'],
                'pay_type' => $presale_order['deposit_pay_type'],
                'pay_type_name' => $presale_order['deposit_pay_type_name'],
                'delivery_type' => $presale_order['delivery_type'],
                'delivery_type_name' => $presale_order['delivery_type_name'],
                'member_id' => $presale_order['member_id'],
                'name' => $presale_order['name'],
                'mobile' => $presale_order['mobile'],
                'telephone' => $presale_order['telephone'],
                'province_id' => $presale_order['province_id'],
                'city_id' => $presale_order['city_id'],
                'district_id' => $presale_order['district_id'],
                'community_id' => $presale_order['community_id'],
                'address' => $presale_order['address'],
                'full_address' => $presale_order['full_address'],
                'longitude' => $presale_order['longitude'],
                'latitude' => $presale_order['latitude'],
                'buyer_ip' => $presale_order['buyer_ip'],
                'buyer_ask_delivery_time' => $presale_order['buyer_ask_delivery_time'],
                'buyer_message' => $presale_order['buyer_message'],
                'goods_money' => $presale_order['goods_money'],
                'delivery_money' => $presale_order['delivery_money'],
                'promotion_money' => $presale_order['promotion_money'],
                'coupon_id' => $presale_order['coupon_id'],
                'coupon_money' => $presale_order['coupon_money'],
                'invoice_money' => $presale_order['invoice_money'],
                'order_money' => $presale_order['order_money'],
                'balance_money' => $presale_order['balance_deposit_money'] + $presale_order['balance_final_money'],
                'pay_money' => $presale_order['pay_deposit_money'] + $presale_order['pay_final_money'],//现金支付金额
                'create_time' => $presale_order['create_time'],
                'pay_time' => time(),
                'goods_num' => $presale_order['num'],
                'delivery_store_id' => $presale_order['delivery_store_id'],
                'delivery_store_name' => $presale_order['delivery_store_name'],
                'delivery_store_info' => $presale_order['delivery_store_info'],
                'promotion_type' => 'presale',
                'promotion_type_name' => '商品预售',
                'promotion_status_name' => '已完成',
                'is_invoice' => $presale_order['is_invoice'],
                'invoice_type' => $presale_order['invoice_type'],
                'invoice_title' => $presale_order['invoice_title'],
                'taxpayer_number' => $presale_order['taxpayer_number'],
                'invoice_rate' => $presale_order['invoice_rate'],
                'invoice_content' => $presale_order['invoice_content'],
                'invoice_delivery_money' => $presale_order['invoice_delivery_money'],
                'invoice_full_address' => $presale_order['invoice_full_address'],
                'is_tax_invoice' => $presale_order['is_tax_invoice'],
                'invoice_email' => $presale_order['invoice_email'],
                'invoice_title_type' => $presale_order['invoice_title_type'],
                'is_fenxiao' => $presale_order['is_fenxiao'],
                'predict_delivery_time' => $presale_order['predict_delivery_time']//预计发货时间
            ];
            $order_id = model('order')->add($order_data);

            //添加订单商品项
            $order_goods_data = [
                'order_id' => $order_id,
                'order_no' => $presale_order['order_no'],
                'site_id' => $presale_order['site_id'],
                'member_id' => $presale_order['member_id'],
                'goods_id' => $presale_order['goods_id'],
                'sku_id' => $presale_order['sku_id'],
                'sku_name' => $presale_order['sku_name'],
                'sku_image' => $presale_order['sku_image'],
                'sku_no' => $presale_order['sku_no'],
                'is_virtual' => $presale_order['is_virtual'],
                'goods_class' => $presale_order['goods_class'],
                'goods_class_name' => $presale_order['goods_class_name'],
                'price' => $presale_order['price'],
                'cost_price' => $presale_order['cost_price'],
                'num' => $presale_order['num'],
                'goods_money' => $presale_order['goods_money'],
                'cost_money' => $presale_order['cost_price'] * $presale_order['num'],
                'real_goods_money' => $presale_order['goods_money'] - ($presale_order['presale_money'] - $presale_order['presale_deposit_money']),
                'promotion_money' => $presale_order['promotion_money'],
                'coupon_money' => $presale_order['coupon_money'],
                'goods_name' => $presale_order['goods_name'],
                'sku_spec_format' => $presale_order['sku_spec_format'],
                'is_fenxiao' => $presale_order['is_fenxiao'],
                'delivery_status_name' =>'未发货',
                'delivery_status' => 0
            ];
            model('order_goods')->add($order_goods_data);
            model('promotion_presale_order')->update(['relate_order_id'=>$order_id],[['id','=',$presale_order['id']]]);
            //调用线上支付
            $order_model = new OrderCommon();
            $result = $order_model->orderOnlinePay( ['out_trade_no' => $presale_order['deposit_out_trade_no'], 'pay_type' => $presale_order['deposit_pay_type'] ]);
            if($result['code'] < 0){
                model('order')->rollback();
                return $result;
            }
            model('order')->commit();
            return $this->success();
        }catch (\Exception $e){

            model('order')->rollback();
            return $this->error('',$e->getMessage());
        }

    }

    /*********************************************** 订单异步回调 end *****************************************************************/

    /**
     * 订单删除
     * @param $condition
     * @return array
     */
    public function deleteOrder($condition)
    {
        $info = model('promotion_presale_order')->getInfo($condition,'order_status');
        if(empty($info)){
            return $this->error('','订单不存在');
        }
        if($info['order_status'] != self::ORDER_CLOSE){
            return $this->error('','抱歉，只有已关闭的订单才可以删除');
        }

        $res = model('promotion_presale_order')->delete($condition);
        if ($res) {
            return $this->success($res);
        } else {
            return $this->error();
        }
    }

    /**
     * 获取定金/尾款支付流水号
     * @param $id
     * @param $member_id
     * @param $site_id
     * @return array
     */
    public function getPresaleOrderOutTradeNo($id,$member_id)
    {
        //订单信息
        $order_info = model('promotion_presale_order')->getInfo(
            [
                ['id','=',$id],
                ['member_id','=',$member_id]
            ],
            'order_status,deposit_out_trade_no,final_out_trade_no'
        );

        if(empty($order_info)){
            return $this->error('','订单不存在');
        }
        //未支付定金
        if($order_info['order_status'] == self::ORDER_CREATE){
            return $this->success($order_info['deposit_out_trade_no']);
        }

        if($order_info['order_status'] == self::WAIT_FINAL_PAY){

            return $this->success($order_info['final_out_trade_no']);
        }

        return $this->error('','请核实数据后重试');
    }
}