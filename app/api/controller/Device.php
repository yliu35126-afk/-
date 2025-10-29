<?php
namespace app\api\controller;

use app\api\controller\BaseApi;
use think\facade\Db;
use GatewayClient\Gateway;
use think\facade\Config as FacadeConfig;

class Device extends BaseApi
{
    /**
     * 设备注册：绑定 device_id 与 key（device_secret），并初始化默认盘/价档
     * 入参：device_id（必填）、key（必填）
     * 可选：device_name、device_type
     */
    public function register()
    {
        // 基础参数校验
        $device_id_raw = $this->params['device_id'] ?? '';
        $key = strval($this->params['key'] ?? '');
        $device_name = strval($this->params['device_name'] ?? '');
        $device_type = strval($this->params['device_type'] ?? '');
        if (!$device_id_raw || !$key) {
            return $this->response($this->error('', '缺少必填参数：device_id 或 key'));
        }

        // 设备查询：支持纯数字 device_id 与字符串 device_sn
        $device = null;
        if (is_numeric($device_id_raw)) {
            $device = Db::name('device_info')->where('device_id', intval($device_id_raw))->find();
        } else {
            $device = Db::name('device_info')->where('device_sn', strval($device_id_raw))->find();
        }

        $now = time();
        if (empty($device)) {
            // 若设备不存在，按传入的 device_id_raw 自动创建（device_sn 走字符串，数字走主键创建失败，这里以 device_sn 方式兜底）
            $record = [
                'device_sn'   => is_numeric($device_id_raw) ? ('SN-' . strval($device_id_raw)) : strval($device_id_raw),
                'site_id'     => 0,
                'owner_id'    => 0,
                'installer_id'=> 0,
                'supplier_id' => 0,
                'promoter_id' => 0,
                'agent_code'  => '',
                'board_id'    => 0,
                'status'      => 1,
                'prob_mode'   => 'round',
                'prob_json'   => null,
                'auto_reset'  => 0,
                'device_secret' => $key,
                'create_time' => $now,
                'update_time' => $now,
            ];
            try {
                $new_id = Db::name('device_info')->insertGetId($record);
                $device = Db::name('device_info')->where('device_id', $new_id)->find();
            } catch (\Throwable $e) {
                return $this->response($this->error('', '设备创建失败：' . $e->getMessage()));
            }
        } else {
            // 已存在：校验/绑定秘钥
            $secret = strval($device['device_secret'] ?? '');
            if ($secret === '') {
                // 首次绑定秘钥
                Db::name('device_info')->where('device_id', intval($device['device_id']))->update([
                    'device_secret' => $key,
                    'update_time'   => $now,
                ]);
                $device['device_secret'] = $key;
            } elseif (!hash_equals($secret, $key)) {
                return $this->response($this->error('', '设备秘钥不匹配'));
            }
        }

        $device_id = intval($device['device_id']);
        if ($device_name !== '' || $device_type !== '') {
            // 轻量更新名称/类型字段（若表无此字段则忽略）
            try {
                $upd = [ 'update_time' => $now ];
                if ($device_name !== '') $upd['device_name'] = $device_name;
                if ($device_type !== '') $upd['device_type'] = $device_type;
                Db::name('device_info')->where('device_id', $device_id)->update($upd);
            } catch (\Throwable $e) { /* ignore */ }
        }

        // 默认盘绑定：若未绑定 board_id，则绑定到演示抽奖盘（board_id=1）
        $board_id = intval($device['board_id'] ?? 0);
        if ($board_id <= 0) {
            $default_board = Db::name('lottery_board')->where('board_id', 1)->find();
            if (empty($default_board)) {
                return $this->response($this->error('', '未找到默认抽奖盘，请先初始化抽奖盘'));
            }
            Db::name('device_info')->where('device_id', $device_id)->update([
                'board_id' => 1,
                'update_time' => $now,
            ]);
            $board_id = 1;
        }

        // 默认价档绑定：若无有效绑定则绑定默认价档（tier_id=1，长期有效）
        $bind = Db::name('device_price_bind')->where([
            ['device_id', '=', $device_id],
            ['status', '=', 1]
        ])->order('start_time desc')->find();
        $tier_id = intval($bind['tier_id'] ?? 0);
        if ($tier_id <= 0) {
            $default_tier = Db::name('lottery_price_tier')->where('tier_id', 1)->find();
            if (empty($default_tier)) {
                return $this->response($this->error('', '未找到默认价档，请先初始化价档'));
            }
            Db::name('device_price_bind')->insert([
                'device_id'  => $device_id,
                'tier_id'    => 1,
                'status'     => 1,
                'start_time' => $now,
                'end_time'   => 0,
                'create_time'=> $now,
                'update_time'=> $now,
            ]);
            $tier_id = 1;
        }

        return $this->response($this->success([
            'device_id' => $device_id,
            'board_id'  => $board_id,
            'tier_id'   => $tier_id,
            'message'   => '设备注册成功并完成默认配置绑定'
        ]));
    }

