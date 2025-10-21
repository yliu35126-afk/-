<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: http://www.niushop.com.cn
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : niuteam
 * @date : 2015.1.17
 * @version : v1.0.0.0
 */

namespace addon\presale\api\controller;

use app\api\controller\BaseApi;
use addon\presale\model\PresaleOrderCreate as OrderCreateModel;

/**
 * 订单创建
 * @author Administrator
 *
 */
class Ordercreate extends BaseApi
{

    /**
     * 定金创建订单
     */
    public function depositCreate()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $order_create = new OrderCreateModel();
        $data = [
            'presale_id' => isset($this->params['presale_id']) ? $this->params['presale_id'] : '', //预售id
            'sku_id' => isset($this->params['sku_id']) ? $this->params['sku_id'] : '',
            'num' => isset($this->params['num']) ? $this->params['num'] : '',//购买的商品数量
            'site_id' => isset($this->params['site_id']) ? $this->params['site_id'] : '',//站点id
            'site_name' => isset($this->params['site_name']) ? $this->params['site_name'] : '',//店铺名称
            'member_id' =>  isset($this->params['member_id']) ? $this->params['member_id'] : '',
            'is_balance' => isset($this->params['is_balance']) ? $this->params['is_balance'] : 0,//是否使用余额
            'order_from' => $this->params['app_type'],
            'order_from_name' => $this->params['app_type_name'],
            'pay_password' => isset($this->params['pay_password']) ? $this->params['pay_password'] : '',//支付密码
            'buyer_message' => isset($this->params["buyer_message"]) && !empty($this->params["buyer_message"]) ? $this->params["buyer_message"] : '',
            'delivery' => isset($this->params["delivery"]) && !empty($this->params["delivery"]) ? json_decode($this->params["delivery"], true) : [],
            'coupon' => isset($this->params["coupon"]) && !empty($this->params["coupon"]) ? json_decode($this->params["coupon"], true) : [],
            'member_address' => isset($this->params["member_address"]) && !empty($this->params["member_address"]) ? json_decode($this->params["member_address"], true) : [],

            'latitude' => $this->params["latitude"] ?? null,
            'longitude' => $this->params["longitude"] ?? null,

            'is_invoice' => $this->params["is_invoice"] ?? 0,
            'invoice_type' => $this->params["invoice_type"] ?? 0,
            'invoice_title' => $this->params["invoice_title"] ?? '',
            'taxpayer_number' => $this->params["taxpayer_number"] ?? '',
            'invoice_content' => $this->params["invoice_content"] ?? '',
            'invoice_full_address' => $this->params["invoice_full_address"] ?? '',
            'is_tax_invoice' => $this->params["is_tax_invoice"] ?? 0,
            'invoice_email' => $this->params["invoice_email"] ?? '',
            'invoice_title_type' => $this->params["invoice_title_type"] ?? 0,
            'buyer_ask_delivery_time' => $this->params["buyer_ask_delivery_time"] ?? '',
        ];

