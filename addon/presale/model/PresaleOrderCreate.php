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

namespace addon\presale\model;

use addon\manjian\model\Manjian;
use app\model\goods\Goods;
use app\model\order\OrderCreate;
use app\model\store\Store;
use think\facade\Cache;
use app\model\express\Express;
use app\model\system\Pay;
use app\model\express\Config as ExpressConfig;
use app\model\order\Config;
use app\model\express\Local;
use addon\coupon\model\Coupon;
use addon\store\model\StoreGoodsSku;
use addon\store\model\StoreMember as StoreMemberModel;
use think\facade\Db;

/**
 * 订单创建(商品预售)
 *
 * @author Administrator
 *
 */
class PresaleOrderCreate extends OrderCreate
{

    public $goods_money = 0;//商品金额
    public $balance_money = 0;//余额
    public $delivery_money = 0;//配送费用
    public $coupon_money = 0;//优惠券金额
    public $adjust_money = 0;//调整金额
    public $invoice_money = 0;//发票费用
    public $promotion_money = 0;//优惠金额
    public $order_money = 0;//订金金额
    public $pay_money = 0;//支付总价
    public $final_money = 0;//尾款金额
    public $is_virtual = 0;  //是否是虚拟类订单
    public $order_name = '';  //订单详情
    public $goods_num = 0;  //商品种数
    public $member_balance_money = 0;//会员账户余额(计算过程中会逐次减少)
    public $pay_type = 'ONLINE_PAY';//支付方式
    public $invoice_delivery_money = 0;
    public $error = 0;  //是否有错误
    public $error_msg = '';  //错误描述


    /************************************************** 定金支付 start *********************************************************************/

