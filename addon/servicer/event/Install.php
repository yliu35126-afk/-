<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\servicer\event;

/**
 * 应用安装
 */
class Install
{
    /**
     * 执行安装
     */
    public function handle()
    {
        $source_file = './' . ADDON_PATH . 'servicer/' . 'config/source/Events.php';
        $target_file  = './' . ADDON_PATH . 'servicer/gateway/Applications/Service/Events.php';

        // 读取配置文件
        $fp     = fopen($source_file, 'r');
        $config = fread($fp, filesize($source_file));
        fclose($fp);

        // 替换内容
        $database_config = config('database');
        $config          = str_replace('model_hostname', $database_config['connections']['mysql']['hostname'], $config);
        $config          = str_replace('model_database', $database_config['connections']['mysql']['database'], $config);
        $config          = str_replace('model_username', $database_config['connections']['mysql']['username'], $config);
        $config          = str_replace('model_password', $database_config['connections']['mysql']['password'], $config);
        $config          = str_replace('model_port', $database_config['connections']['mysql']['hostport'], $config);
        $config          = str_replace('model_prefix', $database_config['connections']['mysql']['prefix'], $config);

        // 检测文件是否可写
        $fp = fopen($target_file, 'w');
        if ($fp == false) {
            return error(-1, '写入配置失败，请检查' . $target_file . '是否可写入！');
        }

        fwrite($fp, $config);
        fclose($fp);

        return success();
    }
}
