<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wechatpay\model;


use app\model\system\Config as ConfigModel;
use app\model\BaseModel;
/**
 * 微信支付配置
 */
class Config extends BaseModel
{
    /**
     * 支持端口
     * @var unknown
     */
    public $app_type = ['h5', 'wechat','pc', 'weapp', 'app'];
    
    /**
     * 设置支付配置
     * array $data
     */
    public function setPayConfig($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '微信支付配置', 1, [['site_id', '=', 0], ['app_module', '=', 'admin'], ['config_key', '=', 'WECHAT_PAY_CONFIG']]);
        return $res;
    }
    
    /**
     * 获取支付配置
     */
    public function getPayConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', 0], ['app_module', '=', 'admin'], ['config_key', '=', 'WECHAT_PAY_CONFIG']]);
        if (!empty($res['data']['value'])) {
            $res['data']['value']['api_type'] = $res['data']['value']['api_type'] ?? 'v2';
            $res['data']['value']['transfer_type'] = $res['data']['value']['transfer_type'] ?? 'v2';
        }
        return $res;
    }
}