    /**
     * 订单创建
     * @param unknown $data
     */
    public function create($data)
    {
        //查询出会员相关信息
        $calculate_data = $this->depositCalculate($data);
        if (isset($calculate_data['code']) && $calculate_data['code'] < 0)
            return $calculate_data;

        if ($this->error > 0) {
            return $this->error(['error_code' => $this->error], $this->error_msg);
        }
        $pay = new Pay();
        $out_trade_no = $pay->createOutTradeNo();

        $presale_common_order = new PresaleOrderCommon();
        $order_status_data = $presale_common_order->getOrderStatus();
        $order_status = $order_status_data['data'];

        model("promotion_presale_order")->startTrans();
        //循环生成多个订单
        try {

            $order_item = $calculate_data['shop_goods_list']; //订单数据主体
            $item_delivery = $order_item['delivery'] ?? [];
            $delivery_type = $item_delivery['delivery_type'] ?? '';

            $delivery_type_name = Express::express_type[$delivery_type]["title"] ?? '';
            $presale_info = $calculate_data['presale_info'];
            $promotion_presale_info = $calculate_data['promotion_presale_info'];;
            //订单主表
            $order_no = $this->createOrderNo($data['site_id'],$data['member_id']);
            $order_type = $this->orderType($order_item, $calculate_data);

            $sku_info = $order_item['goods_list'][0];
            $data_order = [
                'site_id' => $data['site_id'],
                'site_name' => $data['site_name'],
                'presale_id' => $data['presale_id'],
                'order_no' => $order_no,
                'deposit_out_trade_no' => $out_trade_no,
                'order_from' => $data['order_from'],
                'order_from_name' => $data['order_from_name'],
                'order_type' => $order_type['order_type_id'],
                'order_type_name' => $order_type['order_type_name'],
                'order_status_name' => $order_status[$presale_common_order::ORDER_CREATE]['name'],
                'order_status_action' => json_encode($order_status[$presale_common_order::ORDER_CREATE], JSON_UNESCAPED_UNICODE),
                'pay_start_time' => $presale_info['pay_start_time'],
                'pay_end_time' => $presale_info['pay_end_time'],
                'is_fenxiao' => $presale_info['is_fenxiao'],
                'goods_id' => $presale_info['goods_id'],
                'goods_name' => $presale_info['goods_name'],

                'sku_id' => $data['sku_id'],
                'sku_name' => $sku_info['sku_name'],
                'sku_image' => $sku_info['sku_image'],
                'sku_no' => $sku_info['sku_no'],
                'is_virtual' => $sku_info['is_virtual'],
                'goods_class' => $sku_info['goods_class'],
                'goods_class_name' => $sku_info['goods_class_name'],
                'cost_price' => $sku_info['cost_price'],
                'sku_spec_format' => $sku_info['sku_spec_format'],

                'member_id' => $data['member_id'],
                'name' => $calculate_data['member_address']['name'] ?? '',
                'mobile' => $calculate_data['member_address']['mobile'] ?? '',
                'telephone' => $calculate_data['member_address']['telephone'] ?? '',
                'province_id' => $calculate_data['member_address']['province_id'] ?? '',
                'city_id' => $calculate_data['member_address']['city_id'] ?? '',
                'district_id' => $calculate_data['member_address']['district_id'] ?? '',
                'community_id' => $calculate_data['member_address']['community_id'] ?? '',
                'address' => $calculate_data['member_address']['address'] ?? '',
                'full_address' => $calculate_data['member_address']['full_address'] ?? '',
                'longitude' => $calculate_data['member_address']['longitude'] ?? '',
                'latitude' => $calculate_data['member_address']['latitude'] ?? '',
                'buyer_ip' => request()->ip(),
                'buyer_ask_delivery_time' => $order_item['buyer_ask_delivery_time'] ?? '',//定时达
                "buyer_message" => $order_item["buyer_message"],

                'num' => $data['num'],
                'presale_deposit' => $presale_info['presale_deposit'],//定金单价
                'presale_deposit_money' => $calculate_data['presale_deposit_money'],//定金总额
                'presale_price' => $presale_info['presale_price'],//抵扣单价
                'presale_money' => $calculate_data['presale_money'],//抵扣总额

                'price' => $sku_info['price'],
                'goods_money' => $order_item['goods_money'],
                'balance_deposit_money' => $calculate_data['balance_money'],
                'pay_deposit_money' => $calculate_data['pay_money'],
                'delivery_money' => $calculate_data['delivery_money'],
                'promotion_money' => $calculate_data['promotion_money'],
                'coupon_id' => $order_item['coupon_id'] ?? 0,
                'coupon_money' => $calculate_data['coupon_money'] ?? 0,
                'invoice_money' => $calculate_data['invoice_money'],
                'final_money' => $calculate_data['final_money'],
                'order_money' => $calculate_data['order_money'],
                'delivery_type' => $delivery_type,
                'delivery_type_name' => $delivery_type_name,
                'delivery_store_id' => $order_item["delivery_store_id"] ?? 0,
                "delivery_store_name" => $order_item["delivery_store_name"] ?? '',
                "delivery_store_info" => $order_item["delivery_store_info"] ?? '',


                "is_invoice" => $data["is_invoice"] ?? 0,
                "invoice_type" => $data["invoice_type"] ?? 0,
                "invoice_title" => $data["invoice_title"] ?? '',
                "taxpayer_number" => $data["taxpayer_number"] ?? '',
                "invoice_rate" => $data["invoice_rate"] ?? 0,
                "invoice_content" => $data["invoice_content"] ?? '',
                "invoice_delivery_money" => $data["invoice_delivery_money"] ?? 0,
                "invoice_full_address" => $data["invoice_full_address"] ?? '',
                'is_tax_invoice' => $data["is_tax_invoice"] ?? '',
                'invoice_email' => $data["invoice_email"] ?? '',
                'invoice_title_type' => $data["invoice_title_type"] ?? 0,
                'order_status' => '0',
                'create_time' => time(),

                'is_deposit_back' =>$promotion_presale_info['is_deposit_back'],
                'deposit_agreement' =>$promotion_presale_info['deposit_agreement'],
            ];
            $order_id = model("promotion_presale_order")->add($data_order);
            //减少库存
            $stock_result = $this->decStock(["site_id" => $data['site_id'], "num" => $data['num'], 'presale_id' => $data['presale_id'] , 'sku_id' => $data['sku_id']]);
            if ($stock_result["code"] != 0) {
                model("promotion_presale_order")->rollback();
                return $stock_result;
            }

            //todo  满减送
            if (!empty($order_item['manjian_rule_list'])) {
                $mansong_data = [];
                foreach ($order_item['manjian_rule_list'] as $item) {
                    // 检测是否有赠送内容
                    if (isset($item['rule']['point']) || isset($item['rule']['coupon'])) {
                        array_push($mansong_data, [
                            'manjian_id' => $item['manjian_info']['manjian_id'],
                            'site_id' => $order_item['site_id'],
                            'manjian_name' => $item['manjian_info']['manjian_name'],
                            'point' => isset($item['rule']['point']) ? number_format($item['rule']['point']) : 0,
                            'coupon' => $item['rule']['coupon'] ?? 0,
                            'order_id' => $order_id,
                            'member_id' => $data['member_id'],
                            'order_sku_ids' => !empty($item['sku_ids']) ? implode($item['sku_ids']) : '',
                        ]);
                    }
                }
                if (!empty($mansong_data)) {
                    model('promotion_mansong_record')->addList($mansong_data);
                }
            }

            //优惠券
            if ($data_order['coupon_id'] > 0 && $data_order['coupon_money'] > 0) {
                //优惠券处理方案
                $member_coupon_model = new Coupon();
                $coupon_use_result = $member_coupon_model->useCoupon($data_order['coupon_id'], $data['member_id'], $order_id); //使用优惠券
                if ($coupon_use_result['code'] < 0) {
                    model("promotion_presale_order")->rollback();
                    return $this->error('', "COUPON_ERROR");
                }
            }

            //扣除余额(统一扣除)
            if ($calculate_data["balance_money"] > 0) {
                $calculate_data['order_id'] = $order_id;
                $this->pay_type = "BALANCE";
                $balance_result = $this->useBalance($calculate_data, $this);
                if ($balance_result["code"] < 0) {
                    model("promotion_presale_order")->rollback();
                    return $balance_result;
                }
            }

            //添加门店关注记录和减少门店商品库存
            $result_list = $this->addStoreMemberAndDecStock($data_order);
            if ($result_list['code'] < 0) {
                model("promotion_presale_order")->rollback();
                return $result_list;
            }

            //生成整体支付单据
            $pay->addPay($order_item['site_id'], $out_trade_no, $this->pay_type, $this->order_name, $this->order_name, $this->pay_money, '', 'DepositOrderPayNotify', '');
            //增加关闭订单自动事件
            $presale_order_model = new PresaleOrderCommon();
            $presale_order_model->addDepositOrderCronClose($order_id, $data['site_id']);

            Cache::tag("order_create_presale_" . $data['member_id'])->clear();
            model("promotion_presale_order")->commit();
            return $this->success($out_trade_no);

        } catch (\Exception $e) {
            model("promotion_presale_order")->rollback();
            return $this->error('', $e->getMessage() . $e->getFile() . $e->getLine());
          // return $this->error('', $e->getMessage());
        }

    }

