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
            try {
                $site_id = $this->site_id;
                $today_start = strtotime(date('Y-m-d 00:00:00'));
                $today_end   = strtotime(date('Y-m-d 23:59:59'));

                $boards = model('lottery_board')->getCount([ ['site_id', '=', $site_id] ]);
                $devices = model('device_info')->getCount([ ['site_id', '=', $site_id] ]);
                $slots = model('lottery_slot')->getCount([]);
                $records_total = model('lottery_record')->getCount([ ['board_id', '>', 0] ]);
                $records_today = model('lottery_record')->getCount([ ['create_time', 'between', [$today_start, $today_end]] ]);

                // 统一分润数据源：按角色明细 ns_lottery_profit 汇总金额
                $profit_total = \think\facade\Db::name('ns_lottery_profit')->where([ ['site_id', '=', $site_id] ])->sum('amount');
                $profit_today = \think\facade\Db::name('ns_lottery_profit')->where([ ['site_id', '=', $site_id], ['create_time', 'between', [$today_start, $today_end]] ])->sum('amount');
                // 角色分布聚合（用于图表）
                $role_rows = \think\facade\Db::name('ns_lottery_profit')
                    ->field('role, SUM(amount) AS amount')
                    ->where([ ['site_id', '=', $site_id] ])
                    ->group('role')->select()->toArray();
                $role_dist = [];
                foreach ($role_rows as $rr) { $role_dist[] = ['role' => strval($rr['role']), 'amount' => floatval($rr['amount'])]; }

                // 已核销奖励数（今日）
                // 正确顺序：condition, field, order, page, page_size
                $today_records = model('lottery_record')->pageList([ ['create_time', 'between', [$today_start, $today_end]] ], 'record_id,ext', 'record_id desc', 1, 2000);
                $list = $today_records['list'] ?? ($today_records['data']['list'] ?? []);
                $verified_today = 0; $settled_today = 0;
                foreach ($list as $row) {
                    $ext = json_decode($row['ext'] ?? '{}', true);
                    $order = $ext['order'] ?? [];
                    if (strtolower($order['status'] ?? '') === 'verified') $verified_today++;
                    if (strtolower($ext['profit_settle'] ?? '') === 'settled') $settled_today++;
                }

                return json(success(0, '', [
                    'boards' => intval($boards),
                    'devices' => intval($devices),
                    'slots' => intval($slots),
                    'records_total' => intval($records_total),
                    'records_today' => intval($records_today),
                    'profit_total' => floatval($profit_total),
                    'profit_today' => floatval($profit_today),
                    'verified_today' => $verified_today,
                    'settled_today' => $settled_today,
                    'role_dist' => $role_dist,
                ]));
            } catch (\Throwable $e) {
                return json(success(0, '', [
                    'boards' => 0,
                    'devices' => 0,
                    'slots' => 0,
                    'records_total' => 0,
                    'records_today' => 0,
                    'profit_total' => 0,
                    'profit_today' => 0,
                    'verified_today' => 0,
                    'settled_today' => 0,
                    'error' => 'dashboard_stats_error',
                    'message' => $e->getMessage(),
                ]));
            }
        } else {
            return $this->fetch('dashboard/index');
        }
    }
}