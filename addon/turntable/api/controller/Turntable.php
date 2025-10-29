<?php
namespace addon\turntable\api\controller;

use app\api\controller\BaseApi;
use addon\turntable\model\Lottery as LotteryModel;

class Turntable extends BaseApi
{
    /**
     * 获取游戏详情
     */
    public function info()
    {
        $game = new \app\model\games\Games();
        $game_id = $this->params['game_id'] ?? 0;
        $site_id = $this->params['site_id'] ?? 0;
        $info = $game->getGamesDetail($site_id, $game_id);
        if ($info['code'] == 0) {
            // 抽奖记录
            if ($info['data']['is_show_winner']) {
                $record = new \app\model\games\Record();
                $record_data = $record->getGamesDrawRecordPageList([ ['game_id', '=', $game_id], ['is_winning', '=', 1] ], 1, 10, 'create_time desc', 'member_nick_name,award_name,create_time');
                $info['data']['draw_record'] = $record_data['data']['list'];
            }
            // 剩余次数
            $token = $this->checkToken();
            $info['data']['surplus_num'] = 0;
            if ($info['data']['join_frequency'] && $token['code'] == 0) {
                $surplus_num = $game->getMemberSurplusNum($game_id, $this->member_id, $site_id);
                $info['data']['surplus_num'] = $surplus_num['data'];
            }
        }
        return $this->response($info);
    }

    /**
     * 16格抽奖：奖品列表
     */
    public function prizeList()
    {
        $lottery = new LotteryModel();
        $site_id   = $this->params['site_id'] ?? 0;
        $device_sn = $this->params['device_sn'] ?? '';
        $device_id = $this->params['device_id'] ?? 0;
        $board_id  = $this->params['board_id'] ?? 0;

        // 兼容 device_id
        if (!$device_sn && $device_id) {
            $device = model('device_info')->getInfo([['device_id', '=', $device_id]], '*');
            if (!empty($device)) {
                $device_sn = $device['device_sn'];
                $board_id = $board_id ?: ($device['board_id'] ?? 0);
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
        $lottery = new LotteryModel();
        $site_id   = $this->params['site_id'] ?? 0;
        $device_sn = $this->params['device_sn'] ?? '';
        $device_id = $this->params['device_id'] ?? 0;
        $board_id  = $this->params['board_id'] ?? 0;
        $tier_id   = $this->params['tier_id'] ?? 0;

        // 兼容 device_id
        if (!$device_sn && $device_id) {
            $device = model('device_info')->getInfo([['device_id', '=', $device_id]], '*');
            if (!empty($device)) {
                $device_sn = $device['device_sn'];
                $board_id = $board_id ?: ($device['board_id'] ?? 0);
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
     * 收件信息填写
     */
    public function address()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $record_id = $this->params['record_id'] ?? 0;
        if (!$record_id) return $this->response($this->error('', '缺少 record_id'));

        // 收件信息（最小必填）
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
            return $this->response($this->error('', '地址信息不完整'));
        }

        $record = model('lottery_record')->getInfo([['record_id', '=', $record_id], ['member_id', '=', $this->member_id]], '*');
        if (empty($record)) return $this->response($this->error('', '记录不存在'));
        if (($record['prize_type'] ?? 'thanks') !== 'goods') {
            return $this->response($this->error('', '非实物奖品无需填写地址'));
        }

        // 写入扩展信息
        $ext = json_decode($record['ext'] ?: '{}', true);
        $ext['shipping_address'] = $addr;
        model('lottery_record')->update(['ext' => json_encode($ext, JSON_UNESCAPED_UNICODE)], [['record_id', '=', $record_id]]);

        return $this->response($this->success(['record_id' => $record_id]));
    }

    /**
     * 到店核销：校验核销码，更新奖励单状态为 verified
     */
    public function verify()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $record_id  = intval($this->params['record_id'] ?? 0);
        $verify_code = strtoupper(trim($this->params['verify_code'] ?? ''));
        if (!$record_id || !$verify_code) return $this->response($this->error('', '缺少参数'));

        $record = model('lottery_record')->getInfo([['record_id', '=', $record_id], ['member_id', '=', $this->member_id]], '*');
        if (empty($record)) return $this->response($this->error('', '记录不存在'));
        if (($record['prize_type'] ?? 'thanks') !== 'goods') return $this->response($this->error('', '非实物奖品无需核销'));

        $ext = json_decode($record['ext'] ?: '{}', true);
        $order = $ext['order'] ?? [];
        if (empty($order) || strtoupper($order['verify_code'] ?? '') !== $verify_code) {
            return $this->response($this->error('', '核销码不正确'));
        }
        if (($order['status'] ?? '') === 'verified') {
            return $this->response($this->success(['record_id' => $record_id, 'status' => 'verified']));
        }
        // 标记核销成功（设备线默认现场领取，不做发货标记）
        $order['status'] = 'verified';
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
                    'payload'     => json_encode(['from' => 'device_api_verify'], JSON_UNESCAPED_UNICODE),
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

        return $this->response($this->success(['record_id' => $record_id, 'status' => 'verified']));
    }

    /**
     * 抽奖记录：返回当前会员或指定设备的抽奖分页列表
     * 路由：GET /addons/turntable/api/record?page=&page_size=&device_id=
     */
    public function record()
    {
        $lottery   = new LotteryModel();
        $page      = intval($this->params['page'] ?? 1);
        $page_size = intval($this->params['page_size'] ?? 10);
        $device_id = intval($this->params['device_id'] ?? 0);

        // token 可选：有则按会员筛选；无则仅允许按 device_id 或返回空列表以保护隐私
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
            // 未登录且未按设备筛选，则不返回公共数据
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