    /**
     * 订单计算（定金）
     * @param unknown $data
     */
    public function depositCalculate($data)
    {
        $data = $this->initMemberAddress($data); //初始化地址
        $data = $this->initMemberAccount($data);//初始化会员账户

        //余额付款
        if ($data['is_balance'] > 0) {
            $this->member_balance_money = $data["member_account"]["balance_total"] ?? 0;
        }

        //查询预售活动信息
        $promotion_presale_info = model('promotion_presale')->getInfo([['presale_id', '=', $data["presale_id"]]]);

        if(empty($promotion_presale_info)){
            $this->error = 1;
            $this->error_msg = "预售活动不存在!";
        }
        $data['promotion_presale_info'] = $promotion_presale_info;
        //查询预售信息
        $join = [
            ['promotion_presale pp', 'pp.presale_id = ppg.presale_id', 'inner'],
            ['goods g','g.goods_id = ppg.goods_id','inner']
        ];
        $condition = [
            ['ppg.presale_id', '=', $data["presale_id"]],
            ['ppg.sku_id', '=', $data['sku_id']],
            ['g.goods_state', '=', 1],
            ['g.is_delete', '=', 0]
        ];
        $field = 'pp.*,ppg.sku_id,ppg.presale_stock,ppg.presale_deposit,ppg.presale_price,g.goods_name,g.site_name';
        $presale_info = model('promotion_presale_goods')->getInfo($condition,$field,'ppg',$join);

        if(empty($presale_info)){
            $this->error = 1;
            $this->error_msg = "商品不存在!";
        }
        //判断活动是否过期或开启
        if ($presale_info["status"] != 1) {
            $this->error = 1;
            $this->error_msg = "当前商品预售活动未开启或已过期!";
        }
        //判断购买数是否超过限购
        if ($presale_info["presale_num"] < $data['num'] && $presale_info["presale_num"] > 0) {
            $this->error = 1;
            $this->error_msg = "该商品限制购买不能大于" . $presale_info["presale_num"] . "件!";
        }

        //判断是否已存在订单
        $presale_order_count = model('promotion_presale_order')->getCount(
            [
                ['member_id', '=', $data['member_id']],
                ['presale_id', '=', $data['presale_id']],
                ['order_status', '>=', 0],
                ['refund_status', '=', 0]
            ]);
        if ($presale_order_count > 0) {
            $this->error = 1;
            $this->error_msg = "预售期间，同一商品只可购买一次!";
        }

        $data["presale_info"] = $presale_info;

        //商品列表信息
        $shop_goods_list = $this->getOrderGoodsCalculate($data);
        $data['shop_goods_list'] = $shop_goods_list;

        $data['shop_goods_list'] = $this->shopOrderCalculate($shop_goods_list, $data);

        //定金金额
        $presale_deposit_money = $presale_info['presale_deposit'] * $data['num'];
        //余额抵扣(判断是否使用余额)
        if ($this->member_balance_money > 0) {
            if ($presale_deposit_money <= $this->member_balance_money) {
                $balance_money = $presale_deposit_money;
            } else {
                $balance_money = $this->member_balance_money;
            }
        } else {
            $balance_money = 0;
        }
        $pay_money = $presale_deposit_money - $balance_money;//计算出实际支付金额
        $this->pay_money += $pay_money;

        $this->member_balance_money -= $balance_money;//预减少账户余额
        $this->balance_money += $balance_money;//累计余额

        $order_money = $this->final_money + $presale_deposit_money;

        //总结计算
        $data['delivery_money'] = $this->delivery_money;
        $data['coupon_money'] = $this->coupon_money;
        $data['adjust_money'] = $this->adjust_money;
        $data['invoice_money'] = $this->invoice_money;
        $data['invoice_delivery_money'] = $this->invoice_delivery_money;
        $data['promotion_money'] = $this->promotion_money;
        $data['presale_deposit_money'] = $presale_deposit_money;
        $data['order_money'] = $order_money;
        $data['balance_money'] = $this->balance_money;
        $data['pay_money'] = $this->pay_money;
        $data['goods_money'] = $this->goods_money;
        $data['goods_num'] = $this->goods_num;
        $data['is_virtual'] = $this->is_virtual;
        $data['final_money'] = $this->final_money;
        $data['presale_money'] = $presale_info['presale_price'] * $data['num'];
        $data['site_name'] = $presale_info['site_name'];

        return $data;
    }

