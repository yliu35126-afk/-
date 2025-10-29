<?php
namespace addon\turntable\event;

use think\facade\Db;
use think\facade\Log;

class TurntableSettlement
{
    /**
     * 监听处理抽奖结算
     * @param array $data ['record_id'=>int]
     */
    public function handle($data)
    {
        try {
            $record_id = intval($data['record_id'] ?? 0);
            if ($record_id <= 0) return true;

            $record = Db::name('lottery_record')->where('record_id', $record_id)->find();
            if (empty($record)) return true;

            $site_id  = intval($record['site_id'] ?? 0);
            $goods_id = intval($record['goods_id'] ?? 0);
            $tier_id  = intval($record['tier_id'] ?? 0);
            $amount   = floatval($record['amount'] ?? 0); // 价档价（抽奖金额）
            $device_id= intval($record['device_id'] ?? 0);

            // 读取价档分润配置；若记录未写入 tier_id，兜底按设备当前绑定的价档
            $tier = $tier_id ? Db::name('lottery_price_tier')->where([['tier_id','=',$tier_id]])->find() : [];
            if (empty($tier) && $device_id > 0) {
                try {
                    $bind = Db::name('device_price_bind')
                        ->where([ ['device_id', '=', $device_id], ['status', '=', 1] ])
                        ->order('start_time desc')
                        ->find();
                    $fallback_tid = intval($bind['tier_id'] ?? 0);
                    if ($fallback_tid > 0) {
                        $tier = Db::name('lottery_price_tier')->where('tier_id', $fallback_tid)->find() ?: [];
                    }
                } catch (\Throwable $be) { /* ignore */ }
            }
            $profit_cfg = [];
            if (!empty($tier)) {
                $profit_cfg = json_decode($tier['profit_json'] ?? '{}', true) ?: [];
            }

            // 当抽中实物奖品且记录金额为0（常见：goods 奖项不直接计入 record.amount），
            // 以价档价格作为抽奖金额参与毛利与分润计算，确保分润联调路径可落库。
            if ($amount <= 0 && !empty($tier)) {
                $tier_price = isset($tier['price']) ? floatval($tier['price']) : 0.0;
                if ($tier_price > 0) {
                    $amount = $tier_price;
                }
            }

            // 供货价：优先 goods.supply_price，兜底 goods.cost_price
            $supply_price = 0.0;
            $supplier_id = 0;
            if ($goods_id) {
                $goods = Db::name('goods')->where([["goods_id","=",$goods_id]])->field('supply_price,cost_price,supplier_id')->find();
                if (!empty($goods)) {
                    $supplier_id = intval($goods['supplier_id'] ?? 0);
                    $sp = isset($goods['supply_price']) ? floatval($goods['supply_price']) : 0.0;
                    $cp = isset($goods['cost_price']) ? floatval($goods['cost_price']) : 0.0;
                    $supply_price = $sp > 0 ? $sp : $cp;
                }
            }
            // 商品未标注供应商时，兜底使用设备归属的供应商
            if ($supplier_id <= 0 && $device_id > 0) {
                $devSupplierId = Db::name('device_info')->where('device_id', $device_id)->value('supplier_id');
                $supplier_id = intval($devSupplierId ?: 0);
            }

            // 毛利：价档价 - 供货价，最低为0
            $gross_profit = max(round($amount - $supply_price, 2), 0);
            // 结算日志：记录关键参数与分润模板
            try {
                Log::write('Settlement executed: record_id=' . $record_id . ', gross=' . $gross_profit . ', profit=' . json_encode($profit_cfg, JSON_UNESCAPED_UNICODE));
            } catch (\Throwable $le) {}

            // 计算分润：
            // - 若配置值全为数值且最大值/总和均<=1，则按毛利*比例；
            // - 否则视为固定金额；
            $values = array_values($profit_cfg ?: []);
            $sumRates = 0; $maxVal = 0; $allNumeric = true;
            foreach ($values as $v) {
                if (!is_numeric($v)) { $allNumeric = false; }
                $val = floatval($v);
                $sumRates += $val; if ($val > $maxVal) $maxVal = $val;
            }
            $treatAsRate = ($allNumeric && $maxVal <= 1.000001 && $sumRates <= 1.000001);

            $platform_money = 0; $supplier_money = 0; $promoter_money = 0; $installer_money = 0; $owner_money = 0; $merchant_money = 0; $agent_money = 0; $member_money = 0; $city_money = 0;
            $map = [
                'platform' => 'platform_money',
                'supplier' => 'supplier_money',
                'promoter' => 'promoter_money',
                'installer'=> 'installer_money',
                'owner'    => 'owner_money',
                // 兼容商户/店铺键名
                'merchant' => 'merchant_money',
                'shop'     => 'merchant_money',
                // 代理总额（若模板直接给出）
                'agent'    => 'agent_money',
                // 新增：会员与城市分站分润
                'member'   => 'member_money',
                'city'     => 'city_money',
            ];
            foreach ($map as $k => $col) {
                if (isset($profit_cfg[$k])) {
                    $num = floatval($profit_cfg[$k]);
                    ${$col} = $treatAsRate ? round($gross_profit * $num, 2) : round($num, 2);
                }
            }

            // 若模板未显式给出 agent，总额尝试按省/市/区细项或价档佣金合成
            if ($agent_money <= 0) {
                $agent_sum = 0.0;
                $keys = ['province','city','district'];
                foreach ($keys as $rk) {
                    if (isset($profit_cfg[$rk])) {
                        $v = floatval($profit_cfg[$rk]);
                        $agent_sum += $treatAsRate ? round($gross_profit * $v, 2) : round($v, 2);
                    }
                }
                if ($agent_sum > 0) { $agent_money = round($agent_sum, 2); }
                // 兜底：读取价档上的区域佣金（若存在）
                if ($agent_money <= 0 && !empty($tier)) {
                    $tiers = [ 'province_commission', 'city_commission', 'district_commission', 'commission_rate' ];
                    $sum = 0.0; foreach ($tiers as $tf) { if (isset($tier[$tf]) && is_numeric($tier[$tf])) { $sum += floatval($tier[$tf]); } }
                    if ($sum > 0) {
                        // 若佣金值看起来是比例（≤1），按毛利计算；否则视为固定额（极少见）
                        $asRate = ($sum <= 1.000001);
                        $agent_money = round($asRate ? ($gross_profit * $sum) : $sum, 2);
                    }
                }
            }

            // 校验总和不超过毛利
            $sumShare = $platform_money + $supplier_money + $promoter_money + $installer_money + $owner_money + $merchant_money + $agent_money + $member_money + $city_money;
            if ($sumShare > $gross_profit) {
                $scale = $gross_profit > 0 ? ($gross_profit / $sumShare) : 0;
                $platform_money = round($platform_money * $scale, 2);
                $supplier_money = round($supplier_money * $scale, 2);
                $promoter_money = round($promoter_money * $scale, 2);
                $installer_money= round($installer_money * $scale, 2);
                $owner_money    = round($owner_money * $scale, 2);
                $merchant_money = round($merchant_money * $scale, 2);
                $agent_money    = round($agent_money * $scale, 2);
                $member_money   = round($member_money * $scale, 2);
                $city_money     = round($city_money * $scale, 2);
            }
            // 平台兜底：若总分配金额小于毛利，差额归平台
            $sumShare = $platform_money + $supplier_money + $promoter_money + $installer_money + $owner_money + $merchant_money + $agent_money + $member_money + $city_money;
            if ($sumShare < $gross_profit) {
                $platform_money = round($platform_money + ($gross_profit - $sumShare), 2);
            }

            // 更新 lottery_profit 占位记录
            $profitRow = Db::name('lottery_profit')->where('record_id', $record_id)->find();
            if (!empty($profitRow)) {
                Db::name('lottery_profit')->where('record_id', $record_id)->update([
                    'platform_money' => $platform_money,
                    'supplier_money' => $supplier_money,
                    'promoter_money' => $promoter_money,
                    'installer_money'=> $installer_money,
                    'owner_money'    => $owner_money,
                ]);
            }

            // 供应商结算落账（若存在供应商）
            if ($supplier_id > 0) {
                Db::name('settlement_amount')->insert([
                    'record_id'    => $record_id,
                    'site_id'      => $site_id,
                    'supplier_id'  => $supplier_id,
                    'goods_id'     => $goods_id,
                    'amount'       => $amount,
                    'supply_price' => $supply_price,
                    'gross_profit' => $gross_profit,
                    'create_time'  => time(),
                ]);
            }

            // 更新占位表状态
            Db::name('lottery_settlement')->where('record_id', $record_id)->update([
                'status' => 'done',
                'update_time' => time(),
            ]);

            // 回写抽奖记录扩展：标记分润结算完成
            try {
                $ext = json_decode($record['ext'] ?? '{}', true) ?: [];
                $ext['profit_settle'] = 'settled';
                // 设备归属角色快照
                if ($device_id > 0) {
                    $dev = Db::name('device_info')->where('device_id', $device_id)->field('site_id,owner_id,installer_id,promoter_id,supplier_id,agent_code')->find();
                    if (!empty($dev)) {
                        $ext['profit_roles'] = [
                            'merchant_site_id' => intval($dev['site_id'] ?? 0),
                            'supplier_id'      => intval($dev['supplier_id'] ?? 0),
                            'promoter_id'      => intval($dev['promoter_id'] ?? 0),
                            'installer_id'     => intval($dev['installer_id'] ?? 0),
                            'owner_id'         => intval($dev['owner_id'] ?? 0),
                            'agent_code'       => strval($dev['agent_code'] ?? ''),
                        ];
                        // 解析店铺地区匹配代理ID（快照）
                        try {
                            $shop = Db::name('shop')->where('site_id', $site_id)->field('province,city,district')->find();
                            $prov_id = intval($shop['province'] ?? 0); $city_id2 = intval($shop['city'] ?? 0); $dist_id = intval($shop['district'] ?? 0);
                            $resolved_agent_id = 0;
                            if ($dist_id > 0) {
                                $ag = Db::name('ns_agent')->where([['level','=',3],['district_id','=',$dist_id],['status','=',1]])->find();
                                if (!empty($ag)) $resolved_agent_id = intval($ag['agent_id']);
                            }
                            if ($resolved_agent_id <= 0 && $city_id2 > 0) {
                                $ag = Db::name('ns_agent')->where([['level','=',2],['city_id','=',$city_id2],['status','=',1]])->find();
                                if (!empty($ag)) $resolved_agent_id = intval($ag['agent_id']);
                            }
                            if ($resolved_agent_id <= 0 && $prov_id > 0) {
                                $ag = Db::name('ns_agent')->where([['level','=',1],['province_id','=',$prov_id],['status','=',1]])->find();
                                if (!empty($ag)) $resolved_agent_id = intval($ag['agent_id']);
                            }
                            if ($resolved_agent_id > 0) {
                                $ext['profit_roles']['agent_id'] = $resolved_agent_id;
                            }
                            // 城市代理快照（仅市级）
                            $resolved_city_agent_id = 0;
                            if ($city_id2 > 0) {
                                $ag2 = Db::name('ns_agent')->where([['level','=',2],['city_id','=',$city_id2],['status','=',1]])->find();
                                if (!empty($ag2)) $resolved_city_agent_id = intval($ag2['agent_id']);
                            }
                            if ($resolved_city_agent_id > 0) {
                                $ext['profit_roles']['city_agent_id'] = $resolved_city_agent_id;
                            }
                        } catch (\Throwable $re) {}
                        // 统一分润明细写入 ns_lottery_profit（平台/城市使用幂等写库避免重复）
                        try {
                            // 幂等写库：按 (record_id, role) 更新或插入
                            $upsertProfit = function($role, $targetId, $money) use ($record_id, $site_id, $device_id, $record) {
                                $order_id2 = intval($record['order_id'] ?? 0);
                                $amt = round(floatval($money), 2);
                                if ($amt <= 0) return;
                                try {
                                    $exists = Db::name('ns_lottery_profit')->where([ ['record_id','=',$record_id], ['role','=',$role] ])->find();
                                    $data = [
                                        'record_id'  => $record_id,
                                        'order_id'   => $order_id2,
                                        'role'       => $role,
                                        'target_id'  => intval($targetId ?? 0),
                                        'amount'     => $amt,
                                        'site_id'    => $site_id,
                                        'device_id'  => $device_id,
                                        'status'     => 'pending',
                                    ];
                                    if (!empty($exists)) {
                                        Db::name('ns_lottery_profit')->where('id', intval($exists['id']))->update($data);
                                    } else {
                                        $data['create_time'] = time();
                                        Db::name('ns_lottery_profit')->insert($data);
                                    }
                                } catch (\Throwable $ue) { /* ignore */ }
                            };
                            $rows = [];
                            $order_id = intval($record['order_id'] ?? 0);
                            $now = time();
                            // 平台（兜底到站点）- 使用幂等写库
                            if ($platform_money > 0) { $upsertProfit('platform', intval($dev['site_id'] ?? 0), $platform_money); }
                            // 商户
                            if ($merchant_money > 0) {
                                $rows[] = [
                                    'record_id'  => $record_id,
                                    'order_id'   => $order_id,
                                    'role'       => 'merchant',
                                    'target_id'  => intval($dev['site_id'] ?? 0),
                                    'amount'     => $merchant_money,
                                    'site_id'    => $site_id,
                                    'device_id'  => $device_id,
                                    'create_time'=> $now,
                                ];
                            }
                            // 供应商
                            if ($supplier_money > 0) {
                                $rows[] = [
                                    'record_id'  => $record_id,
                                    'order_id'   => $order_id,
                                    'role'       => 'supplier',
                                    'target_id'  => intval($dev['supplier_id'] ?? $supplier_id),
                                    'amount'     => $supplier_money,
                                    'site_id'    => $site_id,
                                    'device_id'  => $device_id,
                                    'create_time'=> $now,
                                ];
                            }
                            // 会员（抽奖参与会员）
                            if ($member_money > 0) {
                                $rows[] = [
                                    'record_id'  => $record_id,
                                    'order_id'   => $order_id,
                                    'role'       => 'member',
                                    'target_id'  => intval($record['member_id'] ?? 0),
                                    'amount'     => $member_money,
                                    'site_id'    => $site_id,
                                    'device_id'  => $device_id,
                                    'create_time'=> $now,
                                ];
                            }
                            // 推广员
                            if ($promoter_money > 0) {
                                $rows[] = [
                                    'record_id'  => $record_id,
                                    'order_id'   => $order_id,
                                    'role'       => 'promoter',
                                    'target_id'  => intval($dev['promoter_id'] ?? 0),
                                    'amount'     => $promoter_money,
                                    'site_id'    => $site_id,
                                    'device_id'  => $device_id,
                                    'create_time'=> $now,
                                ];
                            }
                            // 安装员
                            if ($installer_money > 0) {
                                $rows[] = [
                                    'record_id'  => $record_id,
                                    'order_id'   => $order_id,
                                    'role'       => 'installer',
                                    'target_id'  => intval($dev['installer_id'] ?? 0),
                                    'amount'     => $installer_money,
                                    'site_id'    => $site_id,
                                    'device_id'  => $device_id,
                                    'create_time'=> $now,
                                ];
                            }
                            // 设备主
                            if ($owner_money > 0) {
                                $rows[] = [
                                    'record_id'  => $record_id,
                                    'order_id'   => $order_id,
                                    'role'       => 'owner',
                                    'target_id'  => intval($dev['owner_id'] ?? 0),
                                    'amount'     => $owner_money,
                                    'site_id'    => $site_id,
                                    'device_id'  => $device_id,
                                    'create_time'=> $now,
                                ];
                            }
                            // 代理（按店铺地区匹配最近一级代理）
                            if ($agent_money > 0) {
                                $agent_id = 0;
                                try {
                                    // 以站点地区为准，按 区→市→省 逐级匹配
                                    $shop = Db::name('shop')->where('site_id', $site_id)->field('province,city,district')->find();
                                    $prov_id = intval($shop['province'] ?? 0); $city_id2 = intval($shop['city'] ?? 0); $dist_id = intval($shop['district'] ?? 0);
                                    if ($dist_id > 0) {
                                        $ag = Db::name('ns_agent')->where([['level','=',3],['district_id','=',$dist_id],['status','=',1]])->find();
                                        if (!empty($ag)) $agent_id = intval($ag['agent_id']);
                                    }
                                    if ($agent_id <= 0 && $city_id2 > 0) {
                                        $ag = Db::name('ns_agent')->where([['level','=',2],['city_id','=',$city_id2],['status','=',1]])->find();
                                        if (!empty($ag)) $agent_id = intval($ag['agent_id']);
                                    }
                                    if ($agent_id <= 0 && $prov_id > 0) {
                                        $ag = Db::name('ns_agent')->where([['level','=',1],['province_id','=',$prov_id],['status','=',1]])->find();
                                        if (!empty($ag)) $agent_id = intval($ag['agent_id']);
                                    }
                                } catch (\Throwable $re) { $agent_id = 0; }

                                if ($agent_id > 0) {
                                    $rows[] = [
                                        'record_id'  => $record_id,
                                        'order_id'   => $order_id,
                                        'role'       => 'agent',
                                        'target_id'  => $agent_id,
                                        'amount'     => $agent_money,
                                        'site_id'    => $site_id,
                                        'device_id'  => $device_id,
                                        'create_time'=> $now,
                                    ];
                                }
                            }
                            // 城市分站（仅匹配城市级代理）- 使用幂等写库
                            if ($city_money > 0) {
                                $city_agent_id = 0;
                                try {
                                    $shop = Db::name('shop')->where('site_id', $site_id)->field('city')->find();
                                    $city_id2 = intval($shop['city'] ?? 0);
                                    if ($city_id2 > 0) {
                                        $ag = Db::name('ns_agent')->where([['level','=',2],['city_id','=',$city_id2],['status','=',1]])->find();
                                        if (!empty($ag)) $city_agent_id = intval($ag['agent_id']);
                                    }
                                } catch (\Throwable $re2) { $city_agent_id = 0; }
                                $upsertProfit('city', $city_agent_id, $city_money);
                            }
                            if (!empty($rows)) {
                                Db::name('ns_lottery_profit')->insertAll($rows);
                                // 代理流水：写入 agent/city 到 ns_agent_ledger
                                try {
                                    $ledgerRows = [];
                                    foreach ($rows as $r) {
                                        $role = strval($r['role'] ?? '');
                                        if ($role === 'agent' || $role === 'city') {
                                            $ledgerRows[] = [
                                                'agent_id'    => intval($r['target_id'] ?? 0),
                                                'role'        => $role,
                                                'record_id'   => $record_id,
                                                'amount'      => floatval($r['amount'] ?? 0),
                                                'site_id'     => $site_id,
                                                'device_id'   => $device_id,
                                                'type'        => 'profit',
                                                'create_time' => $now,
                                            ];
                                        }
                                    }
                                    if (!empty($ledgerRows)) {
                                        Db::name('ns_agent_ledger')->insertAll($ledgerRows);
                                    }
                                } catch (\Throwable $le) {}
                            }
                        } catch (\Throwable $ie) { /* 忽略写入失败以免影响主流程 */ }
                    }
                }
                Db::name('lottery_record')->where('record_id', $record_id)->update([
                    'ext' => json_encode($ext, JSON_UNESCAPED_UNICODE),
                ]);
            } catch (\Throwable $we) {}

        } catch (\Throwable $e) {
            // 标记失败但不抛出异常影响主流程
            try {
                $rid = intval($data['record_id'] ?? 0);
                Db::name('lottery_settlement')->where('record_id', $rid)->update([
                    'status' => 'failed',
                    'update_time' => time(),
                ]);
            } catch (\Throwable $ee) {}
        }

        return true;
    }
}