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


namespace addon\alisms\event;

use addon\alisms\model\Config;

/**
 * 短信方式  (后台调用)
 */
class SmsType
{
    /**
     * 短信发送方式方式及配置
     */
    public function handle()
    {
        $info = array (
            "sms_type" => "alisms",
            "sms_type_name" => "阿里云短信",
            "edit_url" => "alisms://admin/sms/config",
            "desc" => "阿里云短信服务（Short Message Service）支持国内和国际快速发送验证码、短信通知和推广短信，服务范围覆盖全球200多个国家和地区。国内短信支持三网合一专属通道，与工信部携号转网平台实时互联。电信级运维保障，实时监控自动切换，到达率高达99%。"
        );

        $config_model = new Config();
        $config = $config_model->getSmsConfig();
        $info[ 'status' ] = $config[ 'data' ][ 'is_use' ] ?? 0;

        return $info;
    }
}