    /**
     * 待付款订单（定金）
     * @param unknown $data
     */
    public function depositOrderPayment($data)
    {
        $calculate_data = $this->depositCalculate($data);

        //优惠券
        $coupon_list = $this->getOrderCouponList($calculate_data,$data);
        $calculate_data['shop_goods_list']["coupon_list"] = $coupon_list;

        $express_type = [];
        if ($this->is_virtual == 0) {

            //2. 查询店铺配送方式（1. 物流  2. 自提  3. 外卖）
            if ($calculate_data['shop_goods_list']["express_config"]["is_use"] == 1) {
                $express_type[] = ["title" => Express::express_type["express"]["title"], "name" => "express"];
            }
            //查询店铺是否开启门店自提
            if ($calculate_data['shop_goods_list']["store_config"]["is_use"] == 1) {
                //根据坐标查询门店
                $store_model = new Store();
                $store_condition = array(
                    ['site_id', '=', $data['site_id']],
                    ['is_pickup', '=', 1],
                    ['status', '=', 1],
                    ['is_frozen', '=', 0],
                );


                $latlng = array(
                    'lat' => $data['latitude'],
                    'lng' => $data['longitude'],
                );
                $store_list_result = $store_model->getLocationStoreList($store_condition, '*', $latlng);
                $store_list = $store_list_result["data"];
                //如果用户默认选中了门店
                $store_id = 0;
                if ($data['default_store_id'] > 0) {
                    if (!empty($store_list)) {
                        $store_array = array_column($store_list, 'store_id');
                        if (in_array($data['default_store_id'], $store_array)) {
                            $store_id = $data['default_store_id'];
                        } else {
                            $store_condition = array(
                                ['site_id', '=', $data['site_id']],
                                ['is_pickup', '=', 1],
                                ['status', '=', 1],
                                ['is_frozen', '=', 0],
                                ['store_id', '=', $data['default_store_id']],
                            );
                            $store_info_result = $store_model->getStoreInfo($store_condition, '*');
                            $store_info = $store_info_result['data'];
                            if (!empty($store_info)) {
                                $store_id = $data['default_store_id'];
                                $store_list[] = $store_info;
                            }
                        }
                    }

                }
                $express_type[] = ["title" => Express::express_type["store"]["title"], "name" => "store", "store_list" => $store_list, 'store_id' => $store_id];
            }
            //查询店铺是否开启外卖配送
            if ($calculate_data['shop_goods_list']["local_config"]["is_use"] == 1) {
                //查询本店的通讯地址
                $express_type[] = ["title" => "外卖配送", "name" => "local"];
            }
        }

        $calculate_data['shop_goods_list']["express_type"] = $express_type;

        return $calculate_data;
    }