    public function probabilityUpdate()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $device_id = $this->params['device_id'] ?? '';
        $mode      = strtolower(trim($this->params['mode'] ?? 'round'));
        $auto_reset = intval($this->params['auto_reset'] ?? 0) ? 1 : 0;
        $grids     = $this->params['grids'] ?? [];

        if (!$device_id || empty($grids) || !in_array($mode, ['round','live'])) {
            return $this->response($this->error('', '参数不完整或模式不合法'));
        }

        $device = null;
        if (is_numeric($device_id)) {
            $device = Db::name('device_info')->where('device_id', intval($device_id))->find();
        } else {
            $device = Db::name('device_info')->where('device_sn', strval($device_id))->find();
        }
        if (empty($device)) return $this->response($this->error('', '设备不存在'));

        $board_id = intval($device['board_id'] ?? 0);
        if ($board_id <= 0) return $this->response($this->error('', '设备未绑定抽奖盘'));

        $normalized = [];
        foreach ($grids as $g) {
            $no = intval($g['no'] ?? 0);
            if ($no < 1 || $no > 16) continue;
            $normalized[] = [
                'no' => $no,
                'weight' => intval($g['weight'] ?? 0),
                'inventory' => intval($g['inventory'] ?? 0),
                'allow_fallback' => intval($g['allow_fallback'] ?? 0) ? 1 : 0,
            ];
        }
        if (empty($normalized)) return $this->response($this->error('', '无有效格数据'));

        $prob_json = json_encode([
            'mode' => $mode,
            'auto_reset' => $auto_reset,
            // 轮次计数器，如果未设置，从1开始
            'round_counter' => intval(($device['prob_json'] ? (json_decode($device['prob_json'], true)['round_counter'] ?? 1) : 1)),
            'grids' => $normalized
        ], JSON_UNESCAPED_UNICODE);

        Db::name('device_info')
            ->where('device_id', intval($device['device_id']))
            ->update([
                'prob_mode' => $mode,
                'auto_reset' => $auto_reset,
                'prob_json' => $prob_json,
            ]);

        foreach ($normalized as $g) {
            $slot = Db::name('lottery_slot')
                ->where([['board_id', '=', $board_id], ['position', '=', $g['no'] - 1]])
                ->field('slot_id')
                ->find();
            $slot_id = intval($slot['slot_id'] ?? 0);
            $existing = Db::name('device_probability_grid')
                ->where([['device_id', '=', intval($device['device_id'])], ['grid_no', '=', $g['no']]])
                ->field('id')
                ->find();
            $data = [
                'device_id' => intval($device['device_id']),
                'board_id' => $board_id,
                'grid_no' => $g['no'],
                'slot_id' => $slot_id,
                'weight' => $g['weight'],
                'allow_fallback' => $g['allow_fallback'],
                'inventory_override' => intval($g['inventory'] ?? 0),
                'inventory_current' => intval($g['inventory'] ?? 0),
                'update_time' => time(),
            ];
            if ($existing) {
                Db::name('device_probability_grid')->where('id', intval($existing['id']))->update($data);
            } else {
                $data['create_time'] = time();
                Db::name('device_probability_grid')->insert($data);
            }
        }

        // WebSocket 推送：cmd=update_probability 到设备通道
        try {
            FacadeConfig::load(__DIR__ . '/../../../addon/servicer/config/gateway_client.php');
            Gateway::$registerAddress = @config()['register_address'];
            $payload = [
                'cmd' => 'update_probability',
                'device_id' => intval($device['device_id']),
                'mode' => $mode,
                'auto_reset' => $auto_reset,
                'grids' => $normalized,
            ];
            // UID 约定：ns_device_{device_id}
            Gateway::sendToUid('ns_device_' . intval($device['device_id']), json_encode($payload, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // 忽略推送异常，返回成功
        }

        return $this->response($this->success(['device_id' => intval($device['device_id'])]));
    }

    /**
     * 查询设备关联的分润奖品（基于设备盘的有效奖品）
     * 请求参数：device_id（或 device_sn）
     * 返回：[{ prize_id, name }]
     */
    public function profitprizes()
    {
        $device_id = $this->params['device_id'] ?? '';
        if (!$device_id) return $this->response($this->error('', '缺少 device_id'));

        $device = null;
        if (is_numeric($device_id)) {
            $device = Db::name('device_info')->where('device_id', intval($device_id))->find();
        } else {
            $device = Db::name('device_info')->where('device_sn', strval($device_id))->find();
        }
        if (empty($device)) return $this->response($this->error('', '设备不存在'));

        $board_id = intval($device['board_id'] ?? 0);
        if ($board_id <= 0) return $this->response($this->error('', '设备未绑定抽奖盘'));

        $slots = Db::name('lottery_slot')->where('board_id', $board_id)->order('position asc')->select()->toArray();
        $items = [];
        foreach ($slots as $s) {
            $type = strtolower($s['prize_type'] ?? 'thanks');
            if ($type === 'thanks') continue;
            $items[] = [
                'prize_id' => intval($s['slot_id'] ?? 0),
                'name' => strval($s['prize_name'] ?? ''),
            ];
        }
        return $this->response($this->success([ 'list' => $items ]));
    }

    /**
     * 设备自检：验证数据库连通性、设备是否存在、默认盘与价档绑定情况
     * 入参：device_id（或 device_sn，可选）
     * 返回：{ db: {ok}, device: {...}, board: {...}, tier: {...} }
     */
    public function selfcheck()
    {
        $out = [ 'db' => ['ok' => false], 'device' => [], 'board' => [], 'tier' => [], 'ws' => [] ];
        // 数据库连通性检查
        try {
            Db::query('SELECT 1');
            $out['db']['ok'] = true;
        } catch (\Throwable $e) {
            $out['db'] = ['ok' => false, 'error' => $e->getMessage()];
            return $this->response($this->success($out));
        }

        // 可选：WebSocket /health 检测
        $check_ws = intval($this->params['check_ws'] ?? 0) === 1;
        if ($check_ws) {
            $ws_host = getenv('WS_HOST');
            $ws_port = getenv('WS_PORT');
            if (empty($ws_host)) $ws_host = '127.0.0.1';
            if (empty($ws_port)) $ws_port = '3001';
            $ws_url = sprintf('http://%s:%s/health', $ws_host, $ws_port);
            $out['ws'] = ['ok' => false, 'url' => $ws_url];
            try {
                $resp = null;
                if (function_exists('curl_init')) {
                    $ch = curl_init($ws_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    $resp = curl_exec($ch);
                    if ($resp === false) {
                        $out['ws']['error'] = curl_error($ch);
                    }
                    curl_close($ch);
                } else {
                    $ctx = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'timeout' => 2,
                        ],
                    ]);
                    $resp = @file_get_contents($ws_url, false, $ctx);
                }
                if (!empty($resp)) {
                    $json = json_decode($resp, true);
                    if (is_array($json)) {
                        $out['ws'] = ['ok' => true, 'url' => $ws_url, 'data' => $json];
                    } else {
                        $out['ws']['error'] = 'Invalid JSON';
                    }
                } elseif (!isset($out['ws']['error'])) {
                    $out['ws']['error'] = 'No response';
                }
            } catch (\Throwable $e) {
                $out['ws']['error'] = $e->getMessage();
            }
        }

