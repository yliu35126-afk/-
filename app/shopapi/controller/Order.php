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

namespace app\shopapi\controller;

use app\model\express\ExpressPackage;
use app\model\order\Config as ConfigModel;
use app\model\order\Order as OrderModel;
use app\model\order\OrderCommon as OrderCommonModel;
use think\facade\Config;

/**
 * 订单
 * Class Order
 * @package app\shop\controller
 */
class Order extends BaseApi
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) {
            echo $this->response($token);
            exit;
        }
    }

    /**
     * 获取订单查询条件
     */
    public function condition()
    {
        $data = [];

        $order_label_list = array (
            "order_no" => "订单号",
            "out_trade_no" => "外部单号",
            "name" => "收货人姓名",
            "mobile" => "收货人手机号",
            "order_name" => "商品名称",
        );

        $order_common_model = new OrderCommonModel();

        $order_type_list = $order_common_model->getOrderTypeStatusList();
        if (array_key_exists('all', $order_type_list)) {
            array_unshift($order_type_list, $order_type_list[ 'all' ]);
            unset($order_type_list[ 'all' ]);
        }
        $data[ 'order_type_list' ] = $order_type_list;
        $data[ 'order_label_list' ] = $order_label_list;
        $data[ 'order_status_list' ] = $order_type_list[ 1 ][ 'status' ];//订单状态

        //订单来源 (支持端口)
        $order_from = Config::get("app_type");
        $data[ 'order_from_list' ] = $order_from;

        $pay_type = $order_common_model->getPayType();
        $data[ 'pay_type_list' ] = $pay_type;

        //营销活动类型
        $order_promotion_type = event('OrderPromotionType');
        $data[ 'promotion_type' ] = $order_promotion_type;
        $data[ 'http_type' ] = get_http_type();

        return $this->response($this->success($data));
    }

    /**
     * 订单列表
     */
    public function lists()
    {
        $order_label_list = array (
            "order_no" => "订单号",
            "out_trade_no" => "外部单号",
            "name" => "收货人姓名",
            "mobile" => "收货人手机号",
            "order_name" => "商品名称",
        );
        $order_status = isset($this->params[ 'order_status' ]) ? $this->params[ 'order_status' ] : '';//订单状态
        $order_name = isset($this->params[ 'order_name' ]) ? $this->params[ 'order_name' ] : '';
        $pay_type = isset($this->params[ 'pay_type' ]) ? $this->params[ 'pay_type' ] : '';
        $order_from = isset($this->params[ 'order_from' ]) ? $this->params[ 'order_from' ] : '';
        $start_time = isset($this->params[ 'start_time' ]) ? $this->params[ 'start_time' ] : '';
        $end_time = isset($this->params[ 'end_time' ]) ? $this->params[ 'end_time' ] : '';

        $order_label = isset($this->params[ 'order_label' ]) ? $this->params[ 'order_label' ] : '';
        if (empty($order_label_list[ $order_label ])) {
            $order_label = '';
        }
        $search_text = isset($this->params[ 'search' ]) ? $this->params[ 'search' ] : '';
        $promotion_type = isset($this->params[ 'promotion_type' ]) ? $this->params[ 'promotion_type' ] : '';//订单类型
        $order_type = isset($this->params[ 'order_type' ]) ? $this->params[ 'order_type' ] : 'all';//营销类型
        $settlement_state = isset($this->params[ 'settlement_state' ]) ? $this->params[ 'settlement_state' ] : '';//结算状态

        $page_index = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        $alias = 'o';
        $join = null;
        $condition = [
            [ "o.site_id", "=", $this->site_id ]
        ];
        //订单状态
        if ($order_status != "") {
            if ($order_status != 'refunding') {
                $condition[] = [ "o.order_status", "=", $order_status ];
            } else {
                $join = [
                    [
                        'order_goods og',
                        'og.order_id = o.order_id',
                        'left'
                    ]
                ];
                $condition[] = [ "og.refund_status", "not in", [ 0, 3 ] ];
            }
        }
        //订单内容 模糊查询
        if ($order_name != "") {
            $condition[] = [ "o.order_name", 'like', "%$order_name%" ];
        }
        //订单来源
        if ($order_from != "") {
            $condition[] = [ "o.order_from", "=", $order_from ];
        }
        //订单支付
        if ($pay_type != "") {
            $condition[] = [ "o.pay_type", "=", $pay_type ];
        }
        //订单类型
        if ($order_type != 'all') {
            $condition[] = [ "o.order_type", "=", $order_type ];
        }
        //结算状态
        if ($settlement_state == 1) {
            $condition[] = [ "o.is_settlement", "=", '1' ];
        } elseif ($settlement_state == 2) {
            $condition[] = [ "o.is_settlement", "=", '0' ];
        }
        //营销类型
        if ($promotion_type != "") {
            if ($promotion_type == 'empty') {
                $condition[] = [ "o.promotion_type", "=", '' ];
            } else {
                $condition[] = [ "o.promotion_type", "=", $promotion_type ];
            }
        }
        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ "o.create_time", ">=", date_to_time($start_time) ];
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ "o.create_time", "<=", date_to_time($end_time) ];
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'o.create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
        }
        if ($search_text != "") {
            $condition[] = [ 'o.' . $order_label, 'like', "%$search_text%" ];
        }
        $order_common_model = new OrderCommonModel();
        $field = 'o.order_id, o.order_no, o.order_type,o.order_status, o.order_status_name, o.order_status_action, o.pay_type_name, o.name, o.mobile, o.address, o.full_address, o.order_money, o.create_time, o.remark, o.promotion_type_name, o.promotion_status_name';
        $oder_goods_field = 'order_goods_id, goods_id, sku_id, sku_image, goods_class_name, price, num, is_present, goods_name, sku_spec_format, refund_status, refund_status_name';
        $list = $order_common_model->getOrderPageList($condition, $page_index, $page_size, "o.create_time desc", $field, $alias, $join, $oder_goods_field);
        return $this->response($list);
    }

    /**
     * 快递订单详情
     */
    public function detail()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;

        $order_common_model = new OrderCommonModel();
        $condition = array (
            [ 'order_id', '=', $order_id ],
            [ 'site_id', '=', $this->site_id ]
        );
        $order_detail_result = $order_common_model->getOrderDetail($condition);

        $order_log_condition = array (
            [ 'order_id', '=', $order_id ]
        );
        $order_log_count = $order_common_model->getOrderLogCount($order_log_condition);

        if (empty($order_detail_result[ 'data' ])) {
            return $this->response($this->error('查询不到此订单信息!'));
        }

        if ($order_detail_result[ 'data' ][ 'buyer_ask_delivery_time' ] == 0) {
            $order_detail_result[ 'data' ][ 'buyer_ask_delivery_time_str' ] = '立即送达';
        } else {
            $order_detail_result[ 'data' ][ 'buyer_ask_delivery_time_str' ] = date("H:i:s", $order_detail_result[ 'data' ][ 'buyer_ask_delivery_time' ]);
        }
        $order_detail_result[ 'data' ][ 'log_count' ] = $order_log_count[ 'data' ];

        return $this->response($order_detail_result);
    }

    /**
     * 订单日志列表
     * @return false|string
     */
    public function log()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        $order_common_model = new OrderCommonModel();

        $order_log_condition = array (
            [ 'order_id', '=', $order_id ]
        );
        $order_log_list = $order_common_model->getOrderLogList($order_log_condition);
        return $this->response($order_log_list);
    }

    /**
     * 订单关闭
     * @return mixed
     */
    public function close()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        $order_common_model = new OrderCommonModel();
        $close_condition = array (
            [ 'order_id', '=', $order_id ],
            [ 'site_id', '=', $this->site_id ],
        );
        $result = $order_common_model->orderClose($close_condition);
        return $this->response($result);
    }

    /**
     * 订单调价
     * @return mixed
     */
    public function adjustPrice()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        $adjust_money = isset($this->params[ 'adjust_money' ]) ? $this->params[ 'adjust_money' ] : 0;
        $delivery_money = isset($this->params[ 'delivery_money' ]) ? $this->params[ 'delivery_money' ] : 0;
        $order_common_model = new OrderCommonModel();
        $condition = array (
            [ 'order_id', '=', $order_id ],
            [ 'site_id', '=', $this->site_id ]
        );
        $result = $order_common_model->orderAdjustMoney($condition, $adjust_money, $delivery_money);
        return $this->response($result);
    }

    /**
     * 订单发货
     * @return mixed
     */
    public function delivery()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        $order_goods_ids = isset($this->params[ 'order_goods_ids' ]) ? $this->params[ 'order_goods_ids' ] : '';
        $express_company_id = isset($this->params[ 'express_company_id' ]) ? $this->params[ 'express_company_id' ] : 0;
        $delivery_no = isset($this->params[ 'delivery_no' ]) ? $this->params[ 'delivery_no' ] : '';
        $delivery_type = isset($this->params[ 'delivery_type' ]) ? $this->params[ 'delivery_type' ] : 0;
        $order_model = new OrderModel();
        $data = array (
            "type" => isset($this->params[ 'type' ]) ? $this->params[ 'type' ] : 'manual',//发货方式（手动发货、电子面单）
            "order_goods_ids" => $order_goods_ids,
            "express_company_id" => $express_company_id,
            "delivery_no" => $delivery_no,
            "order_id" => $order_id,
            "delivery_type" => $delivery_type,
            "site_id" => $this->site_id,
            "template_id" => isset($this->params[ 'template_id' ]) ? $this->params[ 'template_id' ] : '0'//电子面单模板id
        );
        $result = $order_model->orderGoodsDelivery($data);
        return $this->response($result);
    }

    /**
     * 获取订单项列表
     */
    public function getOrderGoodsList()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        $delivery_status = isset($this->params[ 'delivery_status' ]) ? $this->params[ 'delivery_status' ] : '';

        $order_common_model = new OrderCommonModel();
        $condition = array (
            [ "order_id", "=", $order_id ],
            [ "site_id", "=", $this->site_id ],
            [ "refund_status", "<>", 3 ],
        );
        if ($delivery_status != '') {
            $condition[] = [ "delivery_status", "=", $delivery_status ];
        }
        $field = "order_goods_id, order_id, site_id, site_name, sku_name, sku_image, sku_no, is_virtual, price, cost_price, num, goods_money, cost_money, delivery_status, delivery_no, goods_id, delivery_status_name,refund_status,refund_status_name";
        $result = $order_common_model->getOrderGoodsList($condition, $field, '', null, "");
        return $this->response($result);
    }

    /**
     * 订单修改收货地址
     * @return mixed
     */
    public function editAddress()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;

        $order_model = new OrderModel();
        $province_id = isset($this->params[ 'province_id' ]) ? $this->params[ 'province_id' ] : '';
        $city_id = isset($this->params[ 'city_id' ]) ? $this->params[ 'city_id' ] : '';
        $district_id = isset($this->params[ 'district_id' ]) ? $this->params[ 'district_id' ] : '';
        $community_id = isset($this->params[ 'community_id' ]) ? $this->params[ 'community_id' ] : '';
        $address = isset($this->params[ 'address' ]) ? $this->params[ 'address' ] : '';
        $full_address = isset($this->params[ 'full_address' ]) ? $this->params[ 'full_address' ] : '';
        $longitude = isset($this->params[ 'longitude' ]) ? $this->params[ 'longitude' ] : '';
        $latitude = isset($this->params[ 'latitude' ]) ? $this->params[ 'latitude' ] : '';
        $mobile = isset($this->params[ 'mobile' ]) ? $this->params[ 'mobile' ] : '';
        $telephone = isset($this->params[ 'telephone' ]) ? $this->params[ 'telephone' ] : '';
        $name = isset($this->params[ 'name' ]) ? $this->params[ 'name' ] : '';
        $data = array (
            "province_id" => $province_id,
            "city_id" => $city_id,
            "district_id" => $district_id,
            "community_id" => $community_id,
            "address" => $address,
            "full_address" => $full_address,
            "longitude" => $longitude,
            "latitude" => $latitude,
            "mobile" => $mobile,
            "telephone" => $telephone,
            "name" => $name,
        );
        $condition = array (
            [ "order_id", "=", $order_id ],
            [ "site_id", "=", $this->site_id ]
        );
        $result = $order_model->orderAddressUpdate($data, $condition);
        return $this->response($result);
    }

    /**
     * 获取订单信息
     */
    public function getOrderInfo()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;

        $order_common_model = new OrderCommonModel();
        $condition = array (
            [ "order_id", "=", $order_id ],
            [ "site_id", "=", $this->site_id ],
        );
        $result = $order_common_model->getOrderInfo($condition);
        return $this->response($result);
    }

    /**
     * 获取订单 订单项内容
     */
    public function getOrderDetail()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        $order_common_model = new OrderCommonModel();
        $condition = array (
            [ 'order_id', '=', $order_id ],
            [ 'site_id', '=', $this->site_id ]
        );
        $result = $order_common_model->getOrderDetail($condition);
        return $this->response($result);
    }

    /**
     * 卖家备注
     */
    public function orderRemark()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        $remark = isset($this->params[ 'remark' ]) ? $this->params[ 'remark' ] : '';

        $order_common_model = new OrderCommonModel();
        $condition = array (
            [ "order_id", "=", $order_id ],
            [ "site_id", "=", $this->site_id ],
        );
        $data = array (
            "remark" => $remark
        );
        $result = $order_common_model->orderUpdate($data, $condition);
        return $this->response($result);
    }

    /**
     * 延长收货时间
     */
    public function extendTakeDelivery()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        $condition = array (
            [ 'order_id', '=', $order_id ],
            [ 'site_id', '=', $this->site_id ],
        );
        $order_common_model = new OrderCommonModel();
        $log_data = [
            'uid' => $this->uid,
            'username' => $this->user_info[ 'username' ],
            'module' => 'shop'
        ];
        $result = $order_common_model->extendTakeDelivery($condition, $log_data);
        return $this->response($result);
    }

    /**
     * 线下支付
     */
    public function offlinePay()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        $order_common_model = new OrderCommonModel();
        $order_detail_result = $order_common_model->orderOfflinePay($order_id);
        return $this->response($order_detail_result);
    }

    /**
     * 交易配置
     */
    public function config()
    {
        $config_model = new ConfigModel();

        //订单事件时间设置数据
        $type = isset($this->params[ 'type' ]) ? $this->params[ 'type' ] : [];
        $data = [
            'invoice_status' => isset($this->params[ 'invoice_status' ]) ? $this->params[ 'invoice_status' ] : 0,
            'invoice_rate' => isset($this->params[ 'invoice_rate' ]) ? $this->params[ 'invoice_rate' ] : 0,
            'invoice_content' => isset($this->params[ 'invoice_content' ]) ? $this->params[ 'invoice_content' ] : [],
            'invoice_money' => isset($this->params[ 'invoice_money' ]) ? $this->params[ 'invoice_money' ] : 0,
            'type' => $type,
        ];
        $res = $config_model->setOrderInvoiceConfig($data, $this->site_id, $this->app_module);
        return $this->response($res);
    }

    /**
     * 订单列表（发票）
     */
    public function invoicelist()
    {
        $order_common_model = new OrderCommonModel();

        $page_index = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $alias = 'o';
        $join = null;
        $condition = [
            [ "o.site_id", "=", $this->site_id ],
            [ 'o.is_invoice', '=', 1 ]
        ];

        //订单编号
        $order_no = isset($this->params[ 'order_no' ]) ? $this->params[ 'order_no' ] : '';
        if ($order_no) {
            $condition[] = [ "o.order_no", "like", "%" . $order_no . "%" ];
        }
        //订单状态
        $order_status = isset($this->params[ 'order_status' ]) ? $this->params[ 'order_status' ] : '';
        if ($order_status != "") {
            if ($order_status != 'refunding') {
                $condition[] = [ "o.order_status", "=", $order_status ];
            } else {
                $join = [
                    [
                        'order_goods og',
                        'og.order_id = o.order_id',
                        'left'
                    ]
                ];
                $condition[] = [ "og.refund_status", "not in", [ 0, 3 ] ];
            }
        }
        $order_type = isset($this->params[ 'order_type' ]) ? $this->params[ 'order_type' ] : 'all';//营销类型
        $start_time = isset($this->params[ 'start_time' ]) ? $this->params[ 'start_time' ] : '';
        $end_time = isset($this->params[ 'end_time' ]) ? $this->params[ 'end_time' ] : '';

        //订单类型
        if ($order_type != 'all') {
            $condition[] = [ "o.order_type", "=", $order_type ];
        }

        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ "o.create_time", ">=", date_to_time($start_time) ];
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ "o.create_time", "<=", date_to_time($end_time) ];
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'o.create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
        }
        $list = $order_common_model->getOrderPageList($condition, $page_index, $page_size, "o.create_time desc", 'o.*', $alias, $join);
        return $this->response($list);
    }

    /**
     * 订单包裹信息
     */
    public function package()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : '';//订单id
        $express_package_model = new ExpressPackage();
        $condition = array (
            [ "order_id", "=", $order_id ],
        );
        $order_model = new OrderCommonModel();
        $order_info = $order_model->getOrderInfo($condition)[ 'data' ] ?? [];
        $condition[] = [ 'site_id', '=', $order_info[ 'site_id' ] ];
        $package = $express_package_model->package($condition, $order_info[ 'mobile' ]);
        if ($package) {
            $result = [
                'package' => $package,
                'order_status' => $order_info[ 'order_status' ]
            ];
            return $this->response($this->success($result));
        } else {
            return $this->response($this->error());
        }
    }

    /**
     * 修改单个订单的物流信息（电子面单的除外）
     */
    public function editOrderDelivery()
    {
        $order_id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;// 订单id
        $package_id = isset($this->params[ 'package_id' ]) ? $this->params[ 'package_id' ] : 0;// 包裹id
        $delivery_type = isset($this->params[ 'delivery_type' ]) ? $this->params[ 'delivery_type' ] : 0;// 是否需要物流
        $express_company_id = isset($this->params[ 'express_company_id' ]) ? $this->params[ 'express_company_id' ] : '';// 物流公司
        $delivery_no = isset($this->params[ 'delivery_no' ]) ? $this->params[ 'delivery_no' ] : '';// 物流单号

        $delivery_json = array (
            'site_id' => $this->site_id,
            'order_id' => $order_id,
            'package_id' => $package_id,
            'delivery_type' => $delivery_type,
            'express_company_id' => $express_company_id,
            'delivery_no' => $delivery_no
        );
        $express_package_model = new ExpressPackage();
        $res = $express_package_model->editOrderExpressDeliveryPackage($delivery_json);
        return $this->response($res);
    }

}