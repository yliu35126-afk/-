<?php
namespace app\api\controller;

use think\facade\Db;
use addon\turntable\model\Lottery as LotteryModel;

/**
 * 抽奖网关控制器（薄代理）
 * 目的：为联调提供稳定入口，转发到设备版转盘插件控制器
 * 路由示例：
 * - /index.php/api/lotterygateway/amounttiers
 * - /index.php/api/lotterygateway/prizelist
 * - /index.php/api/lotterygateway/draw
 * - /index.php/api/lotterygateway/address
 * - /index.php/api/lotterygateway/verify
 * - /index.php/api/lotterygateway/record
 */
class LotteryGateway extends BaseApi
{
    /** 设备可用价档列表（代理至 addon/device_turntable/api/controller/Turntable::amountTiers） */
    public function amounttiers()
    {
        $controller = new \addon\device_turntable\api\controller\Turntable();
        return $controller->amountTiers();
    }

    /** 16格奖品列表（代理至 addon/device_turntable/api/controller/Turntable::prizeList） */
    public function prizelist()
    {
        $controller = new \addon\device_turntable\api\controller\Turntable();
        return $controller->prizeList();
    }

    /** 抽一次（免 token 联调适配：接受 { deviceCode, userId }） */
    public function draw()
    {
        $deviceCode = trim(strval($this->params['deviceCode'] ?? ''));
        $userId     = intval($this->params['userId'] ?? 0);
        if ($deviceCode === '' || $userId <= 0) {
            return $this->response($this->error('', '缺少参数'));
        }

        // 直调模型以便免 token 联调
        $model = new LotteryModel();
        $res = $model->draw([
            'member_id' => $userId,
            'device_sn' => $deviceCode,
        ]);
        if (($res['code'] ?? -1) < 0) {
            return $this->response($res);
        }

        // 规范化返回结构，并补充 record/verify 信息用于后续核销联调
        $device = Db::name('device_info')->where('device_sn', $deviceCode)->find();
        $deviceId = intval($device['device_id'] ?? 0);
        $last = $deviceId ? Db::name('lottery_record')
            ->where([['member_id', '=', $userId], ['device_id', '=', $deviceId]])
            ->order('record_id desc')->find() : null;
        $recordId = intval($last['record_id'] ?? 0);
        $ext = $last ? (json_decode($last['ext'] ?? '{}', true) ?: []) : [];
        $order = $ext['order'] ?? [];

        $hit = $res['data']['hit'] ?? [];
        return $this->response(success(0, 'success', [
            'resultId'  => $recordId ?: ($hit['slot_id'] ?? 0),
            'prizeId'   => intval($hit['goods_id'] ?? 0),
            'prizeName' => strval($hit['title'] ?? ''),
            'verifyCode'=> $order['verify_code'] ?? null,
        ]));
    }

    /** 收件信息填写（代理至 addon/device_turntable/api/controller/Turntable::address） */
    public function address()
    {
        $controller = new \addon\device_turntable\api\controller\Turntable();
        return $controller->address();
    }

    /** 到店核销（代理至 addon/device_turntable/api/controller/Turntable::verify） */
    public function verify()
    {
        $controller = new \addon\device_turntable\api\controller\Turntable();
        return $controller->verify();
    }

    /** 抽奖记录（代理至 addon/device_turntable/api/controller/Turntable::record） */
    public function record()
    {
        $controller = new \addon\device_turntable\api\controller\Turntable();
        return $controller->record();
    }

