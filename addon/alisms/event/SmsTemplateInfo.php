<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\alisms\event;

use addon\alisms\model\Config;

/**
 * 获取短信模板数据
 */
class SmsTemplateInfo
{
    /**
     * 获取短信模板数据
     */
    public function handle($param)
    {
        $config_model = new Config();
        $sms_config = $config_model->getSmsConfig();
        $sms_config = $sms_config[ 'data' ];
        if ($sms_config['is_use']) {
            $template_info = model('message')->getInfo([ ['keywords', '=', $param['keywords'] ]]);
            if (!empty($template_info['sms_json'])) {
                return json_decode($template_info['sms_json'], true);
            }
        }
    }
}