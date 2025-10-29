<?php
namespace addon\device_turntable\admin\controller;

use app\admin\controller\BaseAdmin;
use think\facade\Db;

class Profit extends BaseAdmin
{
    /**
     * 列表页：使用统一分润明细表 ns_lottery_profit，按 record_id 聚合并汇总各角色金额
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $device_id = (int)input('device_id', 0);
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            $settle_status = input('settle_status', '');

            $where = [];
            if ($device_id > 0) $where[] = ['device_id', '=', $device_id];
            if ($start_time && $end_time) {
                $where[] = ['create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            } elseif (!$start_time && $end_time) {
                $where[] = ['create_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && !$end_time) {
                $where[] = ['create_time', '>=', date_to_time($start_time) ];
            }

            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);

            // 统计按记录分组后的总数（统一分润表）
            $count = (int)Db::name('ns_lottery_profit')->where($where)->group('record_id')->count();
            $groups = Db::name('ns_lottery_profit')
                ->where($where)
                ->group('record_id')
                ->field('record_id, max(create_time) as create_time')
                ->order('create_time desc')
                ->page($page, $page_size)
                ->select()
                ->toArray();

            $list = [ 'code' => 0, 'message' => 'success', 'data' => [ 'count' => $count, 'list' => [] ] ];
            foreach ($groups as $g) {
                $rid = (int)($g['record_id'] ?? 0);
                if ($rid <= 0) continue;
                $rows = Db::name('ns_lottery_profit')->where('record_id', $rid)->select()->toArray();
                $item = [
                    'record_id' => $rid,
                    'device_id' => 0,
                    'site_id' => 0,
                    'amount_total' => 0.0,
                    'platform_money' => 0.0,
                    'supplier_money' => 0.0,
                    'promoter_money' => 0.0,
                    'installer_money'=> 0.0,
                    'owner_money'    => 0.0,
                    'merchant_money' => 0.0,
                    'agent_money'    => 0.0,
                    'member_money'   => 0.0,
                    'city_money'     => 0.0,
                    'create_time'    => (int)($g['create_time'] ?? 0),
                    'settle_status'  => 'pending',
                ];
                foreach ($rows as $r) {
                    $item['device_id'] = $item['device_id'] ?: (int)($r['device_id'] ?? 0);
                    $item['site_id']   = $item['site_id']   ?: (int)($r['site_id']   ?? 0);
                    $amt = floatval($r['amount'] ?? 0);
                    $role = strtolower($r['role'] ?? '');
                    $item['amount_total'] += $amt;
                    if (isset($item[$role . '_money'])) $item[$role . '_money'] += $amt;
                    if ($role === 'city_substation' || $role === 'city_site' || $role === 'city') {
                        $item['city_money'] += $amt;
                    }
                }
                // 结合抽奖记录扩展计算状态
                $rec = Db::name('lottery_record')->where('record_id', $rid)->field('ext')->find();
                $ext = [];
                if (!empty($rec)) { try { $ext = json_decode($rec['ext'] ?? '{}', true); } catch (\Throwable $e) { $ext = []; } }
                $order = $ext['order'] ?? [];
                $verified = (strtolower($order['status'] ?? '') === 'verified');
                $settled = (strtolower($ext['profit_settle'] ?? '') === 'settled');
                $item['settle_status'] = $settled ? 'settled' : ($verified ? 'verified' : 'pending');

                if ($settle_status === '' || $item['settle_status'] === $settle_status) {
                    $list['data']['list'][] = $item;
                }
            }

            // 若统一分润表为空，兼容回退到旧占位表 lottery_profit
            if ($count === 0) {
                $list = [ 'code' => 0, 'message' => 'success', 'data' => [ 'count' => 0, 'list' => [] ] ];
                $where_lp = [];
                if ($device_id > 0) $where_lp[] = ['device_id', '=', $device_id];
                if ($start_time && $end_time) {
                    $where_lp[] = ['create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
                } elseif (!$start_time && $end_time) {
                    $where_lp[] = ['create_time', '<=', date_to_time($end_time) ];
                } elseif ($start_time && !$end_time) {
                    $where_lp[] = ['create_time', '>=', date_to_time($start_time) ];
                }
                $groups_lp = Db::name('lottery_profit')
                    ->where($where_lp)
                    ->group('record_id')
                    ->field('record_id, max(create_time) as create_time')
                    ->order('create_time desc')
                    ->page($page, $page_size)
                    ->select()
                    ->toArray();
                foreach ($groups_lp as $g) {
                    $rid = (int)($g['record_id'] ?? 0);
                    if ($rid <= 0) continue;
                    $lp = Db::name('lottery_profit')->where('record_id', $rid)->find();
                    if (empty($lp)) continue;
                    $item = [
                        'record_id' => $rid,
                        'device_id' => (int)($lp['device_id'] ?? 0),
                        'site_id'   => (int)($lp['site_id'] ?? 0),
                        'amount_total'   => floatval($lp['amount_total'] ?? 0),
                        'platform_money' => floatval($lp['platform_money'] ?? 0),
                        'supplier_money' => floatval($lp['supplier_money'] ?? 0),
                        'promoter_money' => floatval($lp['promoter_money'] ?? 0),
                        'installer_money'=> floatval($lp['installer_money'] ?? 0),
                        'owner_money'    => floatval($lp['owner_money'] ?? 0),
                        'merchant_money' => 0.0,
                        'agent_money'    => 0.0,
                        'member_money'   => 0.0,
                        'city_money'     => 0.0,
                        'create_time'    => (int)($g['create_time'] ?? 0),
                        'settle_status'  => 'pending',
                    ];
                    // 结合抽奖记录扩展计算状态
                    $rec = Db::name('lottery_record')->where('record_id', $rid)->field('ext')->find();
                    $ext = [];
                    if (!empty($rec)) { try { $ext = json_decode($rec['ext'] ?? '{}', true); } catch (\Throwable $e) { $ext = []; } }
                    $order = $ext['order'] ?? [];
                    $verified = (strtolower($order['status'] ?? '') === 'verified');
                    $settled = (strtolower($ext['profit_settle'] ?? '') === 'settled');
                    $item['settle_status'] = $settled ? 'settled' : ($verified ? 'verified' : 'pending');
                    if ($settle_status === '' || $item['settle_status'] === $settle_status) {
                        $list['data']['list'][] = $item;
                    }
                }
                $list['data']['count'] = count($list['data']['list']);
                return $list;
            }

            // 过滤后重置 count
            $list['data']['count'] = count($list['data']['list']);
            return $list;
        } else {
            $devices = model('device_info')->getList([ ['site_id', '=', $this->site_id] ], 'device_id,device_sn');
            $this->assign('devices', $devices['data'] ?? []);
            return $this->fetch('profit/lists');
        }
    }

    /**
     * 标记某条记录已结算：更新抽奖记录扩展与分润行状态
     */
    public function settle()
    {
        $record_id = intval(input('id', 0));
        if (!$record_id) return $this->error('', '缺少参数');

        $exists = Db::name('lottery_record')->where('record_id', $record_id)->find();
        if (empty($exists)) return $this->error('', '关联抽奖记录不存在');
        $ext = [];
        try { $ext = json_decode($exists['ext'] ?? '{}', true) ?: []; } catch (\Throwable $e) { $ext = []; }
        $ext['profit_settle'] = 'settled';
        Db::name('lottery_record')->where('record_id', $record_id)->update([ 'ext' => json_encode($ext, JSON_UNESCAPED_UNICODE) ]);
        // 更新分润明细状态
        Db::name('ns_lottery_profit')->where('record_id', $record_id)->update([ 'status' => 'settled', 'settle_time' => time() ]);
        return $this->success([ 'id' => $record_id ]);
    }