    /** 分润联调：检查指定记录的分润与结算占位 */
    public function profit()
    {
        $record_id = intval($this->params['recordId'] ?? 0);
        if ($record_id <= 0) return $this->response($this->error('', '缺少参数'));

        $record = model('lottery_record')->getInfo([[ 'record_id', '=', $record_id ]], '*');
        if (empty($record)) return $this->response($this->error('', '记录不存在'));
        $ext = json_decode($record['ext'] ?: '{}', true);

        $profits = \think\facade\Db::name('ns_lottery_profit')->where('record_id', $record_id)->select();
        // 兼容回退：若统一分润表暂无记录，回读占位表并转换为行视图
        try {
            $isEmpty = (is_array($profits) && count($profits) === 0) || (method_exists($profits, 'isEmpty') && $profits->isEmpty());
        } catch (\Throwable $e0) { $isEmpty = false; }
        if ($isEmpty) {
            $lp = \think\facade\Db::name('lottery_profit')->where('record_id', $record_id)->find();
            if (!empty($lp)) {
                $profits = [
                    [ 'record_id' => $record_id, 'role' => 'platform',  'amount' => floatval($lp['platform_money'] ?? 0) ],
                    [ 'record_id' => $record_id, 'role' => 'supplier',  'amount' => floatval($lp['supplier_money'] ?? 0) ],
                    [ 'record_id' => $record_id, 'role' => 'promoter',  'amount' => floatval($lp['promoter_money'] ?? 0) ],
                    [ 'record_id' => $record_id, 'role' => 'installer', 'amount' => floatval($lp['installer_money'] ?? 0) ],
                    [ 'record_id' => $record_id, 'role' => 'owner',     'amount' => floatval($lp['owner_money'] ?? 0) ],
                ];
            }
        }
        $settlement = \think\facade\Db::name('lottery_settlement')->where('record_id', $record_id)->find();

        // 追加调试信息：读取价档的分润配置与设备角色
        // 优先取记录中的 tier_id；若为0则读取设备当前绑定的生效价档
        $tier_id = intval($record['tier_id'] ?? 0);
        if ($tier_id <= 0) {
            $bind = \think\facade\Db::name('device_price_bind')
                ->where([ ['device_id', '=', intval($record['device_id'] ?? 0)], ['status', '=', 1] ])
                ->order('start_time desc')
                ->find();
            $tier_id = intval($bind['tier_id'] ?? 0);
        }
        $tier = $tier_id > 0 ? \think\facade\Db::name('lottery_price_tier')->where('tier_id', $tier_id)->find() : null;
        $profitCfg = [];
        try { $profitCfg = json_decode($tier['profit_json'] ?? '{}', true); } catch (\Throwable $e) { $profitCfg = []; }

        $device = \think\facade\Db::name('device_info')->where('device_id', intval($record['device_id'] ?? 0))->find();
        $roles = [
            'supplier_id' => intval($device['supplier_id'] ?? 0),
            'promoter_id' => intval($device['promoter_id'] ?? 0),
            'installer_id'=> intval($device['installer_id'] ?? 0),
            'owner_id'    => intval($device['owner_id'] ?? 0),
        ];

        // 计算角色分润（调试用）并生成详细项
        $amt = floatval($record['amount'] ?? 0);
        $getRatio = function($v) { $n = is_numeric($v) ? floatval($v) : 0.0; return $n > 1 ? ($n / 100.0) : max(0.0, $n); };
        $computed = [];
        $details = [];
        if ($amt > 0 && !empty($profitCfg)) {
            $roleDefs = [
                ['role' => 'platform',  'key' => 'platform_percent',  'fallback' => ['platform','percent']],
                ['role' => 'supplier',  'key' => 'supplier_percent',  'fallback' => ['supplier','percent']],
                ['role' => 'promoter',  'key' => 'promoter_percent',  'fallback' => ['promoter','percent']],
                ['role' => 'installer', 'key' => 'installer_percent', 'fallback' => ['installer','percent']],
                ['role' => 'owner',     'key' => 'owner_percent',     'fallback' => ['owner','percent']],
                // 商户：兼容 merchant_percent 或 merchant/shop.percent
                ['role' => 'merchant',  'key' => 'merchant_percent',  'fallback' => ['merchant','percent']],
            ];
            foreach ($roleDefs as $def) {
                $raw = $profitCfg[$def['key']] ?? ($profitCfg[$def['fallback'][0]]['percent'] ?? 0);
                $ratio = $getRatio($raw);
                $final = round($amt * $ratio, 2);
                $computed[$def['role']] = $final;
                $details[] = [
                    'role' => $def['role'],
                    'configKey' => $def['key'],
                    'configValueRaw' => $raw,
                    'percentNormalized' => $ratio,
                    'baseAmount' => $amt,
                    'finalAmount' => $final,
                    'targetId' => intval($roles[$def['role'].'_id'] ?? 0),
                    'source' => [
                        'tierId' => $tier_id,
                        'tierField' => 'lottery_price_tier.profit_json',
                        'deviceRolesField' => 'device_info.{supplier_id,promoter_id,installer_id,owner_id}',
                    ],
                ];
            }

            // 代理：若模板直接给出 agent_percent 或 agent.percent，否则按省/市/区细项与价档佣金兜底
            $agentRaw = $profitCfg['agent_percent'] ?? ($profitCfg['agent']['percent'] ?? null);
            $agentVal = 0.0; $agentSrc = 'lottery_price_tier.profit_json.agent_percent';
            if ($agentRaw !== null) {
                $agentVal = round($amt * $getRatio($agentRaw), 2);
            } else {
                $agentSrc = 'lottery_price_tier.profit_json.{province,city,district} or tier columns';
                $sum = 0.0;
                foreach (['province','city','district'] as $rk) {
                    if (isset($profitCfg[$rk])) { $sum += round($amt * $getRatio($profitCfg[$rk]), 2); }
                }
                if ($sum <= 0 && !empty($tier)) {
                    $tc = 0.0;
                    foreach (['province_commission','city_commission','district_commission','commission_rate'] as $tf) {
                        if (isset($tier[$tf]) && is_numeric($tier[$tf])) $tc += floatval($tier[$tf]);
                    }
                    if ($tc > 0) { $sum = round($amt * ($tc <= 1.000001 ? $tc : ($tc/100.0)), 2); }
                }
                $agentVal = round($sum, 2);
            }
            if ($agentVal > 0) {
                $computed['agent'] = $agentVal;
                $details[] = [
                    'role' => 'agent',
                    'configKey' => 'agent_percent',
                    'configValueRaw' => $agentRaw,
                    'percentNormalized' => null,
                    'baseAmount' => $amt,
                    'finalAmount' => $agentVal,
                    'targetId' => 0,
                    'source' => [ 'tierId' => $tier_id, 'tierField' => $agentSrc ],
                ];
            }
        }

        // 统计校验项
        $sumComputed = 0.0; foreach ($computed as $v) { $sumComputed += floatval($v); } $sumComputed = round($sumComputed, 2);
        $equalsAmount = abs($sumComputed - round($amt, 2)) < 0.005;
        $profitRows = [];
        try { $profitRows = is_array($profits) ? $profits : (method_exists($profits, 'toArray') ? $profits->toArray() : []); } catch (\Throwable $e) { $profitRows = []; }
        $byRole = [];
        foreach ($profitRows as $pr) { $r = strval($pr['role'] ?? ''); $byRole[$r] = ($byRole[$r] ?? 0) + floatval($pr['amount'] ?? 0); }
        $mismatchRoles = [];
        foreach ($computed as $role => $val) { $rowAmt = round(floatval($byRole[$role] ?? 0), 2); if (abs($rowAmt - round($val, 2)) >= 0.005) { $mismatchRoles[] = $role; } }
        $validation = [
            'sumEqualsAmount' => $equalsAmount,
            'sumComputed' => $sumComputed,
            'recordAmount' => round($amt, 2),
            'rowsExist' => count($profitRows) > 0,
            'mismatchRoles' => $mismatchRoles,
        ];

        // 订单维度汇总视图
        $orderSummary = null;
        $order_id = intval($record['order_id'] ?? 0);
        if ($order_id > 0) {
            $orderRows = \think\facade\Db::name('ns_lottery_profit')->where('order_id', $order_id)->select();
            $roleTotals = [];
            $orderTotal = 0.0;
            foreach ($orderRows as $row) { $role = strval($row['role'] ?? ''); $amt2 = floatval($row['amount'] ?? 0); $roleTotals[$role] = ($roleTotals[$role] ?? 0) + $amt2; $orderTotal += $amt2; }
            $orderSummary = [ 'orderId' => $order_id, 'total' => round($orderTotal,2), 'roles' => $roleTotals ];
        }

        // 兼容展示两类分润来源，避免混淆
        $profitGroupsModel = [];
        $profitRolesCallback = [];
        try {
            if (is_array($ext)) {
                $profitGroupsModel = $ext['profit_groups'] ?? [];
                $profitRolesCallback = $ext['profit_roles'] ?? [];
            }
        } catch (\Throwable $e) {}

        return $this->response($this->success([
            'recordId' => $record_id,
            'profitGroups' => $profitGroupsModel,
            'profitRoles' => $profitRolesCallback,
            'lotteryProfit' => $profits ?: [],
            'settlement' => $settlement ?: null,
            'tierIdEffective' => $tier_id,
            'tierProfitConfig' => $profitCfg,
            'deviceRoles' => $roles,
            'roleAmountsComputed' => $computed,
            'details' => $details,
            'validation' => $validation,
            'orderSummary' => $orderSummary,
        ]));
    }

