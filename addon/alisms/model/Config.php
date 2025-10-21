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

namespace addon\alisms\model;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;

/**
 * 支付宝支付配置
 */
class Config extends BaseModel
{
	/**
	 * 设置短信配置
	 * array $data
	 */
    public function setSmsConfig($data, $is_use)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '阿里云短信配置', $is_use, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'ALI_SMS_CONFIG' ] ]);
        event('EnableCallBack', [ 'sms_type' => 'alisms', 'is_use' => $is_use ]);
        return $res;
    }
	
	/**
	 * 获取短信配置
	 */
	public function getSmsConfig()
	{
		$config = new ConfigModel();
		$res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'ALI_SMS_CONFIG' ] ]);
		return $res;
	}

    /**
     * 设置开关
     */
    public function modifyConfigIsUse($is_use, $app_module = 'admin')
    {
        $config = new ConfigModel();
        $res = $config->modifyConfigIsUse($is_use, [ ['site_id','=',0],[ 'app_module', '=', $app_module ], [ 'config_key', '=', 'ALI_SMS_CONFIG' ] ]);
        event('EnableCallBack', [ 'sms_type' => 'alisms', 'is_use' => $is_use ]);
        return $res;
    }

    /**
     * 事件修改开关状态
     */
    public function enableCallBack($is_use, $app_module = 'admin')
    {
        $config = new ConfigModel();
        $res = $config->modifyConfigIsUse($is_use, [ ['site_id','=',0], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'ALI_SMS_CONFIG' ] ]);
        return $res;
    }
}