    /**
     * 当前页 CSV 导出：输出各角色汇总金额
     */
    public function export()
    {
        $device_id = (int)input('device_id', 0);
        $start_time = input('start_time', '');
        $end_time = input('end_time', '');
        $where = [];
        if ($device_id > 0) $where[] = ['device_id', '=', $device_id];
        if ($start_time && $end_time) {
            $where[] = ['create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
        } elseif (!$start_time && $end_time) {
            $where[] = ['create_time', '<=', date_to_time($end_time) ];
        } elseif ($start_time && !$end_time) {
            $where[] = ['create_time', '>=', date_to_time($start_time) ];
        }

        $groups = Db::name('ns_lottery_profit')
            ->where($where)
            ->group('record_id')
            ->field('record_id, max(create_time) as create_time')
            ->order('create_time desc')
            ->select()
            ->toArray();

        $csv = "记录ID,设备ID,总金额,平台,商户,供应商,推广员,安装商,设备主,代理,会员,城市分站,创建时间\n";
        foreach ($groups as $g) {
            $rid = (int)($g['record_id'] ?? 0);
            $rows = Db::name('ns_lottery_profit')->where('record_id', $rid)->select()->toArray();
            $acc = [
                'device_id' => 0,
                'amount_total' => 0.0,
                'platform' => 0.0,
                'merchant' => 0.0,
                'supplier' => 0.0,
                'promoter' => 0.0,
                'installer'=> 0.0,
                'owner'    => 0.0,
                'agent'    => 0.0,
                'member'   => 0.0,
                'city'     => 0.0,
            ];
            foreach ($rows as $r) {
                $acc['device_id'] = $acc['device_id'] ?: (int)($r['device_id'] ?? 0);
                $amt = floatval($r['amount'] ?? 0);
                $role = strtolower($r['role'] ?? '');
                $acc['amount_total'] += $amt;
                if (isset($acc[$role])) $acc[$role] += $amt;
                if ($role === 'city_substation' || $role === 'city_site' || $role === 'city') $acc['city'] += $amt;
            }
            $csv .= implode(',', [
                $rid,
                $acc['device_id'],
                number_format($acc['amount_total'], 2, '.', ''),
                number_format($acc['platform'], 2, '.', ''),
                number_format($acc['merchant'], 2, '.', ''),
                number_format($acc['supplier'], 2, '.', ''),
                number_format($acc['promoter'], 2, '.', ''),
                number_format($acc['installer'], 2, '.', ''),
                number_format($acc['owner'], 2, '.', ''),
                number_format($acc['agent'], 2, '.', ''),
                number_format($acc['member'], 2, '.', ''),
                number_format($acc['city'], 2, '.', ''),
                date('Y-m-d H:i:s', (int)($g['create_time'] ?? time()))
            ]) . "\n";
        }
        return $this->success([ 'filename' => 'profit_page.csv', 'content' => $csv ]);
    }
}