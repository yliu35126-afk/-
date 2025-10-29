<?php
namespace app\api\controller;

use app\api\controller\BaseApi;
use think\facade\Db;
use think\facade\Log;

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

    public function callback()
    {
        // 与 WebSocket 服务对接的回调，无需登录态
        $device_id = $this->params['device_id'] ?? '';
        $order_id = intval($this->params['order_id'] ?? 0);
        $user_id = intval($this->params['user_id'] ?? 0);
        $lottery_record_id = intval($this->params['lottery_record_id'] ?? 0);
        $prize_id = intval($this->params['prize_id'] ?? 0);
        $prize_name = strval($this->params['prize_name'] ?? '');
        $amount = floatval($this->params['amount'] ?? 0);
        $result = strtolower(trim($this->params['result'] ?? 'error'));

        if (!$device_id) return $this->response($this->error('', '缺少 device_id'));
        if (!in_array($result, ['win', 'lose', 'miss', 'error'])) {
            $result = 'error';
        }

        // 可选：回调签名校验（通过环境变量 SOCKET_CALLBACK_SECRET 开启）
        $secret = getenv('SOCKET_CALLBACK_SECRET') ?: '';
        if ($secret !== '') {
            $cbTs = intval($this->params['ts'] ?? 0);
            $cbSig = strval($this->params['sign'] ?? '');
            if ($cbTs <= 0 || abs(time() - $cbTs) > 180 || $cbSig === '') {
                return $this->response($this->error('', '签名缺失或过期'));
            }
            $raw = (is_numeric($device_id) ? intval($device_id) : strval($device_id)) . '|' . $order_id . '|' . $lottery_record_id . '|' . $result . '|' . $cbTs;
            $calc = hash_hmac('sha256', $raw, $secret);
            if (!hash_equals($calc, $cbSig)) {
                return $this->response($this->error('', '签名校验失败'));
            }
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
        $board_id = intval($device['board_id'] ?? 0);

        // 构建抽奖记录（最小必需字段），与 WebSocket 本地落库结构保持一致
        $record = [
            'member_id' => $user_id,
            'device_id' => $device_id_num,
            'board_id'  => $board_id,
            'slot_id'   => $prize_id ?: 0,
            'prize_type'=> $prize_id > 0 ? 'goods' : 'thanks',
            'goods_id'  => $prize_id ?: 0,
            'tier_id'   => 0,
            'amount'    => $amount,
            'round_no'  => 0,
            'result'    => ($result === 'win') ? 'hit' : (($result === 'lose' || $result === 'miss') ? 'miss' : 'error'),
            'order_id'  => $order_id,
            'inv_snapshot' => null,
            'ext'       => json_encode(['from' => 'socket_callback', 'prize_name' => $prize_name], JSON_UNESCAPED_UNICODE),
            'create_time' => time(),
        ];

        if ($lottery_record_id > 0) {
            // 更新既有记录（若前端已生成 record_id）
            try {
                Db::name('lottery_record')->where('record_id', $lottery_record_id)->update($record);
            } catch (\Throwable $e) {
                // 若更新失败，降级为插入
                $lottery_record_id = Db::name('lottery_record')->insertGetId($record);
            }
        } else {
            // 插入新记录
            $lottery_record_id = Db::name('lottery_record')->insertGetId($record);
        }

        // 新增：按设备绑定价档与金额生成分润明细，写入 ns_lottery_profit（初始 pending）
        try {
            if ($result !== 'error') {
                 // 查设备当前生效的价档
                 $bind = Db::name('device_price_bind')
                     ->where([ ['device_id', '=', $device_id_num], ['status', '=', 1] ])
                     ->order('start_time desc')
                     ->find();
                 $tier_id = intval($bind['tier_id'] ?? 0);
                 if ($tier_id > 0) {
                     $tier = Db::name('lottery_price_tier')->where('tier_id', $tier_id)->find();
                     $profit = [];
                     try { $profit = json_decode($tier['profit_json'] ?? '{}', true); } catch (\Throwable $e) { $profit = []; }
                    // 若回调未给金额（未绑定订单），使用价档单价作为分润基数
                    $amt = floatval($amount);
                    if ($amt <= 0) {
                        // 无法确定分润基数则跳过写库（保持 $amt=0，不写入分润）
                    }
                    $getRatio = function($v) {
                        $n = is_numeric($v) ? floatval($v) : 0.0;
                        return $n > 1 ? ($n / 100.0) : max(0.0, $n);
                    };
                     $platform_ratio = $getRatio($profit['platform_percent'] ?? ($profit['platform']['percent'] ?? 0));
                     $supplier_ratio = $getRatio($profit['supplier_percent'] ?? ($profit['supplier']['percent'] ?? 0));
                     $promoter_ratio = $getRatio($profit['promoter_percent'] ?? ($profit['promoter']['percent'] ?? 0));
                     $installer_ratio = $getRatio($profit['installer_percent'] ?? ($profit['installer']['percent'] ?? 0));
                     $owner_ratio     = $getRatio($profit['owner_percent'] ?? ($profit['owner']['percent'] ?? 0));

                     $rows = [];
                     // 角色ID映射（device_info）
                     $supplier_id = intval($device['supplier_id'] ?? 0);
                     $promoter_id = intval($device['promoter_id'] ?? 0);
                     $installer_id = intval($device['installer_id'] ?? 0);
                     $owner_id = intval($device['owner_id'] ?? 0);
                     // 区域ID（如未建字段，容错为0）
                     $province_id = intval($device['province_id'] ?? 0);
                     $city_id = intval($device['city_id'] ?? 0);
                     $district_id = intval($device['district_id'] ?? 0);

                     $now = time();
                     $pushRow = function($role, $targetId, $ratio) use (&$rows, $amt, $order_id, $lottery_record_id, $now) {
                         $money = round($amt * floatval($ratio), 2);
                         if ($money <= 0) return;
                         $rows[] = [
                             'order_id'   => $order_id,
                             'record_id'  => $lottery_record_id,
                             'role'       => $role,
                             'target_id'  => intval($targetId ?? 0),
                             'amount'     => $money,
                             'status'     => 'pending',
                             'settle_time'=> 0,
                             'create_time'=> $now,
                         ];
                     };

                     // 平台可无具体 target_id（用0占位）
                     $pushRow('platform', 0, $platform_ratio);
                     $pushRow('supplier', $supplier_id, $supplier_ratio);
                     $pushRow('promoter', $promoter_id, $promoter_ratio);
                     $pushRow('installer', $installer_id, $installer_ratio);
                     $pushRow('owner', $owner_id, $owner_ratio);

                     // 新增：省/市/区固定金额模板（来自价档）
                     $pushFixed = function($role, $targetId, $money) use (&$rows, $order_id, $lottery_record_id) {
                         $m = round(floatval($money), 2);
                         if ($m <= 0) return;
                         $rows[] = [
                             'order_id'   => $order_id,
                             'record_id'  => $lottery_record_id,
                             'role'       => $role,
                             'target_id'  => intval($targetId ?? 0),
                             'amount'     => $m,
                             'status'     => 'pending',
                             'settle_time'=> 0,
                             'create_time'=> time(),
                         ];
                     };
                     // 兼容不同字段命名
                     $province_fixed = floatval($tier['province_profit'] ?? ($tier['province_fixed'] ?? 0));
                     $city_fixed     = floatval($tier['city_profit'] ?? ($tier['city_fixed'] ?? 0));
                     $district_fixed = floatval($tier['district_profit'] ?? ($tier['district_fixed'] ?? 0));
                     // 区域 → 代理精准映射（找不到归平台，即 target_id=0）
                     if ($province_fixed > 0 && $province_id > 0) {
                         try {
                             $agent = Db::name('ns_agent')->where([
                                 ['level','=',1], ['province_id','=',$province_id], ['status','=',1]
                             ])->order('agent_id asc')->find();
                             $agent_id = intval($agent['agent_id'] ?? 0);
                             $pushFixed('province', $agent_id, $province_fixed);
                         } catch (\Throwable $eag) { $pushFixed('province', 0, $province_fixed); }
                     }
                     if ($city_fixed > 0 && $city_id > 0) {
                         try {
                             $agent = Db::name('ns_agent')->where([
                                 ['level','=',2], ['city_id','=',$city_id], ['status','=',1]
                             ])->order('agent_id asc')->find();
                             $agent_id = intval($agent['agent_id'] ?? 0);
                             $pushFixed('city', $agent_id, $city_fixed);
                         } catch (\Throwable $eag) { $pushFixed('city', 0, $city_fixed); }
                     }
                     if ($district_fixed > 0 && $district_id > 0) {
                         try {
                             $agent = Db::name('ns_agent')->where([
                                 ['level','=',3], ['district_id','=',$district_id], ['status','=',1]
                             ])->order('agent_id asc')->find();
                             $agent_id = intval($agent['agent_id'] ?? 0);
                             $pushFixed('district', $agent_id, $district_fixed);
                         } catch (\Throwable $eag) { $pushFixed('district', 0, $district_fixed); }
                     }

                     if (empty($rows)) {
                         // 分润配置为空或全为0时，兜底：供应商100%；若无供应商则设备所有者100%；仍无则平台100%
                         $fallback_target = 0;
                         $fallback_role = 'platform';
                         if ($supplier_id > 0) { $fallback_role = 'supplier'; $fallback_target = $supplier_id; }
                         elseif ($owner_id > 0) { $fallback_role = 'owner'; $fallback_target = $owner_id; }
                         $rows[] = [
                             'order_id'   => $order_id,
                             'record_id'  => $lottery_record_id,
                             'role'       => $fallback_role,
                             'target_id'  => intval($fallback_target),
                             'amount'     => round($amt, 2),
                             'status'     => 'pending',
                             'settle_time'=> 0,
                             'create_time'=> $now,
                         ];
                     }

                     if (!empty($rows)) {
                         $exists = Db::name('ns_lottery_profit')
                             ->where([ ['order_id', '=', $order_id], ['record_id', '=', $lottery_record_id] ])
                             ->count();
                         if (intval($exists) === 0) {
                             Db::name('ns_lottery_profit')->insertAll($rows);
                             // 同步写入记录扩展：将回调路径的角色分润写入 ext.profit_roles，避免与模型 group_# 冲突
                             try {
                                 $rec = Db::name('lottery_record')->where('record_id', $lottery_record_id)->find();
                                 $ext2 = [];
                                 try { $ext2 = json_decode($rec['ext'] ?? '{}', true); } catch (\Throwable $ejson) { $ext2 = []; }
                                 $byRole2 = [];
                                 foreach ($rows as $r) { $role = strval($r['role'] ?? ''); $byRole2[$role] = ($byRole2[$role] ?? 0) + floatval($r['amount'] ?? 0); }
                                 foreach ($byRole2 as $k => $v) { $byRole2[$k] = round($v, 2); }
                                 $ext2['profit_roles'] = $byRole2;
                                 Db::name('lottery_record')->where('record_id', $lottery_record_id)->update(['ext' => json_encode($ext2, JSON_UNESCAPED_UNICODE)]);
                             } catch (\Throwable $eext) {}
                         }
                     }
                 }
             }
         } catch (\Throwable $e) {
            // 记录异常，避免静默吞掉错误
            try { Log::error('lottery.callback profit error: ' . $e->getMessage()); } catch (\Throwable $e2) {}
        }

        return $this->response($this->success([
            'order_id' => $order_id,
            'record_id' => $lottery_record_id,
            'msg' => 'ok'
        ]));
    }

    public function records()
    {
        $device_id = $this->params['device_id'] ?? '';
        $order_id = intval($this->params['order_id'] ?? 0);
        $limit = intval($this->params['limit'] ?? 20);
        if ($limit <= 0 || $limit > 200) $limit = 20;

        $where = [];
        if ($device_id !== '') {
            if (is_numeric($device_id)) {
                $where[] = ['device_id', '=', intval($device_id)];
            } else {
                $dev = Db::name('device_info')->where('device_sn', strval($device_id))->find();
                if ($dev) $where[] = ['device_id', '=', intval($dev['device_id'])];
            }
        }
        if ($order_id > 0) $where[] = ['order_id', '=', $order_id];

        $list = Db::name('lottery_record')
            ->where($where)
            ->order('record_id desc')
            ->limit($limit)
            ->select()
            ->toArray();

        return $this->response($this->success(['list' => $list, 'count' => count($list)]));
    }

    public function profits()
    {
        $order_id = intval($this->params['order_id'] ?? 0);
        $record_id = intval($this->params['record_id'] ?? 0);
        $limit = intval($this->params['limit'] ?? 50);
        if ($limit <= 0 || $limit > 200) $limit = 50;

        $where = [];
        if ($order_id > 0) $where[] = ['order_id', '=', $order_id];
        if ($record_id > 0) $where[] = ['record_id', '=', $record_id];

        try {
            $list = Db::name('ns_lottery_profit')
                ->where($where)
                ->order('id desc')
                ->limit($limit)
                ->select()
                ->toArray();
        } catch (\Throwable $e) {
            $list = [];
        }

        return $this->response($this->success(['list' => $list, 'count' => count($list)]));
    }
}