    /** 订单维度汇总（财务对账用） */
    public function order_summary()
    {
        $order_id = intval($this->params['orderId'] ?? $this->params['order_id'] ?? 0);
        if ($order_id <= 0) return $this->response($this->error('', '缺少参数'));

        $rows = Db::name('ns_lottery_profit')->where('order_id', $order_id)->select();
        $roleTotals = []; $orderTotal = 0.0; $recordIds = [];
        foreach ($rows as $row) { $role = strval($row['role'] ?? ''); $amt = floatval($row['amount'] ?? 0); $roleTotals[$role] = ($roleTotals[$role] ?? 0) + $amt; $orderTotal += $amt; $recordIds[] = intval($row['record_id'] ?? 0); }
        $recordIds = array_values(array_unique(array_filter($recordIds)));

        $recordsOut = [];
        foreach ($recordIds as $rid) {
            $rec = model('lottery_record')->getInfo([[ 'record_id', '=', $rid ]], '*');
            if (empty($rec)) continue;
            $ext = []; try { $ext = json_decode($rec['ext'] ?? '{}', true); } catch (\Throwable $e) { $ext = []; }
            $settle = Db::name('lottery_settlement')->where('record_id', $rid)->find();
            $recordsOut[] = [
                'record_id' => intval($rec['record_id']),
                'order_id' => intval($rec['order_id'] ?? 0),
                'amount' => floatval($rec['amount'] ?? 0),
                'status' => strval(($ext['order']['status'] ?? '') ?: ''),
                'settlement' => $settle ?: null,
                'profit_roles' => $ext['profit_roles'] ?? [],
                'profit_groups' => $ext['profit_groups'] ?? [],
            ];
        }

        return $this->response($this->success([
            'orderId' => $order_id,
            'total' => round($orderTotal, 2),
            'roles' => $roleTotals,
            'records' => $recordsOut,
        ]));
    }

