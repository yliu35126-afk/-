<?php
namespace addon\device_turntable\api\controller;

use app\api\controller\BaseApi;
use addon\turntable\model\Lottery as LotteryModel;
use think\facade\Db;

/**
 * 设备抽奖-订单与支付
 * 路由：
 * - POST /addons/device_turntable/api/order/create    创建抽奖支付订单（返回网关所需参数占位）
 * - POST /addons/device_turntable/api/order/paynotify 支付回调（成功后触发抽奖）
 */
class Order extends BaseApi
{
    /**
     * 创建抽奖订单（轻量）
     * 入参：device_sn|device_id, tier_id, site_id
     * 出参：order_no, amount, subject, attach（网关透传）
     */
    public function create()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $site_id   = intval($this->params['site_id'] ?? 0);
        $device_sn = strval($this->params['device_sn'] ?? '');
        $device_id = intval($this->params['device_id'] ?? 0);
        $tier_id   = intval($this->params['tier_id'] ?? 0);

        if ((!$device_sn && !$device_id) || $tier_id <= 0) {
            return $this->response($this->error('', 'PARAM_INCOMPLETE'));
        }

        // 解析设备
        if ($device_sn) $device = model('device_info')->getInfo([[ 'device_sn', '=', $device_sn ]], '*');
        else $device = model('device_info')->getInfo([[ 'device_id', '=', $device_id ]], '*');
        if (empty($device)) return $this->response($this->error('', 'DEVICE_NOT_FOUND'));
        $device_id = intval($device['device_id'] ?? 0);
        $board_id  = intval($device['board_id'] ?? 0);
        if ($board_id <= 0) return $this->response($this->error('', 'BOARD_NOT_BOUND'));

        // 校验设备与价档绑定
        $bind = model('device_price_bind')->getInfo([[ 'device_id', '=', $device_id ], [ 'tier_id', '=', $tier_id ]], '*');
        if (empty($bind)) return $this->response($this->error('', 'DEVICE_TIER_UNBOUND'));

        // 取价档
        $tier = model('lottery_price_tier')->getInfo([[ 'tier_id', '=', $tier_id ], [ 'status', '=', 1 ]], 'tier_id,title,price,status');
        if (empty($tier)) return $this->response($this->error('', 'TIER_INVALID'));

        $amount  = floatval($tier['price']);
        $subject = '设备抽奖-' . ($tier['title'] ?? ('T' . $tier_id));
        $order_no = 'LOT' . date('YmdHis') . mt_rand(1000, 9999);

        // 回调透传上下文
        $attach = json_encode([
            'member_id' => intval($this->member_id),
            'site_id'   => $site_id,
            'device_id' => $device_id,
            'board_id'  => $board_id,
            'tier_id'   => $tier_id,
        ], JSON_UNESCAPED_UNICODE);

        return $this->response($this->success([
            'order_no' => $order_no,
            'amount'   => $amount,
            'subject'  => $subject,
            'attach'   => $attach,
        ]));
    }

    /**
     * 支付回调（成功后触发抽奖）
     * 常用网关参数：order_no, total_amount, trade_status, attach, sign, ts
     * 签名：可通过环境变量 LOTTERY_PAY_SECRET 开启 HMAC 校验
     */
    public function paynotify()
    {
        // 无需登录
        $order_no  = strval($this->params['order_no'] ?? '');
        $amount    = floatval($this->params['total_amount'] ?? 0);
        $status    = strtolower(strval($this->params['trade_status'] ?? ''));
        $attachRaw = strval($this->params['attach'] ?? '');
        $ts        = intval($this->params['ts'] ?? 0);
        $sign      = strval($this->params['sign'] ?? '');

        if ($order_no === '' || $attachRaw === '' || $status === '') {
            return $this->response($this->error('', 'PARAM_INCOMPLETE'));
        }

        // 可选签名校验
        $secret = getenv('LOTTERY_PAY_SECRET') ?: '';
        if ($secret !== '') {
            if ($ts <= 0 || abs(time() - $ts) > 300 || $sign === '') {
                return $this->response($this->error('', 'SIGN_MISSING_OR_EXPIRED'));
            }
            $raw = $order_no . '|' . number_format($amount, 2, '.', '') . '|' . $status . '|' . $ts;
            $calc = hash_hmac('sha256', $raw, $secret);
            if (!hash_equals($calc, $sign)) {
                return $this->response($this->error('', 'SIGN_INVALID'));
            }
        }

        if (!in_array($status, ['success','finished','paid'])) {
            return $this->response($this->success(['ignored' => 1, 'message' => lang('NON_SUCCESS_STATUS')]))
            ;
        }

        // 解析 attach
        $ctx = [];
        try { $ctx = json_decode($attachRaw, true) ?: []; } catch (\Throwable $e) { $ctx = []; }
        $member_id = intval($ctx['member_id'] ?? 0);
        $site_id   = intval($ctx['site_id'] ?? 0);
        $device_id = intval($ctx['device_id'] ?? 0);
        $board_id  = intval($ctx['board_id'] ?? 0);
        $tier_id   = intval($ctx['tier_id'] ?? 0);

        if ($member_id <= 0 || $device_id <= 0 || $board_id <= 0 || $tier_id <= 0) {
            return $this->response($this->error('', 'CONTEXT_INCOMPLETE'));
        }

        // 触发抽奖
        $lottery = new LotteryModel();
        $start_ts = time();
        $res = $lottery->draw([
            'member_id' => $member_id,
            'site_id'   => $site_id,
            'device_id' => $device_id,
            'board_id'  => $board_id,
            'tier_id'   => $tier_id,
        ]);
        if ($res['code'] < 0) return $this->response($res);

        // 回写支付信息到最新抽奖记录（近窗口内）
        $record = Db::name('lottery_record')
            ->where([['member_id', '=', $member_id], ['device_id', '=', $device_id]])
            ->where('create_time', '>=', $start_ts - 5)
            ->order('record_id desc')
            ->find();
        if ($record) {
            $ext = [];
            try { $ext = json_decode($record['ext'] ?? '{}', true) ?: []; } catch (\Throwable $e) { $ext = []; }
            $ext['pay'] = [
                'order_no'    => $order_no,
                'amount'      => round($amount, 2),
                'trade_status'=> $status,
                'notify_time' => time(),
            ];
            Db::name('lottery_record')->where('record_id', intval($record['record_id']))
                ->update(['ext' => json_encode($ext, JSON_UNESCAPED_UNICODE)]);
        }

        return $this->response($this->success([
            'order_no' => $order_no,
            'draw'     => $res['data'] ?? [],
        ]));
    }
}