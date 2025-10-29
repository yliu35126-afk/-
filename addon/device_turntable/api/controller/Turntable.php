<?php
namespace addon\device_turntable\api\controller;

use app\api\controller\BaseApi;
use addon\turntable\model\Lottery as LotteryModel;

/**
 * 设备版抽奖 API 控制器
 * 路由示例：
 * - GET    /addons/device_turntable/api/turntable/prizelist
 * - POST   /addons/device_turntable/api/turntable/draw
 * - POST   /addons/device_turntable/api/turntable/address
 * - POST   /addons/device_turntable/api/turntable/verify
 * - GET    /addons/device_turntable/api/turntable/record
 * - GET    /addons/device_turntable/api/turntable/amounttiers
 */
class Turntable extends BaseApi
{
    /**
     * 设备可用价档列表（按设备SN或ID获取）
     * params: device_sn | device_id
     * return: [ { tier_id, title, price, status } ]
     */
    public function amountTiers()
    {
        $device_sn = $this->params['device_sn'] ?? '';
        $device_id = intval($this->params['device_id'] ?? 0);

        if (!$device_sn && !$device_id) {
            return $this->response($this->error('', 'MISSING_DEVICE'));
        }

        // 查询设备
        if ($device_sn) {
            $device = model('device_info')->getInfo([['device_sn', '=', $device_sn]], '*');
        } else {
            $device = model('device_info')->getInfo([['device_id', '=', $device_id]], '*');
        }
        if (empty($device)) return $this->response($this->error('', 'DEVICE_NOT_FOUND'));

        $device_id = intval($device['device_id'] ?? ($device['id'] ?? 0));
        if (!$device_id) return $this->response($this->success(['list' => [], 'count' => 0]));

        // 设备-价档绑定表，取所有绑定的 tier_id
        $bind_list = model('device_price_bind')->getList([
            ['device_id', '=', $device_id]
        ], 'tier_id', 'id asc');

        if (empty($bind_list)) {
            return $this->response($this->success(['list' => [], 'count' => 0]));
        }

        $tier_ids = array_values(array_unique(array_map(function($row){ return intval($row['tier_id']); }, $bind_list)));
        if (empty($tier_ids)) return $this->response($this->success(['list' => [], 'count' => 0]));

        $tiers = model('lottery_price_tier')->getList([
            ['tier_id', 'in', $tier_ids],
            ['status', '=', 1]
        ], 'tier_id,title,price,status', 'price asc');

        $tiers = $tiers ?: [];
        $result = [];
        foreach ($tiers as $t) {
            $result[] = [
                'tier_id' => intval($t['tier_id']),
                'title'   => $t['title'],
                'price'   => (float)$t['price'],
                'status'  => intval($t['status'] ?? 1),
            ];
        }
        return $this->response($this->success(['list' => $result, 'count' => count($result)]));
    }

    /**
     * 16格抽奖：奖品列表
     */
    public function prizeList()
    {
        $lottery   = new LotteryModel();
        $site_id   = $this->params['site_id'] ?? 0;
        $device_sn = $this->params['device_sn'] ?? '';
        $device_id = $this->params['device_id'] ?? 0;
        $board_id  = $this->params['board_id'] ?? 0;

        if (!$device_sn && $device_id) {
            $device = model('device_info')->getInfo([['device_id', '=', $device_id]], '*');
            if (!empty($device)) {
                $device_sn = $device['device_sn'];
                $board_id  = $board_id ?: ($device['board_id'] ?? 0);
            }
        }

        $res = $lottery->prizeList([
            'site_id'   => $site_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'board_id'  => $board_id,
        ]);
        return $this->response($res);
    }

    /**
     * 16格抽奖：抽一次
     */
    public function draw()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $lottery   = new LotteryModel();
        $site_id   = $this->params['site_id'] ?? 0;
        $device_sn = $this->params['device_sn'] ?? '';
        $device_id = $this->params['device_id'] ?? 0;
        $board_id  = $this->params['board_id'] ?? 0;
        $tier_id   = $this->params['tier_id'] ?? 0;

        if (!$device_sn && $device_id) {
            $device = model('device_info')->getInfo([['device_id', '=', $device_id]], '*');
            if (!empty($device)) {
                $device_sn = $device['device_sn'];
                $board_id  = $board_id ?: ($device['board_id'] ?? 0);
            }
        }

