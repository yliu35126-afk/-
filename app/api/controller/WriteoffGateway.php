<?php
namespace app\api\controller;

use think\facade\Db;

/**
 * 核销网关控制器（薄代理）
 * 目的：为联调提供稳定入口，转发到现有核销接口
 * 路由示例：
 * - /index.php/api/writeoffgateway/verify
 * - /index.php/api/writeoffgateway/lists
 */
class WriteoffGateway extends BaseApi
{
    /** 到店核销（免token适配：verifyCode + merchantId） */
    public function verify()
    {
        $verify_code = strtoupper(trim($this->params['verifyCode'] ?? $this->params['verify_code'] ?? ''));
        $merchant_id = intval($this->params['merchantId'] ?? 0);
        $need_ship   = intval($this->params['needShip'] ?? ($this->params['need_ship'] ?? 0));
        if (!$verify_code || !$merchant_id) {
            return $this->response($this->error('', '缺少参数'));
        }

        // 通用核销中心记录
        $verify = model('verify')->getInfo([[ 'verify_code', '=', $verify_code ]], '*');
        if (empty($verify)) return $this->response($this->error('', 'VERIFY_CODE_INVALID'));
        if (intval($verify['is_verify'] ?? 0) === 1) {
            return $this->response($this->success(['status' => 'verified']));
        }

        $record_id = intval($verify['relate_id'] ?? 0);
        if ($record_id <= 0) return $this->response($this->error('', '记录不存在'));
        $record = model('lottery_record')->getInfo([[ 'record_id', '=', $record_id ]], '*');
        if (empty($record)) return $this->response($this->error('', '记录不存在'));
        if (($record['prize_type'] ?? 'thanks') !== 'goods') return $this->response($this->error('', '非实物奖品无需核销'));

        $ext   = json_decode($record['ext'] ?: '{}', true);
        $order = $ext['order'] ?? [];
        if (empty($order) || strtoupper($order['verify_code'] ?? '') !== $verify_code) {
            return $this->response($this->error('', 'VERIFY_CODE_INVALID'));
        }
        if (($order['status'] ?? '') === 'verified') {
            return $this->response($this->success(['record_id' => $record_id, 'status' => 'verified']));
        }

        // 标记核销成功；默认现场领取
        $order['status'] = 'verified';
        // 可选后续发货：现场缺货或选择后续配送
        if ($need_ship === 1 && !empty($ext['shipping_address'])) {
            $order['delivery_status'] = 'to_ship';
        }
        $ext['order'] = $order;
        model('lottery_record')->update(['ext' => json_encode($ext, JSON_UNESCAPED_UNICODE)], [[ 'record_id', '=', $record_id ]]);

        // 更新核销中心记录
        model('verify')->update([
            'verifier_id'  => $merchant_id,
            'verifier_name'=> 'GatewayVerifier',
            'is_verify'    => 1,
            'verify_time'  => time(),
        ], [[ 'id', '=', intval($verify['id']) ]]);

        // 入队抽奖结算占位并触发结算事件（幂等）
        try {
            $exists = Db::name('lottery_settlement')->where('record_id', $record_id)->find();
            if (empty($exists)) {
                Db::name('lottery_settlement')->insert([
                    'record_id'   => $record_id,
                    'source_type' => 'self',
                    'status'      => 'pending',
                    'payload'     => json_encode(['from' => 'writeoff_gateway', 'need_ship' => $need_ship], JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            } else {
                Db::name('lottery_settlement')->where('record_id', $record_id)->update([
                    'status' => 'pending',
                    'update_time' => time(),
                ]);
            }
            if (function_exists('event')) { try { event('turntable_settlement', ['record_id' => $record_id]); } catch (\Throwable $e) {} }
        } catch (\Throwable $te) {}

        return $this->response($this->success(['record_id' => $record_id, 'status' => 'verified', 'need_ship' => $need_ship]));
    }

    /** 核销记录列表（通用 Verify 控制器） */
    public function lists()
    {
        $controller = new \app\api\controller\Verify();
        return $controller->lists();
    }
}