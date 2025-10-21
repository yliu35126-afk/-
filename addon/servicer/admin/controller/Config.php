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

namespace addon\servicer\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\servicer\model\Config as ConfigModel;

/**
 * 客服配置
 */
class Config extends BaseAdmin
{
    /**
     * 客服配置
     */
    public function config()
    {
        $config_model = new ConfigModel();
        if (request()->isAjax()) {
            $web_socket = input("web_socket");

            $data = [
                'web_socket' => $web_socket,//websocket链接
            ];
            $this->addLog("设置客服配置");
            $res = $config_model->setConfig($data);
            return $res;
        } else {
            $config_result = $config_model->getConfig()[ 'data' ] ?? [];
            $this->assign('config', $config_result[ 'value' ] ?? '');
            return $this->fetch('config/config');
        }
    }

}
