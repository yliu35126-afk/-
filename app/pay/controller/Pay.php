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

namespace app\pay\controller;

use app\Controller;
use app\model\system\Pay as PayModel;

/**
 * 支付控制器
 */
class Pay extends Controller
{
    /**
     * 支付异步回调
     */
    public function notify()
    {
        event('PayNotify', []);
    }

    /**
     * 支付返回
     */
    public function payReturn()
    {
        $app_type     = input('app_type', '');
        $out_trade_no = input('out_trade_no', '');

        $pay_model = new PayModel();
        $pay_info_result = $pay_model->getPayInfo($out_trade_no);
        $pay_info = $pay_info_result['data'] ?? [];

        if(!empty($pay_info['return_url'])){
            $this->redirect(addon_url($pay_info['return_url']));
        }else{
            switch ($app_type) {
                case 'pc':
                    $pc_domain = getPcDomain();
                    $return_url = $pc_domain . '/result?code=' . $out_trade_no;
                    $this->redirect($return_url);
                    break;
                case 'h5':
                    $h5_domain = getH5Domain();
                    $return_url = $h5_domain . '/pages/pay/result/result?code=' . $out_trade_no;
                    $this->redirect($return_url);
                    break;
            }
        }

    }
}