    /**
     * 获取商品的计算信息
     * @param unknown $data
     */
    public function getOrderGoodsCalculate($data)
    {
        $shop_goods_list = [];
        $shop_goods = $this->getPresaleShopGoodsList($data);
        $shop_goods['promotion_money'] = 0;
        $shop_goods_list = $shop_goods;

        return $shop_goods_list;
    }

    /**
     * 获取立即购买商品信息
     * @param unknown $data
     * @return multitype:string number unknown mixed
     */
    public function getPresaleShopGoodsList($data)
    {
        $join = [
            ['shop ns', 'ngs.site_id = ns.site_id', 'inner']
        ];
        $field = 'sku_id, sku_name, sku_no, price, discount_price,cost_price, stock, volume, weight, sku_image, ngs.site_id, goods_state, is_virtual, is_free_shipping, shipping_template,goods_class, goods_class_name, goods_id,sku_spec_format,goods_name,max_buy,min_buy,ns.site_name';

        $sku_info = model("goods_sku")->getInfo([['sku_id', '=', $data['sku_id']], ["ngs.site_id", "=", $data["site_id"]]], $field, 'ngs', $join);
        if (empty($sku_info)) {
            return $this->error([], "不存在的商品!");
        }
        $price = $sku_info[ "price" ];

        $sku_info['num'] = $data['num'];
        $goods_money = $price * $data['num'];
        $sku_info['price'] = $price;
        $sku_info['goods_money'] = $goods_money;
        $sku_info['real_goods_money'] = $goods_money;
        $sku_info['coupon_money'] = 0; //优惠券金额
        $sku_info['promotion_money'] = 0; //优惠金额
        $goods_list[] = $sku_info;
        $shop_goods = [
            'goods_money' => $goods_money,
            'site_id' => $sku_info['site_id'],

            'goods_list_str' => $sku_info['sku_id'] . ':' . $sku_info['num'],
            'goods_list' => $goods_list,
            'order_name' => $sku_info["sku_name"],
            'goods_num' => $sku_info['num'],
            'limit_purchase' => [
                'goods_' . $sku_info['goods_id'] => [
                    'goods_id' => $sku_info['goods_id'],
                    'goods_name' => $sku_info["sku_name"],
                    'num' => $sku_info['num'],
                    'max_buy' => $sku_info['max_buy'],
                    'min_buy' => $sku_info['min_buy']
                ]
            ]
        ];
        return $shop_goods;
    }

    /**
     * 库存变化
     * @return array
     */
    public function decStock($param)
    {
        $condition = array(
            ['site_id', '=', $param['site_id']],
            ['presale_id', '=', $param['presale_id']],
            ['sku_id','=',$param['sku_id']]
        );
        $presale_info = model("promotion_presale_goods")->getInfo($condition, "presale_stock");
        if (empty($presale_info))
            return $this->error();

        if ($presale_info["presale_stock"] <= 0)
            return $this->error('', "库存不足!");

        //编辑sku库存
        $res = model("promotion_presale_goods")->setDec($condition, "presale_stock", $param["num"]);
        if ($res === false)
            return $this->error();

        return $this->success($res);
    }

