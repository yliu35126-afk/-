<?php
namespace app\api\controller;

use app\api\controller\BaseApi;
use think\facade\Db;

class Lottery extends BaseApi
{
    public function result()
    {
        $token = $this->checkToken();
        $member_id = ($token['code'] == 0) ? intval($this->member_id) : 0;

        $device_id = $this->params['device_id'] ?? '';
        $grid_no   = intval($this->params['grid_no'] ?? 0);
        $result    = strtolower(trim($this->params['result'] ?? 'miss'));
        $ts        = intval($this->params['ts'] ?? time());
        $sign      = strval($this->params['sign'] ?? '');
        if (!$device_id || $grid_no < 1 || $grid_no > 16) {
            return $this->response($this->error('', '参数不完整'));
        }
        if (!in_array($result, ['win','lose','miss'])) $result = 'miss';
        if (!$ts || !$sign) {
            return $this->response($this->error('', '缺少签名或时间戳'));
        }
        if (abs(time() - $ts) > 180) {
            return $this->response($this->error('', '时间戳过期'));
        }

        // 查设备
        $device = null;
        if (is_numeric($device_id)) {
            $device = Db::name('device_info')->where('device_id', intval($device_id))->find();
        } else {
            $device = Db::name('device_info')->where('device_sn', strval($device_id))->find();
        }
        if (empty($device)) return $this->response($this->error('', '设备不存在'));
        $device_id_num = intval($device['device_id']);
        $site_id = intval($device['site_id'] ?? 0);
        $board_id = intval($device['board_id'] ?? 0);
        if ($board_id <= 0) return $this->response($this->error('', '设备未绑定抽奖盘'));

        // 签名校验：HMAC-SHA256(device_id|grid_no|result|ts, device_secret)
        $device_secret = strval($device['device_secret'] ?? '');
        if ($device_secret === '') {
            return $this->response($this->error('', '设备未配置密钥'));
        }
        $payload = $device_id_num . '|' . $grid_no . '|' . $result . '|' . $ts;
        $calc = hash_hmac('sha256', $payload, $device_secret);
        if (!hash_equals($calc, $sign)) {
            return $this->response($this->error('', '签名校验失败'));
        }

        // 定位槽位（position 从0开始）
        $slot = Db::name('lottery_slot')
            ->where([['board_id', '=', $board_id], ['position', '=', $grid_no - 1]])
            ->find();
        if (empty($slot)) return $this->response($this->error('', '格子不存在'));

        // 读取设备概率配置
        $prob_mode = strtolower($device['prob_mode'] ?? 'round');
        $auto_reset = intval($device['auto_reset'] ?? 0) ? 1 : 0;
        $prob = [];
        try { $prob = json_decode($device['prob_json'] ?? '{}', true); } catch (\Throwable $e) { $prob = []; }
        $grids = is_array($prob['grids'] ?? null) ? $prob['grids'] : [];
        $round_no = intval($prob['round_counter'] ?? 1);

        // 仅当中奖时扣减该格的轮次库存
        foreach ($grids as &$g) {
            if (intval($g['no'] ?? 0) === $grid_no) {
                if ($result === 'win') {
                    $inv = intval($g['inventory'] ?? 0);
                    if ($inv > 0) $g['inventory'] = $inv - 1;
                }
                break;
            }
        }
        unset($g);

        // 统计扣减后的库存总和并生成快照（扣减后快照，用于审计）
        $snapshot = [];
        $sum = 0; foreach ($grids as $g2) { $snapshot[intval($g2['no'] ?? 0)] = intval($g2['inventory'] ?? 0); $sum += intval($g2['inventory'] ?? 0); }

        $reset_performed = 0;
        $new_round_no = $round_no;
        if ($prob_mode === 'round' && $auto_reset === 1 && $sum <= 0) {
            // 加载初始库存（来自 device_probability_grid.inventory_override）
            $overrides = Db::name('device_probability_grid')
                ->where('device_id', $device_id_num)
                ->column('inventory_override', 'grid_no');
            foreach ($grids as &$g3) {
                $no = intval($g3['no'] ?? 0);
                $init_inv = intval($overrides[$no] ?? 0);
                $g3['inventory'] = $init_inv;
            }
            unset($g3);
            $prob['round_counter'] = $round_no + 1;
            $prob['last_reset_ts'] = time();
            $reset_performed = 1;
            $new_round_no = $prob['round_counter'];
        }
        // 写回设备概率配置
        $prob['grids'] = $grids;
        Db::name('device_info')
            ->where('device_id', $device_id_num)
            ->update([
                'prob_json' => json_encode($prob, JSON_UNESCAPED_UNICODE),
            ]);

        // 记录抽奖（带轮次号与扣减后库存快照；若发生重置，round_no记录重置前的轮次）
        $record = [
            'member_id' => $member_id,
            'device_id' => $device_id_num,
            'board_id'  => $board_id,
            'slot_id'   => intval($slot['slot_id']),
            'prize_type'=> strval($slot['prize_type'] ?? 'thanks'),
            'goods_id'  => intval($slot['goods_id'] ?? 0),
            'tier_id'   => 0,
            'amount'    => 0,
            'round_no'  => $round_no,
            'result'    => ($result === 'win') ? 'hit' : 'miss',
            'order_id'  => 0,
            'inv_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'ext'       => json_encode(['from' => 'device_callback', 'ts' => $ts, 'reset_performed' => $reset_performed, 'new_round_no' => $new_round_no], JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
        ];
        $record_id = Db::name('lottery_record')->insertGetId($record);

        return $this->response($this->success(['record_id' => $record_id, 'grid_no' => $grid_no, 'result' => $result, 'round_no' => $round_no, 'reset_performed' => $reset_performed]));
    }

