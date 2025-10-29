<?php
/**
 * 分润明细可视化（Turntable 插件）
 */
namespace addon\turntable\admin\controller;

use app\admin\controller\BaseAdmin;
use think\facade\Db;

class Profit extends BaseAdmin
{
    /** 列表页 */
    public function lists()
    {
        if (request()->isAjax()) {
            $page      = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);
            $device_id = (int)input('device_id', 0);
            $start_ts  = (int)input('start_time', 0);
            $end_ts    = (int)input('end_time', 0);

            $where = [];
            if ($device_id > 0) $where[] = ['device_id', '=', $device_id];
            if ($start_ts > 0) $where[] = ['create_time', '>=', $start_ts];
            if ($end_ts > 0) $where[] = ['create_time', '<=', $end_ts];

            // 优先读取统一分润表 ns_lottery_profit 并按 record_id 聚合
            $base = Db::name('ns_lottery_profit')->where($where);
            // 分组计数（记录数）
            $count = (int)Db::name('ns_lottery_profit')->where($where)->group('record_id')->count();
            $groups = Db::name('ns_lottery_profit')
                ->where($where)
                ->group('record_id')
                ->field('record_id, max(create_time) as create_time')
                ->order('create_time desc')
                ->page($page, $page_size)
                ->select()
                ->toArray();

            $list = [];
            if ($count > 0) {
                foreach ($groups as $g) {
                    $rid = (int)($g['record_id'] ?? 0);
                    if ($rid <= 0) continue;
                    $rows = Db::name('ns_lottery_profit')->where('record_id', $rid)->select()->toArray();
                    $row = [
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
                    ];
                    foreach ($rows as $pr) {
                        $amt = floatval($pr['amount'] ?? 0);
                        $role = strval($pr['role'] ?? '');
                        switch ($role) {
                            case 'platform': $row['platform_money'] += $amt; break;
                            case 'supplier': $row['supplier_money'] += $amt; break;
                            case 'promoter': $row['promoter_money'] += $amt; break;
                            case 'installer':$row['installer_money']+= $amt; break;
                            case 'owner':    $row['owner_money']    += $amt; break;
                            case 'merchant': $row['merchant_money'] += $amt; break;
                            case 'agent':    $row['agent_money']    += $amt; break;
                            case 'member':   $row['member_money']   += $amt; break;
                            case 'city':     $row['city_money']     += $amt; break;
                            default: break;
                        }
                        $row['amount_total'] += $amt;
                        if (!$row['device_id'] && isset($pr['device_id'])) $row['device_id'] = (int)$pr['device_id'];
                        if (!$row['site_id'] && isset($pr['site_id'])) $row['site_id'] = (int)$pr['site_id'];
                    }
                    // 附加占位状态与角色快照
                    $settle = Db::name('lottery_settlement')->where('record_id', $rid)->find();
                    $row['settlement_status'] = strval($settle['status'] ?? '');
                    $ext = Db::name('lottery_record')->where('record_id', $rid)->value('ext');
                    $roles = [];
                    try { $j = json_decode($ext ?: '{}', true); $roles = $j['profit_roles'] ?? []; } catch (\Throwable $e) { $roles = []; }
                    $row['roles'] = $roles;
                    $list[] = $row;
                }
            } else {
                // 兼容回退旧占位表结构
                $query = Db::name('lottery_profit')->where($where)->order('create_time desc');
                $count = (int)$query->count();
                $list  = $query->page($page, $page_size)->select()->toArray();
                foreach ($list as &$row) {
                    $rid = (int)($row['record_id'] ?? 0);
                    $settle = Db::name('lottery_settlement')->where('record_id', $rid)->find();
                    $row['settlement_status'] = strval($settle['status'] ?? '');
                    $ext = Db::name('lottery_record')->where('record_id', $rid)->value('ext');
                    $roles = [];
                    try { $j = json_decode($ext ?: '{}', true); $roles = $j['profit_roles'] ?? []; } catch (\Throwable $e) { $roles = []; }
                    $row['roles'] = $roles;
                }
            }

            return success(0, '', [ 'list' => $list, 'count' => $count ]);
        } else {
            return $this->fetch('profit/lists');
        }
    }

    /** 重新触发某条记录的结算事件 */
    public function retrigger()
    {
        if (!request()->isAjax()) return success(-1, '仅支持Ajax', null);
        $record_id = (int)input('record_id', 0);
        if ($record_id <= 0) return success(-1, '参数错误', null);
        try {
            $exists = Db::name('lottery_settlement')->where('record_id', $record_id)->find();
            if (empty($exists)) {
                Db::name('lottery_settlement')->insert([
                    'record_id'   => $record_id,
                    'source_type' => 'manual',
                    'status'      => 'pending',
                    'payload'     => json_encode(['from' => 'admin_manual'], JSON_UNESCAPED_UNICODE),
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            } else {
                $cur_status = strval($exists['status'] ?? '');
                if ($cur_status !== 'pending') {
                    return success(-1, '已结算单不允许重复触发', null);
                }
                Db::name('lottery_settlement')->where('record_id', $record_id)->update([
                    'status' => 'pending',
                    'update_time' => time(),
                ]);
            }
            event('turntable_settlement', ['record_id' => $record_id]);
        } catch (\Throwable $e) {
            return success(-1, '触发失败: '.$e->getMessage(), null);
        }
        return success(0, '已触发结算', null);
    }
}