    /**
     * 获取店铺订单计算
     * @param unknown $site_id 店铺id
     * @param unknown $goods_money 商品总价
     * @param unknown $goods_list 店铺商品列表
     * @param unknown $data 传输生成订单数据
     */
    public function shopOrderCalculate($shop_goods, $data)
    {
        $site_id = $data['site_id'];
        //交易配置
        $config_model = new Config();
        $order_config_result = $config_model->getOrderEventTimeConfig($site_id);
        $order_config = $order_config_result["data"];
        $shop_goods['order_config'] = $order_config['value'] ?? [];

        //定义计算金额
        $goods_money = $shop_goods['goods_money'];  //商品金额
        $delivery_money = 0;  //配送费用
        $promotion_money = 0;  //优惠费用（满减）
        $coupon_money = 0;     //优惠券费用
        $adjust_money = 0;     //调整金额
        $invoice_money = 0;    //发票金额
        $final_money = 0;      //尾款金额
        $balance_money = 0;    //会员余额
        $pay_money = 0;        //应付金额
        $order_money = 0;      //订单金额

        //实际抵扣金额
        if($data['presale_info']['presale_price'] == 0){//全款预售
            $deduction_money = 0;
        }else{
            $deduction_money = $data['presale_info']['presale_price'] * $data['num'] - $data['presale_info']['presale_deposit'] * $data['num'];
        }

        //计算邮费
        if ($this->is_virtual == 1) {
            //虚拟订单  运费为0
            $delivery_money = 0;
            $shop_goods['delivery']['delivery_type'] = '';
        }
        else {

            //查询店铺是否开启快递配送
            $express_config_model = new ExpressConfig();
            $express_config_result = $express_config_model->getExpressConfig($site_id);
            $express_config = $express_config_result["data"];
            $shop_goods["express_config"] = $express_config;
            //查询店铺是否开启门店自提
            $store_config_result = $express_config_model->getStoreConfig($site_id);
            $store_config = $store_config_result["data"];
            $shop_goods["store_config"] = $store_config;
            //查询店铺是否开启外卖配送
            $local_config_result = $express_config_model->getLocalDeliveryConfig($site_id);
            $local_config = $local_config_result["data"];
            $shop_goods["local_config"] = $local_config;
            //如果本地配送开启, 则查询出本地配送的配置
            if ($shop_goods["local_config"]['is_use'] == 1) {
                $local_model = new Local();
                $local_info_result = $local_model->getLocalInfo([['site_id', '=', $site_id]]);
                $local_info = $local_info_result['data'];
                $shop_goods["local_config"]['info'] = $local_info;
            }

            $delivery_array = $data['delivery'] ?? [];
            $delivery_type = $delivery_array["delivery_type"] ?? 'express';
            if ($delivery_type == "store") {
                if (isset($data['delivery']["delivery_type"]) && $data['delivery']["delivery_type"] == "store") {
                    //门店自提
                    $delivery_money = 0;
                    $shop_goods['delivery']['delivery_type'] = 'store';
                    if ($shop_goods["store_config"]["is_use"] == 0) {
                        $this->error = 1;
                        $this->error_msg = "门店自提方式未开启!";
                    }
                    if (empty($data['delivery']["store_id"])) {
                        $this->error = 1;
                        $this->error_msg = "门店未选择!";
                    }
                    $shop_goods['delivery']['store_id'] = $data['delivery']["store_id"];

                    $shop_goods = $this->storeOrderData($shop_goods, $data);
                }
            } else {
                if (empty($data['member_address'])) {
                    $delivery_money = 0;
                    $shop_goods['delivery']['delivery_type'] = 'express';

                    $this->error = 1;
                    $this->error_msg = "未配置默认收货地址!";
                } else {
                    if (!isset($data['delivery']["delivery_type"]) || $data['delivery']["delivery_type"] == "express") {
                        if ($shop_goods["express_config"]["is_use"] == 1) {
                            //物流配送
                            $express = new Express();
                            $express_fee_result = $express->calculate($shop_goods, $data);
                            if ($express_fee_result["code"] < 0) {
                                $this->error = 1;
                                $this->error_msg = $express_fee_result["message"];
                                $delivery_fee = 0;
                            } else {
                                $delivery_fee = $express_fee_result['data']['delivery_fee'];
                            }
                        } else {
                            $this->error = 1;
                            $this->error_msg = "物流配送方式未开启!";
                            $delivery_fee = 0;
                        }
                        $delivery_money = $delivery_fee;
                        $shop_goods['delivery']['delivery_type'] = 'express';
                    } else if ($data['delivery']["delivery_type"] == "local") {
                        //外卖配送
                        $delivery_money = 0;
                        $shop_goods['delivery']['delivery_type'] = 'local';
                        if ($shop_goods["local_config"]["is_use"] == 0) {
                            $this->error = 1;
                            $this->error_msg = "外卖配送方式未开启!";
                        } else {
                            $local_delivery_time = 0;
                            if (!empty($data['buyer_ask_delivery_time'])) {
                                $buyer_ask_delivery_time_temp = explode(':', $data['buyer_ask_delivery_time']);
                                $local_delivery_time = $buyer_ask_delivery_time_temp[0] * 3600 + $buyer_ask_delivery_time_temp[1] * 60;
                            }
                            $shop_goods['buyer_ask_delivery_time'] = $local_delivery_time;

                            $local_model = new Local();
                            $local_result = $local_model->calculate($shop_goods, $data);


                            if ($local_result['code'] < 0) {
                                $this->error = $local_result['data']['code'];
                                $this->error_msg = $local_result['message'];
                            } else {
                                $delivery_money = $local_result['data']['delivery_money'];
                                if (!empty($local_result['data']['error_code'])) {
                                    $this->error = $local_result['data']['code'];
                                    $this->error_msg = $local_result['data']['error'];
                                }
                            }
                        }
                    }
                }
            }
        }

        //尾款金额
        $final_money = $shop_goods['goods_money'] - $data['presale_info']['presale_deposit'] * $data['num'] - $promotion_money - $deduction_money + $delivery_money;
        $shop_goods['order_money'] = $order_money; //订单总金额

        //发票相关

        //todo  模拟常规订单的数据结构

        $is_invoice = $data['is_invoice'] ?? 0;
        if($is_invoice > 0){
            $data['invoice'] = [$site_id => [
                'is_invoice' => $data['is_invoice'],
                'invoice_type' => $data['invoice_type'],
                'invoice_title' => $data['invoice_title'],
                'taxpayer_number' => $data['taxpayer_number'],
                'invoice_content' => $data['invoice_content'],
                'invoice_full_address' => $data['invoice_full_address'],
                'is_tax_invoice' => $data['is_tax_invoice'],
                'invoice_email' => $data['invoice_email'],
                'invoice_title_type' => $data['invoice_title_type'],
                'buyer_ask_delivery_time' => $data['buyer_ask_delivery_time'],
            ]];
        }

        $shop_goods = $this->invoice($shop_goods, $data, $this);

        $final_money = $final_money + $shop_goods['invoice_money'] + $shop_goods['invoice_delivery_money'];

        //理论上是多余的操作
        if ($final_money < 0) {
            $final_money = 0;
        }

        //总结计算
        $shop_goods['goods_money'] = $goods_money;
        $shop_goods['delivery_money'] = $delivery_money;
        $shop_goods['adjust_money'] = $adjust_money;
        $shop_goods['promotion_money'] = $promotion_money;
        $shop_goods['final_money'] = $final_money;
        $shop_goods['balance_money'] = $balance_money;
        $shop_goods['pay_money'] = $pay_money;
        $shop_goods['order_money'] = $order_money;

        $this->goods_money += $goods_money;
        $this->delivery_money += $delivery_money;
        $this->coupon_money += $coupon_money;
        $this->adjust_money += $adjust_money;
        $this->invoice_money += $shop_goods['invoice_money'];
        $this->invoice_delivery_money += $shop_goods['invoice_delivery_money'];
        $this->promotion_money += $promotion_money;
        $this->final_money += $final_money;
        $this->order_name = string_split($this->order_name, ",", $shop_goods["order_name"]);
        //买家留言
        if (isset($data['buyer_message']) && isset($data['buyer_message'])) {
            $item_buyer_message = $data['buyer_message'];
            $shop_goods["buyer_message"] = $item_buyer_message;
        } else {
            $shop_goods["buyer_message"] = '';
        }
        return $shop_goods;
    }