        $res = $lottery->draw([
            'member_id' => $this->member_id,
            'site_id'   => $site_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'board_id'  => $board_id,
            'tier_id'   => $tier_id,
        ]);
        return $this->response($res);
    }

    /**
     * 收件信息填写（仅实物奖品）
     */
    public function address()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $record_id = intval($this->params['record_id'] ?? 0);
        if (!$record_id) return $this->response($this->error('', '缺少 record_id'));

        $addr = [
            'name'         => $this->params['name'] ?? '',
            'mobile'       => $this->params['mobile'] ?? '',
            'province_id'  => intval($this->params['province_id'] ?? 0),
            'city_id'      => intval($this->params['city_id'] ?? 0),
            'district_id'  => intval($this->params['district_id'] ?? 0),
            'address'      => $this->params['address'] ?? '',
            'full_address' => $this->params['full_address'] ?? '',
            'longitude'    => $this->params['longitude'] ?? '',
            'latitude'     => $this->params['latitude'] ?? '',
        ];
        if (!$addr['name'] || !$addr['mobile'] || !$addr['address']) {
            return $this->response($this->error('', 'ADDR_INCOMPLETE'));
        }

        $record = model('lottery_record')->getInfo([
            ['record_id', '=', $record_id],
            ['member_id', '=', $this->member_id]
        ], '*');
        if (empty($record)) return $this->response($this->error('', '记录不存在'));
        if (($record['prize_type'] ?? 'thanks') !== 'goods') {
            return $this->response($this->error('', '非实物奖品无需填写地址'));
        }

        $ext = json_decode($record['ext'] ?: '{}', true);
        $ext['shipping_address'] = $addr;
        model('lottery_record')->update(['ext' => json_encode($ext, JSON_UNESCAPED_UNICODE)], [['record_id', '=', $record_id]]);

        return $this->response($this->success(['record_id' => $record_id]));
    }

    /**
     * 到店核销：核验核销码
     */
    public function verify()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $record_id   = intval($this->params['record_id'] ?? 0);
        $verify_code = strtoupper(trim($this->params['verify_code'] ?? ''));
        if (!$record_id || !$verify_code) return $this->response($this->error('', '缺少参数'));

        $record = model('lottery_record')->getInfo([
            ['record_id', '=', $record_id],
            ['member_id', '=', $this->member_id]
        ], '*');
        if (empty($record)) return $this->response($this->error('', '记录不存在'));
        if (($record['prize_type'] ?? 'thanks') !== 'goods') return $this->response($this->error('', '非实物奖品无需核销'));

        $ext = json_decode($record['ext'] ?: '{}', true);
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
        $need_ship = intval($this->params['need_ship'] ?? 0);
        if ($need_ship === 1 && !empty($ext['shipping_address'])) {
            $order['delivery_status'] = 'to_ship';
        }
        $ext['order'] = $order;
        model('lottery_record')->update(['ext' => json_encode($ext, JSON_UNESCAPED_UNICODE)], [['record_id', '=', $record_id]]);

        // 入队抽奖结算占位并触发结算事件（幂等）
        try {
            $exists = \think\facade\Db::name('lottery_settlement')->where('record_id', $record_id)->find();
            if (empty($exists)) {
                \think\facade\Db::name('lottery_settlement')->insert([
                    'record_id'   => $record_id,
                    'source_type' => 'self',
                    'status'      => 'pending',
                    'payload'     => json_encode(['from' => 'device_verify', 'need_ship' => $need_ship], JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            } else {
                \think\facade\Db::name('lottery_settlement')->where('record_id', $record_id)->update([
                    'status' => 'pending',
                    'update_time' => time(),
                ]);
            }
            event('turntable_settlement', ['record_id' => $record_id]);
        } catch (\Throwable $te) {}

        return $this->response($this->success(['record_id' => $record_id, 'status' => 'verified', 'need_ship' => $need_ship]));
    }

    /**
     * 抽奖记录：当前会员或指定设备的抽奖分页列表
     */
    public function record()
    {
        $lottery   = new LotteryModel();
        $page      = intval($this->params['page'] ?? 1);
        $page_size = intval($this->params['page_size'] ?? 10);
        $device_id = intval($this->params['device_id'] ?? 0);

        $member_id = 0;
        $token = $this->checkToken();
        if ($token['code'] == 0) {
            $member_id = $this->member_id;
        }

        $res = $lottery->record([
            'member_id' => $member_id,
            'device_id' => $device_id,
            'page'      => $page,
            'page_size' => $page_size,
        ]);

        if ($res['code'] == 0) {
            $data = $res['data'];
            $list = $data['list'] ?? [];
            $safe_list = [];
            foreach ($list as $row) {
                $ext = json_decode($row['ext'] ?? '{}', true);
                $order = $ext['order'] ?? [];
                $row['order'] = $order;
                $row['shipping_address'] = $ext['shipping_address'] ?? [];
                $row['verify_status'] = (($order['status'] ?? '') === 'verified') ? 'verified' : 'pending';
                $safe_list[] = $row;
            }
            if ($member_id == 0 && $device_id == 0) {
                $data['list'] = [];
                $data['count'] = 0;
            } else {
                $data['list'] = $safe_list;
            }
            $res = $this->success($data);
        }

        return $this->response($res);
    }
}