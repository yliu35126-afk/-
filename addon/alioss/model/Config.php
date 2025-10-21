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

namespace addon\alioss\model;

use app\model\BaseModel;
use app\model\system\Config as ConfigModel;

/**
 * 阿里云配置
 */
class Config extends BaseModel
{
    /**
     * 设置阿里云OSS上传配置
     * array $data
     */
    public function setAliossConfig($data, $status)
    {
        if ($status == 1) {
            event("CloseOss", []);//同步关闭所有云上传
        }

        $config = new ConfigModel();
        $res = $config->setConfig($data, '阿里云OSS上传配置', $status, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'ALIOSS_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取阿里云上传配置
     */
    public function getAliossConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'ALIOSS_CONFIG' ] ]);
        return $res;
    }

    /**
     * 配置阿里云开关状态
     * @param $status
     */
    public function modifyConfigIsUse($status)
    {
        $config = new ConfigModel();
        $res = $config->modifyConfigIsUse($status, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'ALIOSS_CONFIG' ] ]);
        return $res;
    }
}