    /**
     * 获取立即购买商品信息
     * @param unknown $data
     * @return multitype:string number unknown mixed
     */
    public function getShopGoodsList($data)
    {
        $join = [
            [
                'site ns',
                'ngs.site_id = ns.site_id',
                'inner'
            ]
        ];
        $field = 'sku_id, sku_name, sku_no, price, discount_price,cost_price, stock, volume, weight, sku_image, ngs.site_id, goods_state, is_virtual, is_free_shipping, shipping_template,goods_class, goods_class_name, goods_id,sku_spec_format,goods_name,max_buy,min_buy';
        $sku_info = model("goods_sku")->getInfo([['sku_id', '=', $data['sku_id']], ["ngs.site_id", "=", $data["site_id"]]], $field, 'ngs', $join);
        if (empty($sku_info)) {
            return $this->error([], "不存在的商品!");
        }

        $price = $data["price"];

        $sku_info['num'] = $data['num'];
        $goods_money = $price * $data['num'];
        $sku_info['price'] = $price;
        $sku_info['goods_money'] = $goods_money;
        $sku_info['real_goods_money'] = $goods_money;
        $sku_info['coupon_money'] = 0; //优惠券金额
        $sku_info['promotion_money'] = 0; //优惠金额
        $goods_list[] = $sku_info;
        $shop_goods = [
            'goods_money' => $goods_money,
            'site_id' => $sku_info['site_id'],

            'goods_list_str' => $sku_info['sku_id'] . ':' . $sku_info['num'],
            'goods_list' => $goods_list,
            'order_name' => $sku_info["sku_name"],
            'goods_num' => $sku_info['num'],
            'limit_purchase' => [
                'goods_' . $sku_info['goods_id'] => [
                    'goods_id' => $sku_info['goods_id'],
                    'goods_name' => $sku_info["sku_name"],
                    'num' => $sku_info['num'],
                    'max_buy' => $sku_info['max_buy'],
                    'min_buy' => $sku_info['min_buy']
                ]
            ]
        ];
        return $shop_goods;
    }

