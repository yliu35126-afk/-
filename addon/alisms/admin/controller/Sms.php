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

namespace addon\alisms\admin\controller;

use addon\alisms\model\Config as ConfigModel;
use app\admin\controller\BaseAdmin;

/**
 * 阿里云短信 控制器
 */
class Sms extends BaseAdmin
{
    public function config()
    {
        $config_model = new ConfigModel();
        if (request()->isAjax()) {
            $access_key_id = input("access_key_id", "");//access_key_id
            $access_key_secret = input("access_key_secret", "");//access_key_secret
            $smssign = input("smssign", '');//短信签名

            $status = input("status", 0);//启用状态
            $data = array (
                "access_key_id" => $access_key_id,
                "access_key_secret" => $access_key_secret,
                "smssign" => $smssign
            );
            $result = $config_model->setSmsConfig($data, $status);
            return $result;
        } else {
            $info_result = $config_model->getSmsConfig();
            $info = $info_result[ "data" ];
            $this->assign("info", $info);
            return $this->fetch("sms/config");
        }
    }
}