<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\supply\shop\controller;

use app\model\system\Pay as PayModel;

class Pay extends BaseSupplyshop
{

    public function __construct()
    {
        parent::__construct();
        $check_login_result = $this->checkLogin();
        if ($check_login_result[ 'code' ] < 0) {
            echo json_encode($check_login_result);
            exit();
        }
    }

    /**
     * 支付信息
     */
    public function notify()
    {
        $out_trade_no = input('out_trade_no', '');
        $pay = new PayModel();
        $info = $pay->getPayInfo($out_trade_no);
        $this->assign('info', $info);
        return $this->fetch("pay/notify", [], $this->replace);
    }

    /**
     * 支付调用
     */
    public function pay()
    {
        $pay = new PayModel();
        $out_trade_no = input('out_trade_no', '');
        $app_type = input('app_type', 'pc');
        if (request()->isAjax()) {
            $pay_type = input('pay_type', '');

            $return_url = addon_url("supply://shop/pay/notify", [ 'out_trade_no' => $out_trade_no, 'app_type' => $app_type ]);
            $result = $pay->pay($pay_type, $out_trade_no, $app_type, $this->site_id, $return_url);
            return $result;
        } else {
            $info = $pay->getPayType([ 'app_type' => 'pc' ]);
            $this->assign('pay_type', $info[ 'data' ]);
            $info = $pay->getPayInfo($out_trade_no);
            if (empty($info[ 'data' ]))
                $this->error('当前支付已取消!', addon_url('supply://shop/market/index'));

            $this->assign('info', $info);

            return $this->fetch("pay/pay", [], $this->replace);
        }
    }

    /**
     * 获取订单支付状态
     * @return array|void
     */
    public function status()
    {
        if (request()->isAjax()) {
            $pay = new PayModel();
            $out_trade_no = input('out_trade_no', '');
            $res = $pay->getPayStatus($out_trade_no);
            return $res;
        }
    }
}
