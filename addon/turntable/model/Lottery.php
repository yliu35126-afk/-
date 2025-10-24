<?php
namespace addon\turntable\model;

use app\model\BaseModel;

class Lottery extends BaseModel
{
    /**
     * 获取设备绑定的盘信息
     */
    public function getBoardByDeviceSn($device_sn)
    {
        $device = model('device_info')->getInfo([["device_sn", '=', $device_sn]], '*');
        return $this->success($device);
    }

    /**
     * 获取盘的格子列表（按 position 排序）
     */
    public function getSlots($board_id)
    {
        $list = model('lottery_slot')->getList([["board_id", '=', $board_id]], '*', 'position asc');
        return $this->success($list);
    }

    /**
     * 奖品列表（16格），支持 device_sn 或 board_id
     */
    public function prizeList($params)
    {
        $site_id   = $params['site_id'] ?? 0;
        $device_sn = $params['device_sn'] ?? '';
        $device_id = intval($params['device_id'] ?? 0);
        $board_id  = $params['board_id'] ?? 0;

        if ($device_sn) {
            $device_res = $this->getBoardByDeviceSn($device_sn);
            $device     = $device_res['data'] ?? [];
            if (empty($device)) return $this->error('', '设备不存在');
            $board_id = $device['board_id'];
        } elseif ($device_id) {
            $device = model('device_info')->getInfo([["device_id", '=', $device_id]], '*');
            if (empty($device)) return $this->error('', '设备不存在');
            $board_id = $device['board_id'];
        }
        if (empty($board_id)) return $this->error('', '设备未绑定盘，请在后台绑定后重试');

        $board_info = model('lottery_board')->getInfo([["board_id", '=', $board_id], ["site_id", '=', $site_id]], '*');
        if (empty($board_info)) return $this->error('', '抽奖盘不存在');

        $slots_res = $this->getSlots($board_id);
        $slots     = $slots_res['data'] ?? [];

        // 移除“谢谢参与”并用真实奖品填充到 16 格
        $non_thanks = [];
        foreach ($slots as $s) {
            if (($s['prize_type'] ?? 'thanks') !== 'thanks') $non_thanks[] = $s;
        }
        if (empty($non_thanks)) return $this->error('', '未配置可抽奖品');
        $count = count($slots);
        $nextPos = $count;
        while ($nextPos < 16) {
            $src = $non_thanks[$nextPos % count($non_thanks)];
            $clone = $src;
            $clone['position'] = $nextPos;
            $slots[] = $clone;
            $nextPos++;
        }

        // 追加：若传入了设备标识，返回当前生效价档与“按金额”的分润明细
        $tier_out = [];
        $profit_info = [];
        try {
            $device_id_local = 0;
            if ($device_sn) {
                $dev = model('device_info')->getInfo([["device_sn", '=', $device_sn]], 'device_id');
                $device_id_local = intval($dev['device_id'] ?? 0);
            } elseif ($device_id) {
                $device_id_local = intval($device_id);
            }
            if ($device_id_local) {
                $now = time();
                // 优先选取长期有效的绑定
                $bind = model('device_price_bind')->getInfo([
                    ['device_id', '=', $device_id_local],
                    ['status', '=', 1],
                    ['start_time', '<=', $now],
                    ['end_time', '=', 0],
                ], '*');
                if (empty($bind)) {
                    // 其次选当前时间范围内有效的绑定
                    $bind = model('device_price_bind')->getInfo([
                        ['device_id', '=', $device_id_local],
                        ['status', '=', 1],
                        ['start_time', '<=', $now],
                        ['end_time', '>=', $now],
                    ], '*');
                }
                if (!empty($bind)) {
                    $tier = model('lottery_price_tier')->getInfo([ ['tier_id', '=', $bind['tier_id']] ], '*');
                    if (!empty($tier)) {
                        $tier_out = [
                            'tier_id' => intval($tier['tier_id'] ?? 0),
                            'title'   => $tier['title'] ?? '',
                            'price'   => isset($tier['price']) ? floatval($tier['price']) : 0.0,
                            'status'  => intval($tier['status'] ?? 0),
                        ];
                        $profit_raw = json_decode($tier['profit_json'] ?? '{}', true);
                        if (is_array($profit_raw)) {
                            // 与 draw() 保持一致：数值且最大值与总和均 <= 1 视为比例，否则视为金额
                            $values = array_values($profit_raw ?: []);
                            $sumRates = 0; $maxVal = 0; $allNumeric = true;
                            foreach ($values as $v) {
                                if (!is_numeric($v)) { $allNumeric = false; }
                                $val = floatval($v);
                                $sumRates += $val;
                                if ($val > $maxVal) $maxVal = $val;
                            }
                            $treatAsRate = ($allNumeric && $maxVal <= 1.000001 && $sumRates <= 1.000001);

                            foreach ($profit_raw as $k => $val) {
                                $num = floatval($val);
                                $amt = $treatAsRate ? round(($tier_out['price'] ?? 0) * $num, 2) : round($num, 2);
                                if ($amt > 0) $profit_info[$k] = $amt;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // 忽略分润扩展的异常，保证原有接口不受影响
        }

        $data = [
            'board' => $board_info,
            'slots' => $slots,
        ];
        if (!empty($tier_out)) $data['tier'] = $tier_out;
        if (!empty($profit_info)) $data['profit_info'] = $profit_info;
        return $this->success($data);
    }

    /**
     * 抽奖：根据权重随机，实物奖品需库存>0；不再返回“谢谢参与”
     */
    public function draw($params)
    {
        $member_id = $params['member_id'] ?? 0;
        $site_id   = $params['site_id'] ?? 0;
        $device_sn = $params['device_sn'] ?? '';
        $board_id  = $params['board_id'] ?? 0;
        $tier_id   = $params['tier_id'] ?? 0;
        $device_id = intval($params['device_id'] ?? 0);

        if ($device_sn) {
            $device = model('device_info')->getInfo([["device_sn", '=', $device_sn]], '*');
            if (empty($device)) return $this->error('', '设备不存在');
            $board_id  = $device['board_id'];
            $device_id = intval($device['device_id'] ?? 0);
        } elseif ($device_id) {
            $device = model('device_info')->getInfo([["device_id", '=', $device_id]], '*');
            if (empty($device)) return $this->error('', '设备不存在');
            $board_id = $device['board_id'];
        }
        if (empty($board_id) || empty($member_id)) return $this->error('', '参数错误');
 
        // 频控：同用户同设备3秒一次
        $rate_key = "lottery_rate_{$member_id}_{$device_id}";
        try {
            if (\think\facade\Cache::get($rate_key)) {
                return $this->error('', '操作过于频繁，请稍后再试');
            }
            \think\facade\Cache::set($rate_key, 1, 3);
        } catch (\Throwable $e) {}

        // 并发锁：优先用 Redis NX，其次用普通锁兜底
        $lock_key = "lottery_lock_board_{$board_id}";
        $locked = false;
        try {
            $store = \think\facade\Cache::store('redis');
            if (method_exists($store, 'handler')) {
                $redis = $store->handler();
                if ($redis) {
                    // Redis NX 2秒锁
                    $locked = (bool) $redis->set($lock_key, 1, ['nx', 'ex' => 2]);
                }
            }
        } catch (\Throwable $e) {}
        if (!$locked) {
            // 退化锁（非原子，只做简单保护）
            if (\think\facade\Cache::has($lock_key)) {
                return $this->error('', '系统繁忙，请稍后重试');
            }
            \think\facade\Cache::set($lock_key, 1, 2);
            $locked = true;
        }

        // 设备-价档绑定校验
        if ($device_id && $tier_id) {
            $bind = model('device_price_bind')->getInfo([["device_id", '=', $device_id],["tier_id", '=', $tier_id]], '*');
            if (empty($bind)) {
                \think\facade\Cache::delete($lock_key);
                return $this->error('', '设备未绑定该价档');
            }
        }
        $board = model('lottery_board')->getInfo([["board_id", '=', $board_id]], '*');
        $mode  = $board['mode'] ?? 'round';
        $round_no = intval($board['round_no'] ?? 0);
        $auto_reset_round = intval($board['auto_reset_round'] ?? 0) === 1;

        $slots = model('lottery_slot')->getList([["board_id", '=', $board_id]], '*', 'position asc');
        if (empty($slots)) {
            \think\facade\Cache::delete($lock_key);
            return $this->error('', '抽奖盘未配置格子');
        }

        $pick = function($candidates) {
            // 权重随机：权重为0则当作1
            $sum = 0;
            foreach ($candidates as $c) $sum += max(intval($c['weight'] ?? 0), 1);
            $rand = mt_rand(1, max($sum, 1));
            $acc  = 0;
            foreach ($candidates as $c) {
                $acc += max(intval($c['weight'] ?? 0), 1);
                if ($rand <= $acc) return $c;
            }
            return $candidates[array_key_first($candidates)];
        };

        $attempts = 0;
        $hit = null;
        while ($attempts < 5) {
            $attempts++;
            if ($mode === 'round') {
                // 本轮可中次数>0 的候选（不含谢谢参与）
                $candidates = [];
                foreach ($slots as $s) {
                    if (($s['prize_type'] ?? 'thanks') === 'thanks') continue;
                    if (intval($s['round_qty'] ?? 0) <= 0) continue;
                    if ($s['prize_type'] === 'goods' && intval($s['inventory'] ?? 0) <= 0) continue;
                    $candidates[] = $s;
                }
                if (empty($candidates)) {
                    \think\facade\Cache::delete($lock_key);
                    return $this->error('', '抽奖盘未配置可用奖品');
                }
                $hit = $pick($candidates);
                // 命中实物但库存为0：将该槽本轮置为0并重选
                if ($hit['prize_type'] === 'goods' && intval($hit['inventory'] ?? 0) <= 0) {
                    if (!empty($hit['slot_id'])) {
                        model('lottery_slot')->update(['round_qty' => 0], [["slot_id", '=', $hit['slot_id']]]);
                    }
                    $hit = null; continue;
                }
            } elseif ($mode === 'realtime') {
                // 仅含有效权重且非“谢谢参与”的候选
                $candidates = [];
                foreach ($slots as $s) {
                    if (($s['prize_type'] ?? 'thanks') === 'thanks') continue;
                    if ($s['prize_type'] === 'goods' && intval($s['inventory'] ?? 0) <= 0) continue;
                    if (intval($s['weight'] ?? 0) <= 0) continue;
                    $candidates[] = $s;
                }
                if (empty($candidates)) {
                    \think\facade\Cache::delete($lock_key);
                    return $this->error('', '抽奖盘未配置可用奖品');
                }
                $hit = $pick($candidates);
            } else { // random
                $candidates = [];
                foreach ($slots as $s) {
                    if (($s['prize_type'] ?? 'thanks') === 'thanks') continue;
                    if ($s['prize_type'] === 'goods' && intval($s['inventory'] ?? 0) <= 0) continue;
                    $candidates[] = $s;
                }
                if (empty($candidates)) {
                    \think\facade\Cache::delete($lock_key);
                    return $this->error('', '抽奖盘未配置可用奖品');
                }
                $hit = $candidates[array_rand($candidates)];
            }

            if ($hit !== null) break;
        }

        $now    = time();
        $result = 'hit';

        // 价档金额
        $tier = $tier_id ? model('lottery_price_tier')->getInfo([["tier_id", '=', $tier_id]], '*') : [];
        $amount = empty($tier) ? 0 : floatval($tier['price'] ?? ($tier['amount'] ?? 0));

        model('lottery_record')->startTrans();
        try {
            if (!empty($hit['slot_id'])) {
                // 库存扣减
                if ($hit['prize_type'] === 'goods') {
                    $new_inv = max(intval($hit['inventory'] ?? 0) - 1, 0);
                    model('lottery_slot')->update(['inventory' => $new_inv], [["slot_id", '=', $hit['slot_id']]]);
                }
                // 本轮可中次数-1（轮次模式）
                if ($mode === 'round') {
                    $new_qty = max(intval($hit['round_qty'] ?? 0) - 1, 0);
                    model('lottery_slot')->update(['round_qty' => $new_qty], [["slot_id", '=', $hit['slot_id']]]);
                }
            }

            $record_id = model('lottery_record')->add([
                'member_id'  => $member_id,
                'device_id'  => $device_id ?? 0,
                'board_id'   => $board_id,
                'slot_id'    => $hit['slot_id'] ?? 0,
                'prize_type' => $hit['prize_type'] ?? 'thanks',
                'goods_id'   => $hit['goods_id'] ?? 0,
                'tier_id'    => $tier_id,
                'amount'     => $amount,
                'round_no'   => $round_no,
                'result'     => $result,
                'order_id'   => 0,
                'ext'        => json_encode(['hit' => $hit, 'mode' => $mode], JSON_UNESCAPED_UNICODE),
                'create_time'=> $now,
            ]);

            // 命中实物：创建“0元奖励单”（轻量），回写 order_id
            if (!empty($record_id) && ($hit['prize_type'] ?? '') === 'goods') {
                $verify_code = strtoupper(substr(md5($record_id.'-'.$member_id.'-'.$device_id), 0, 10));
                $order_stub = [
                    'record_id'   => $record_id,
                    'member_id'   => $member_id,
                    'site_id'     => $site_id,
                    'device_id'   => $device_id,
                    'board_id'    => $board_id,
                    'goods_id'    => $hit['goods_id'] ?? 0,
                    'slot_id'     => $hit['slot_id'] ?? 0,
                    'amount'      => 0,
                    'status'      => 'pending', // deliver|verified 后续状态
                    'verify_code' => $verify_code,
                    'create_time' => $now,
                ];
                // 轻量保存：直接写回记录ext，避免依赖商城订单复杂流程
                $ext = [ 'hit' => $hit, 'mode' => $mode, 'order' => $order_stub ];
                model('lottery_record')->update([
                    'order_id' => $record_id,
                    'ext'      => json_encode($ext, JSON_UNESCAPED_UNICODE)
                ], [["record_id", '=', $record_id]]);
            }

            // 分润记录：按系统用户组结算
            if (!empty($tier) && !empty($record_id)) {
                $profit = json_decode($tier['profit_json'] ?? '{}', true);
                $group_profit = [];
                // 兼容两种配置：
                // 1) 旧配置为比例(rate: 0-1)，按 amount * rate 计算；
                // 2) 新配置为金额(元)，直接取值。
                $values = array_values($profit ?: []);
                $sumRates = 0; $maxVal = 0; $allNumeric = true;
                foreach ($values as $v) {
                    $val = floatval($v);
                    if (!is_numeric($v)) { $allNumeric = false; }
                    $sumRates += $val;
                    if ($val > $maxVal) $maxVal = $val;
                }
                $treatAsRate = ($allNumeric && $maxVal <= 1.000001 && $sumRates <= 1.000001);

                foreach ($profit as $k => $val) {
                    if (strpos($k, 'group_') === 0) {
                        $gid = intval(substr($k, 6));
                        $num = floatval($val);
                        $amt = $treatAsRate ? round($amount * $num, 2) : round($num, 2);
                        if ($amt > 0) $group_profit[$gid] = $amt;
                    }
                }
                // 将分润明细写入记录扩展
                $record = model('lottery_record')->getInfo([["record_id", "=", $record_id]], 'ext');
                $ext = [];
                if (!empty($record)) $ext = json_decode($record['ext'] ?? '{}', true);
                $ext['profit_groups'] = $group_profit;
                model('lottery_record')->update([
                    'ext' => json_encode($ext, JSON_UNESCAPED_UNICODE)
                ], [["record_id", "=", $record_id]]);
                // 写入占位账单（固定五角色置零，仅保留订单金额）
                model('lottery_profit')->add([
                    'record_id'      => $record_id,
                    'device_id'      => $device_id ?? 0,
                    'tier_id'        => $tier_id,
                    'site_id'        => $site_id,
                    'amount_total'   => $amount,
                    'platform_money' => 0,
                    'supplier_money' => 0,
                    'promoter_money' => 0,
                    'installer_money'=> 0,
                    'owner_money'    => 0,
                    'create_time'    => $now,
                ]);
            }

            model('lottery_record')->commit();
        } catch (\Exception $e) {
            model('lottery_record')->rollback();
            \think\facade\Cache::delete($lock_key);
            return $this->error('', '抽奖异常：' . $e->getMessage());
        }

        \think\facade\Cache::delete($lock_key);

        return $this->success([
            'result' => $result,
            'hit'    => $hit,
            'amount' => $amount,
        ]);
    }

    /**
     * 抽奖记录查询
     */
    public function record($params)
    {
        $member_id = $params['member_id'] ?? 0;
        $device_id = $params['device_id'] ?? 0;
        $condition = [];
        if ($member_id) $condition[] = ['member_id', '=', $member_id];
        if ($device_id) $condition[] = ['device_id', '=', $device_id];

        $page      = $params['page'] ?? 1;
        $page_size = $params['page_size'] ?? 10;
        $list = model('lottery_record')->pageList($condition, 'record_id desc', '*', $page, $page_size);
        return $this->success($list);
    }
}