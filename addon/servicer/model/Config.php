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

namespace addon\servicer\model;

use app\model\BaseModel;
use app\model\system\Config as ConfigModel;

class Config extends BaseModel
{
    /**
     * 客服配置
     * array $data
     */
    public function setConfig($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '客服设置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'SERVICER_CONFIG' ] ]);
        return $res;
    }

    /**
     * 客服配置
     */
    public function getConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'SERVICER_CONFIG' ] ]);
        if (empty($res['data']['value'])) {
            $socket_url = (get_http_type() === 'http' ? str_replace('http', 'ws', __ROOT__) : str_replace('https', 'wss', __ROOT__)) . '/wss';
            $res['data']['value'] = [
                // 客服类型
                'web_socket'   => $socket_url
            ];
        }
        return $res;
    }
}
