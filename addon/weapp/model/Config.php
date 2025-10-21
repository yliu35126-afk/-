<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\weapp\model;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;

/**
 * 微信小程序配置
 */
class Config extends BaseModel
{
    /******************************************************************** 微信小程序配置 start ****************************************************************************/
    /**
     * 设置微信小程序配置
     * @return multitype:string mixed
     */
    public function setWeappConfig($data, $is_use)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '微信公小程序设置', $is_use, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'WEAPP_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取微信小程序配置信息
     * @return multitype:string mixed
     */
    public function getWeappConfig($site_id = 0)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'WEAPP_CONFIG' ] ]);
        return $res;
    }
    /******************************************************************** 微信小程序配置 end ****************************************************************************/

}