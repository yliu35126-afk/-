<?php
namespace app\api\controller;

use think\facade\Db;

/**
 * WS_CONFIG 配置读取/写入（便于联调：DB优先，环境次之）
 */
class WsConfig extends BaseApi
{
    /** 获取配置（DB>env>defaults） */
    public function get()
    {
        $conf = [];
        try {
            $row = Db::name('ws_config')->where('id', 1)->find();
            if (!empty($row)) {
                $conf = [
                    'WS_PORT' => intval($row['ws_port'] ?? 0),
                    'API_BASE_URL' => strval($row['api_base_url'] ?? ''),
                    'WS_SECRET' => strval($row['ws_secret'] ?? ''),
                    'WS_TOKEN' => strval($row['ws_token'] ?? ''),
                    'WS_DEVICE_GROUP' => strval($row['ws_device_group'] ?? ''),
                ];
            }
        } catch (\Throwable $e) {}

        // 环境变量兜底
        if (empty($conf) || empty($conf['WS_PORT'])) {
            $conf['WS_PORT'] = intval(getenv('WS_PORT') ?: 0) ?: 9502;
        }
        if (empty($conf['API_BASE_URL'])) {
            $conf['API_BASE_URL'] = strval(getenv('API_BASE_URL') ?: 'http://127.0.0.1');
        }
        foreach (['WS_SECRET','WS_TOKEN','WS_DEVICE_GROUP'] as $k) {
            if (empty($conf[$k])) $conf[$k] = strval(getenv($k) ?: '');
        }

        return $this->response($this->success(['config' => $conf]));
    }

    /** 更新配置（写DB），需要管理权限 */
    public function set()
    {
        $ws_port = intval($this->params['WS_PORT'] ?? $this->params['ws_port'] ?? 0);
        $api_base = strval($this->params['API_BASE_URL'] ?? $this->params['api_base_url'] ?? '');
        $ws_secret = strval($this->params['WS_SECRET'] ?? $this->params['ws_secret'] ?? '');
        $ws_token = strval($this->params['WS_TOKEN'] ?? $this->params['ws_token'] ?? '');
        $ws_device_group = strval($this->params['WS_DEVICE_GROUP'] ?? $this->params['ws_device_group'] ?? '');

        $data = [
            'ws_port' => $ws_port,
            'api_base_url' => $api_base,
            'ws_secret' => $ws_secret,
            'ws_token' => $ws_token,
            'ws_device_group' => $ws_device_group,
            'update_time' => time(),
        ];
        try {
            $exists = Db::name('ws_config')->where('id', 1)->find();
            if (empty($exists)) {
                $data['id'] = 1;
                $data['create_time'] = time();
                Db::name('ws_config')->insert($data);
            } else {
                Db::name('ws_config')->where('id', 1)->update($data);
            }
            return $this->response($this->success(['code' => 0]));
        } catch (\Throwable $e) {
            return $this->response($this->error('', 'DB_WRITE_FAILED'));
        }
    }
}