    /**
     * 添加门店关注记录和减少门店商品库存
     * @param $data
     * @return array
     */
    public function addStoreMemberAndDecStock($data)
    {
        if (!empty($data['delivery_store_id'])) {
            //添加店铺关注记录
            $shop_member_model = new StoreMemberModel();
            $res = $shop_member_model->addStoreMember($data['delivery_store_id'], $data['member_id']);
            if ($res["code"] < 0) {
                return $res;
            }
            $store_goods_sku_model = new StoreGoodsSku();
            $stock_result = $store_goods_sku_model->decStock(["store_id" => $data["delivery_store_id"], "sku_id" => $data["sku_id"], "store_stock" => $data["num"]]);
            if ($stock_result["code"] < 0) {
                return $this->error('', '当前门店库存不足,请选择其他门店');
            }
        }
        return $this->success();
    }


    /************************************************** 定金支付 end *********************************************************************/

    /************************************************** 尾款支付 start *********************************************************************/

    /**
     * 订单计算（尾款）
     * @param unknown $data
     */
    public function finalCalculate($data)
    {
        $data = $this->initMemberAccount($data);//初始化会员账户
        //余额付款
        if ($data['is_balance'] > 0) {
            $this->member_balance_money = $data["member_account"]["balance_total"] ?? 0;
        }
        //查询预售订单信息
        $presale_order_model = new PresaleOrder();
        $order_info_result = $presale_order_model->getPresaleOrderInfo([["id", "=", $data["id"]]]);
        $order_info = $order_info_result["data"];

        $data["order_info"] = $order_info;

        //判断是否可以支付尾款
        if ($order_info["pay_start_time"] > time()) {
            $this->error = 1;
            $this->error_msg = "尾款支付时间还未开始!";
        }
        if ($order_info["pay_end_time"] < time()) {
            $this->error = 1;
            $this->error_msg = "尾款支付时间已过，已停止支付!";
        }

        //尾款总金额（尾款实际金额 + 发票 + 物流等）
        $order_money = $order_info['final_money'];

        //余额抵扣(判断是否使用余额)
        if ($this->member_balance_money > 0) {
            if ($order_money <= $this->member_balance_money) {
                $balance_money = $order_money;
            } else {
                $balance_money = $this->member_balance_money;
            }
        } else {
            $balance_money = 0;
        }
        $pay_money = $order_money - $balance_money;//计算出实际支付金额
        $this->member_balance_money -= $balance_money;//预减少账户余额
        $this->balance_money += $balance_money;//累计余额
        $is_use = 1;

        $this->pay_money += $pay_money;
        //总结计算
        $data['balance_final_money'] = $this->balance_money;
        $data['pay_final_money'] = $this->pay_money;
        $data['is_use_balance'] = $is_use;
        $data['balance_money'] = $this->balance_money;
        return $data;
    }


    /**
     * 尾款支付
     * @param $data
     * @return array
     */
    public function payfinalMoneyPresaleOrder($data)
    {
        //查询出会员相关信息
        $calculate_data = $this->finalCalculate($data);

        if (isset($calculate_data['code']) && $calculate_data['code'] < 0)
            return $calculate_data;

        if ($this->error > 0) {
            return $this->error(['error_code' => $this->error], $this->error_msg);
        }

        $pay = new Pay();
        $out_trade_no = $pay->createOutTradeNo();

        $order_data = [
            'balance_final_money' => $calculate_data['balance_final_money'],
            'pay_final_money' => $calculate_data['pay_final_money'],
            'final_out_trade_no' => $out_trade_no,
        ];

        model('promotion_presale_order')->startTrans();
        try {

            model('promotion_presale_order')->update($order_data, [['site_id', '=', $data['site_id']], ['id', '=', $data['id']]]);

            //扣除余额(统一扣除)
            if ($calculate_data['is_use_balance'] == 1) {

                if ($calculate_data["balance_final_money"] > 0) {

                    $calculate_data['order_id'] = $data['id'];
                    $this->pay_type = "BALANCE";
                    $balance_result = $this->useBalance($calculate_data, $this);
                    if ($balance_result["code"] < 0) {
                        model("promotion_presale_order")->rollback();
                        return $balance_result;
                    }
                }
            }

            $order_name = $calculate_data['order_info']['sku_name'];
            //生成整体支付单据
            $pay->addPay($data['site_id'], $out_trade_no, $this->pay_type, $order_name, $order_name, $this->pay_money, '', 'FinalOrderPayNotify', '');

            model('promotion_presale_order')->commit();
            return $this->success($out_trade_no);
        } catch (\Exception $e) {
            model()->rollback();
            return $this->error('', $e->getMessage().$e->getFile().$e->getLine());
        }

    }

    /************************************************** 尾款支付 end *********************************************************************/

}