<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\alipay\event;

use addon\alipay\model\Config;

class TransferType
{
    public function handle(array $param)
    {

        $app_type = isset($param['app_type']) ? $param['app_type'] : '';
        if(!empty($app_type)){
            if (!in_array($app_type, [ "h5", "app", "pc", "aliapp" ])) {
                return '';
            }
            $config_model = new Config();
            $config_result = $config_model->getPayConfig();
            $config = $config_result["data"]["value"] ?? [];
            $transfer_status = $config["transfer_status"] ?? 0;
            if($transfer_status == 0){
                return '';
            }
        }
        $info = array(
            "type" => "alipay",
            "type_name" => "支付宝",
        );
        return $info;
    }
}