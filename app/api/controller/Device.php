<?php
namespace app\api\controller;

use app\api\controller\BaseApi;
use think\facade\Db;
use GatewayClient\Gateway;
use think\facade\Config as FacadeConfig;

class Device extends BaseApi
{
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
     * 开始抽奖（智能抽奖入口）：向设备推送 start_lottery 指令
     * 请求参数：device_id（或 device_sn）
     * 返回：{ code, data:{ status:'success', message } }
     */
    public function smartLottery()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

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

        // 推送 start_lottery
        try {
            FacadeConfig::load(__DIR__ . '/../../../addon/servicer/config/gateway_client.php');
            Gateway::$registerAddress = @config()['register_address'];
            $payload = [
                'cmd' => 'start_lottery',
                'device_id' => intval($device['device_id']),
                'ts' => time(),
            ];
            Gateway::sendToUid('ns_device_' . intval($device['device_id']), json_encode($payload, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // 忽略推送异常
        }

        return $this->response($this->success([ 'status' => 'success', 'message' => 'Lottery started' ]));
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
}