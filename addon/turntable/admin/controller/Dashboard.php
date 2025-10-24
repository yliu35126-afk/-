<?php
namespace addon\turntable\admin\controller;

use app\admin\controller\BaseAdmin;

class Dashboard extends BaseAdmin
{
    /**
     * 大盘面板
     */
    public function index()
    {
        if (request()->isAjax()) {
            $site_id = $this->site_id;
            $today_start = strtotime(date('Y-m-d 00:00:00'));
            $today_end   = strtotime(date('Y-m-d 23:59:59'));

            $boards = model('lottery_board')->getCount([ ['site_id', '=', $site_id] ]);
            $devices = model('device_info')->getCount([ ['site_id', '=', $site_id] ]);
            $slots = model('lottery_slot')->getCount([]);
            $records_total = model('lottery_record')->getCount([ ['board_id', '>', 0] ]);
            $records_today = model('lottery_record')->getCount([ ['create_time', 'between', [$today_start, $today_end]] ]);

            $profit_total = model('lottery_profit')->getSum([ ['site_id', '=', $site_id] ], 'amount_total');
            $profit_today = model('lottery_profit')->getSum([ ['site_id', '=', $site_id], ['create_time', 'between', [$today_start, $today_end]] ], 'amount_total');

            // 已核销奖励数（今日）
            $today_records = model('lottery_record')->pageList([ ['create_time', 'between', [$today_start, $today_end]] ], 'record_id desc', 'record_id,ext', 1, 2000);
            $verified_today = 0; $settled_today = 0;
            foreach (($today_records['data']['list'] ?? []) as $row) {
                $ext = json_decode($row['ext'] ?? '{}', true);
                $order = $ext['order'] ?? [];
                if (strtolower($order['status'] ?? '') === 'verified') $verified_today++;
                if (strtolower($ext['profit_settle'] ?? '') === 'settled') $settled_today++;
            }

            return $this->success([
                'boards' => intval($boards),
                'devices' => intval($devices),
                'slots' => intval($slots),
                'records_total' => intval($records_total),
                'records_today' => intval($records_today),
                'profit_total' => floatval($profit_total),
                'profit_today' => floatval($profit_today),
                'verified_today' => $verified_today,
                'settled_today' => $settled_today,
            ]);
        } else {
            return $this->fetch('dashboard/index');
        }
    }
}