<?php
namespace app\admin\controller;

use think\facade\Db;

class WsConfig extends BaseAdmin
{
    public function index()
    {
        if (request()->isAjax()) {
            $ws_port = intval(input('WS_PORT', input('ws_port', 0)));
            $api_base = strval(input('API_BASE_URL', input('api_base_url', '')));
            $ws_secret = strval(input('WS_SECRET', input('ws_secret', '')));
            $ws_token = strval(input('WS_TOKEN', input('ws_token', '')));
            $ws_device_group = strval(input('WS_DEVICE_GROUP', input('ws_device_group', '')));

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
                return json(success(['code' => 0]));
            } catch (\Throwable $e) {
                return json(error('-1', 'DB_WRITE_FAILED'));
            }
        } else {
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

            if (empty($conf) || empty($conf['WS_PORT'])) { $conf['WS_PORT'] = intval(getenv('WS_PORT') ?: 9502); }
            if (empty($conf['API_BASE_URL'])) { $conf['API_BASE_URL'] = strval(getenv('API_BASE_URL') ?: 'http://127.0.0.1'); }
            foreach (['WS_SECRET','WS_TOKEN','WS_DEVICE_GROUP'] as $k) { if (empty($conf[$k])) $conf[$k] = strval(getenv($k) ?: ''); }

            $env = [
                'WS_PORT' => intval(getenv('WS_PORT') ?: 0),
                'API_BASE_URL' => strval(getenv('API_BASE_URL') ?: ''),
                'WS_SECRET' => strval(getenv('WS_SECRET') ?: ''),
                'WS_TOKEN' => strval(getenv('WS_TOKEN') ?: ''),
                'WS_DEVICE_GROUP' => strval(getenv('WS_DEVICE_GROUP') ?: ''),
            ];

            $this->assign('conf', $conf);
            $this->assign('env', $env);
            return $this->fetch('wsconfig/index');
        }
    }
}