        if (empty($data['presale_id']) && empty($data['sku_id'])) {
            return $this->response($this->error('', '缺少必填参数商品数据'));
        }
        $res = $order_create->create($data);
        return $this->response($res);
    }

    /**
     * 定金计算信息
     */
    public function depositCalculate()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'presale_id' => isset($this->params['presale_id']) ? $this->params['presale_id'] : '', //预售id
            'sku_id' => isset($this->params['sku_id']) ? $this->params['sku_id'] : '',
            'num' => isset($this->params['num']) ? $this->params['num'] : '',
            'site_id' => isset($this->params['site_id']) ? $this->params['site_id'] : '',//站点id
            'member_id' =>  isset($this->params['member_id']) ? $this->params['member_id'] : '',
            'is_balance' => isset($this->params['is_balance']) ? $this->params['is_balance'] : 0,//是否使用余额
            'pay_password' => isset($this->params['pay_password']) ? $this->params['pay_password'] : '',//支付密码
            'order_from' => $this->params['app_type'],
            'order_from_name' => $this->params['app_type_name'],
            'delivery' => isset($this->params["delivery"]) && !empty($this->params["delivery"]) ? json_decode($this->params["delivery"], true) : [],
            'coupon' => isset($this->params["coupon"]) && !empty($this->params["coupon"]) ? json_decode($this->params["coupon"], true) : [],
            'member_address' => isset($this->params["member_address"]) && !empty($this->params["member_address"]) ? json_decode($this->params["member_address"], true) : [],

            'latitude' => $this->params["latitude"] ?? null,
            'longitude' => $this->params["longitude"] ?? null,

            'is_invoice' => $this->params["is_invoice"] ?? 0,
            'invoice_type' => $this->params["invoice_type"] ?? 0,
            'invoice_title' => $this->params["invoice_title"] ?? '',
            'taxpayer_number' => $this->params["taxpayer_number"] ?? '',
            'invoice_content' => $this->params["invoice_content"] ?? '',
            'invoice_full_address' => $this->params["invoice_full_address"] ?? '',
            'is_tax_invoice' => $this->params["is_tax_invoice"] ?? 0,
            'invoice_email' => $this->params["invoice_email"] ?? '',
            'invoice_title_type' => $this->params["invoice_title_type"] ?? 0,
            'buyer_ask_delivery_time' => $this->params["buyer_ask_delivery_time"] ?? '',
        ];

        if (empty($data['presale_id']) && empty($data['sku_id'])) {
            return $this->response($this->error('', '缺少必填参数商品数据'));
        }
        $res = $order_create->depositCalculate($data);
        return $this->response($this->success($res));

    }

    /**
     * 待支付订单(定金)
     * @return string
     */
    public function depositPayment()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $order_create = new OrderCreateModel();
        $data = [
            'presale_id' => isset($this->params['presale_id']) ? $this->params['presale_id'] : '', //预售id
            'sku_id' => isset($this->params['sku_id']) ? $this->params['sku_id'] : '',
             'site_id' => isset($this->params['site_id']) ? $this->params['site_id'] : '',//站点id
            'num' => isset($this->params['num']) ? $this->params['num'] : '',
            'member_id' =>  isset($this->params['member_id']) ? $this->params['member_id'] : '',
            'is_balance' => isset($this->params['is_balance']) ? $this->params['is_balance'] : 0,//是否使用余额
            'order_from' => $this->params['app_type'],
            'order_from_name' => $this->params['app_type_name'],
            'latitude' => $this->params["latitude"] ?? null,
            'longitude' => $this->params["longitude"] ?? null,
            'default_store_id' => $this->params["default_store_id"] ?? 0,
        ];
        if (empty($data['presale_id']) && empty($data['sku_id'])) {
            return $this->response($this->error('', '缺少必填参数商品数据'));
        }

        $res = $order_create->depositOrderPayment($data);

        return $this->response($this->success($res));
    }

    /**
     * 待支付订单(尾款)
     * @return string
     */
    public function finalPayment()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);
        $order_create = new OrderCreateModel();
        $data = [
            'id' => isset($this->params['id']) ? $this->params['id'] : '', //预售订单id
            'site_id' => isset($this->params['site_id']) ? $this->params['site_id'] : '',//站点id
            'member_id' =>  isset($this->params['member_id']) ? $this->params['member_id'] : '',
            'is_balance' => isset($this->params['is_balance']) ? $this->params['is_balance'] : 0,//是否使用余额
            'pay_password' => isset($this->params['pay_password']) ? $this->params['pay_password'] : '',//支付密码
        ];
        if (empty($data['id'])) {
            return $this->response($this->error('', '缺少必填参数订单数据'));
        }
        $res = $order_create->finalCalculate($data);
        return $this->response($this->success($res));
    }


    /**
     *  尾款订单
     */
    public function finalCreate()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $order_create = new OrderCreateModel();
        $data = [
            'id' => isset($this->params['id']) ? $this->params['id'] : '', //预售订单id
            'site_id' => isset($this->params['site_id']) ? $this->params['site_id'] : '',//站点id
            'member_id' =>  isset($this->params['member_id']) ? $this->params['member_id'] : '',
            'is_balance' => isset($this->params['is_balance']) ? $this->params['is_balance'] : 0,//是否使用余额
            'pay_password' => isset($this->params['pay_password']) ? $this->params['pay_password'] : '',//支付密码
        ];
        if (empty($data['id'])) {
            return $this->response($this->error('', '缺少必填参数订单数据'));
        }

        $res = $order_create->payfinalMoneyPresaleOrder($data);
        return $this->response($res);
    }

}