    /** 时间窗口聚合（按设备、站点与角色拆分） */
    public function aggregate()
    {
        $start = intval($this->params['start'] ?? 0);
        $end = intval($this->params['end'] ?? 0);
        $device_id = intval($this->params['deviceId'] ?? 0);
        $site_id = intval($this->params['siteId'] ?? 0);

        // 先通过 lottery_record 过滤时间窗口与设备/站点
        $recordWhere = [];
        if ($start && $end && $end > $start) { $recordWhere[] = ['create_time', 'between', [$start, $end]]; }
        if ($device_id > 0) { $recordWhere[] = ['device_id', '=', $device_id]; }
        if ($site_id > 0) { $recordWhere[] = ['site_id', '=', $site_id]; }
        $recordIds = [];
        $records = Db::name('lottery_record')->where($recordWhere)->field('record_id,device_id')->select();
        $deviceMap = []; $siteMap = [];
        $deviceIds = [];
        foreach ($records as $r) { $rid = intval($r['record_id']); $recordIds[] = $rid; $did = intval($r['device_id'] ?? 0); if ($did>0) { $deviceMap[$rid] = $did; $deviceIds[] = $did; } }
        $deviceIds = array_values(array_unique(array_filter($deviceIds)));
        $siteByDevice = [];
        if (!empty($deviceIds)) {
            $siteByDevice = Db::name('device_info')->where([ ['device_id','in',$deviceIds] ])->column('site_id','device_id');
        }
        foreach ($deviceMap as $rid => $did) { $siteMap[$rid] = intval($siteByDevice[$did] ?? 0); }

        $profitWhere = [];
        if (!empty($recordIds)) { $profitWhere[] = ['record_id', 'in', $recordIds]; }
        $profitRows = Db::name('ns_lottery_profit')->where($profitWhere)->select();
        $totals = [ 'sum' => 0.0, 'roles' => [], 'devices' => [], 'sites' => [] ];
        $orderIds = [];
        foreach ($profitRows as $row) {
            $amt = floatval($row['amount'] ?? 0); $role = strval($row['role'] ?? ''); $rid = intval($row['record_id'] ?? 0); $oid = intval($row['order_id'] ?? 0);
            $totals['sum'] += $amt; $totals['roles'][$role] = ($totals['roles'][$role] ?? 0) + $amt;
            $orderIds[] = $oid;
            $did = intval($deviceMap[$rid] ?? 0); $sid = intval($siteMap[$rid] ?? 0);
            if ($did > 0) $totals['devices'][$did] = ($totals['devices'][$did] ?? 0) + $amt;
            if ($sid > 0) $totals['sites'][$sid] = ($totals['sites'][$sid] ?? 0) + $amt;
        }
        $orderIds = array_values(array_unique(array_filter($orderIds)));

        // 计数与状态
        $counts = [ 'orders' => count($orderIds), 'records' => count($recordIds) ];
        $verified = 0; $settled = 0; $pending = 0;
        foreach (array_keys($deviceMap) as $rid) {
            $settle = Db::name('lottery_settlement')->where('record_id', intval($rid))->find();
            $st = strval($settle['status'] ?? '');
            if ($st === 'verified') $verified++;
            else if ($st === 'settled') $settled++;
            else $pending++;
        }
        $counts['verified'] = $verified; $counts['settled'] = $settled; $counts['pending'] = $pending;

        return $this->response($this->success([
            'window' => [ 'start' => $start, 'end' => $end ],
            'totals' => [ 'sum' => round($totals['sum'],2), 'roles' => $totals['roles'], 'devices' => $totals['devices'], 'sites' => $totals['sites'] ],
            'counts' => $counts,
        ]));
    }
}