    /**
     * 核销二维码：商家或用户扫码后核销并触发分润
     * 请求：order_id, verify_code
     */
    public function verify()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $order_id = intval($this->params['order_id'] ?? 0);
        $verify_code = trim($this->params['verify_code'] ?? '');
        if ($order_id <= 0 || !$verify_code) return $this->response($this->error('', '参数不完整'));

        $records = Db::name('lottery_record')->where('order_id', $order_id)->select()->toArray();
        if (empty($records)) return $this->response($this->error('', '订单无抽奖记录'));

        foreach ($records as $r) {
            $ext = json_decode($r['ext'] ?? '{}', true);
            $ext['order'] = [ 'status' => 'verified', 'verify_code' => $verify_code, 'verify_time' => time() ];
            Db::name('lottery_record')->where('record_id', intval($r['record_id']))->update([
                'ext' => json_encode($ext, JSON_UNESCAPED_UNICODE),
            ]);
        }

        // 触发分润：按订单关联记录金额聚合，写入 ns_lottery_profit（示例：merchant100%）
        $total = 0; $rid = 0;
        foreach ($records as $r) { $total += floatval($r['amount'] ?? 0); $rid = intval($r['record_id']); }
        if ($total > 0) {
            Db::name('ns_lottery_profit')->insert([
                'order_id' => $order_id,
                'record_id'=> $rid,
                'role'     => 'merchant',
                'target_id'=> intval($token['data']['member_id'] ?? $this->member_id ?? 0),
                'amount'   => round($total, 2),
                'status'   => 'settled',
                'settle_time' => time(),
                'create_time' => time(),
            ]);
        }

        return $this->response($this->success(['status' => 'success', 'message' => 'Verification completed']));
    }

    /**
     * 扫码核销：同 verify
     */
    public function scan()
    {
        return $this->verify();
    }

    /**
     * 分润发放：根据订单计算并写入分润明细
     * 请求：order_id
     */
    public function distribute()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);
        $order_id = intval($this->params['order_id'] ?? 0);
        if ($order_id <= 0) return $this->response($this->error('', '缺少 order_id'));

        $records = Db::name('lottery_record')->where('order_id', $order_id)->select()->toArray();
        if (empty($records)) return $this->response($this->error('', '订单无抽奖记录'));
        $total = 0; $rid = 0;
        foreach ($records as $r) { $total += floatval($r['amount'] ?? 0); $rid = intval($r['record_id']); }
        if ($total > 0) {
            // 示例：平台5%，商家95%
            $platform = round($total * 0.05, 2);
            $merchant = round($total - $platform, 2);
            Db::name('ns_lottery_profit')->insertAll([
                [
                    'order_id' => $order_id,
                    'record_id'=> $rid,
                    'role'     => 'platform',
                    'target_id'=> 0,
                    'amount'   => $platform,
                    'status'   => 'settled',
                    'settle_time' => time(),
                    'create_time' => time(),
                ],
                [
                    'order_id' => $order_id,
                    'record_id'=> $rid,
                    'role'     => 'merchant',
                    'target_id'=> intval($token['data']['member_id'] ?? $this->member_id ?? 0),
                    'amount'   => $merchant,
                    'status'   => 'settled',
                    'settle_time' => time(),
                    'create_time' => time(),
                ]
            ]);
        }
        return $this->response($this->success(['status' => 'success', 'message' => 'Profit distributed']));
    }
}