        $device_id = $this->params['device_id'] ?? '';
        if (!$device_id) {
            // 未提供设备标识，只返回DB检查结果
            return $this->response($this->success($out));
        }

        // 设备查询：支持数字ID或字符串SN
        $device = null;
        if (is_numeric($device_id)) {
            $device = Db::name('device_info')->where('device_id', intval($device_id))->find();
        } else {
            $device = Db::name('device_info')->where('device_sn', strval($device_id))->find();
        }
        if (empty($device)) {
            $out['device'] = ['ok' => false, 'error' => '设备不存在'];
            return $this->response($this->success($out));
        }
        $out['device'] = [
            'ok' => true,
            'device_id' => intval($device['device_id']),
            'device_sn' => strval($device['device_sn'] ?? ''),
            'board_id' => intval($device['board_id'] ?? 0),
            'site_id' => intval($device['site_id'] ?? 0),
        ];

        // 盘绑定与存在性
        $board_id = intval($device['board_id'] ?? 0);
        if ($board_id <= 0) {
            $out['board'] = ['ok' => false, 'error' => '设备未绑定抽奖盘'];
        } else {
            $board_info = Db::name('lottery_board')->where('board_id', $board_id)->find();
            if (empty($board_info)) {
                $out['board'] = ['ok' => false, 'board_id' => $board_id, 'error' => '抽奖盘不存在'];
            } else {
                $slots_cnt = Db::name('lottery_slot')->where('board_id', $board_id)->count();
                $out['board'] = ['ok' => true, 'board_id' => $board_id, 'slots' => intval($slots_cnt)];
            }
        }

        // 价档绑定检查（选择当前有效或长期有效绑定）
        $tier_out = ['ok' => false];
        try {
            $now = time();
            $bind = Db::name('device_price_bind')->where([
                ['device_id', '=', intval($device['device_id'])],
                ['status', '=', 1],
                ['start_time', '<=', $now],
            ])->order('create_time desc')->find();
            if (empty($bind)) {
                // 其次查长期有效（end_time=0）
                $bind = Db::name('device_price_bind')->where([
                    ['device_id', '=', intval($device['device_id'])],
                    ['status', '=', 1],
                    ['end_time', '=', 0],
                ])->order('create_time desc')->find();
            }
            $tier_id = intval($bind['tier_id'] ?? 0);
            if ($tier_id > 0) {
                $tier = Db::name('lottery_price_tier')->where('tier_id', $tier_id)->find();
                if (!empty($tier)) {
                    $tier_out = ['ok' => true, 'tier_id' => $tier_id, 'price' => floatval($tier['price'] ?? 0)];
                } else {
                    $tier_out = ['ok' => false, 'tier_id' => $tier_id, 'error' => '价档不存在'];
                }
            } else {
                $tier_out = ['ok' => false, 'error' => '未找到有效价档绑定'];
            }
        } catch (\Throwable $e) {
            $tier_out = ['ok' => false, 'error' => $e->getMessage()];
        }
        $out['tier'] = $tier_out;

        return $this->response($this->success($out));
    }
}