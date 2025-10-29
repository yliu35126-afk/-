<?php
namespace addon\turntable\event;

/**
 * 通用核销事件监听：回写抽奖记录为已核销
 */
class Verify
{
    public function handle($params = [])
    {
        try {
            $verify_type = strval($params['verify_type'] ?? '');
            $code = strval($params['verify_code'] ?? '');
            if ($verify_type !== 'turntable_goods' || empty($code)) return true;

            // 通过核销表找到关联的抽奖记录
            $verify = model('verify')->getInfo([[ 'verify_code', '=', $code ]], 'id, relate_id, verifier_id, verifier_name');
            if (empty($verify)) return true;
            $record_id = intval($verify['relate_id'] ?? 0);
            if (!$record_id) return true;

            $record = model('lottery_record')->getInfo([[ 'record_id', '=', $record_id ]], 'record_id, ext');
            if (empty($record)) return true;
            $ext = json_decode($record['ext'] ?? '{}', true);
            $order = $ext['order'] ?? [];
            if (($order['verify_code'] ?? '') !== $code) return true;

            // 已核销则跳过
            if (($order['status'] ?? '') === 'verified') return true;

            // 回写核销状态与核销员信息
            $order['status'] = 'verified';
            $order['verify_time'] = time();
            if (!empty($verify['verifier_id'])) {
                $order['verifier_id'] = intval($verify['verifier_id']);
            }
            if (!empty($verify['verifier_name'])) {
                $order['verifier_name'] = strval($verify['verifier_name']);
            }
            // 设备线为现场核销，当场领取，不做发货标记
            $ext['order'] = $order;
            model('lottery_record')->update([
                'ext' => json_encode($ext, JSON_UNESCAPED_UNICODE),
            ], [[ 'record_id', '=', $record_id ]]);

            // 入队抽奖结算占位（幂等）
            try {
                $exists = \think\facade\Db::name('lottery_settlement')->where('record_id', $record_id)->find();
                if (empty($exists)) {
                    \think\facade\Db::name('lottery_settlement')->insert([
                        'record_id'   => $record_id,
                        'source_type' => 'self',
                        'status'      => 'pending',
                        'payload'     => json_encode(['from' => 'verify_event'], JSON_UNESCAPED_UNICODE),
                        'create_time' => time(),
                        'update_time' => time(),
                    ]);
                } else {
                    // 将状态置为待处理，避免漏跑
                    \think\facade\Db::name('lottery_settlement')->where('record_id', $record_id)->update([
                        'status' => 'pending',
                        'update_time' => time(),
                    ]);
                }
            } catch (\Throwable $ie) {}

            // 触发结算事件
            try {
                event('turntable_settlement', ['record_id' => $record_id]);
            } catch (\Throwable $te) {}

            return true;
        } catch (\Throwable $e) {
            // 避免影响通用核销流程
            return true;
        }
    }
}