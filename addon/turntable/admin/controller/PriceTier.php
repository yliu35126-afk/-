<?php
/**
 * 价档管理控制器
 */
namespace addon\turntable\admin\controller;

use app\admin\controller\BaseAdmin;
use app\model\system\Group as GroupModel;

class PriceTier extends BaseAdmin
{
    /**
     * 价档列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [
                ['site_id', '=', $this->site_id]
            ];

            $status = input('status', '');
            if ($status !== '') {
                $condition[] = ['status', '=', $status];
            }

            $title = input('title', '');
            if (!empty($title)) {
                $condition[] = ['title', 'like', '%' . $title . '%'];
            }

            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);

            $model = model('lottery_price_tier');
            $result = $model->pageList($condition, '*', 'tier_id desc', $page, $page_size);
            return success(0, '', $result);
        } else {
            return $this->fetch('pricetier/lists');
        }
    }

    /**
     * 新增价档
     */
    public function add()
    {
        if (request()->isAjax()) {
            $title = input('title', '');
            $price = input('price', '0');
            $min_price = input('min_price', '0');
            $max_price = input('max_price', '0');
            $status = (int)input('status', 1);
            $profit_json = input('profit_json', '');

            if ($title === '') {
                return success(-1, '标题不能为空', null);
            }

            // 统一 profit_json 严密校验（与结算规则一致）
            $price_f = floatval($price);
            $map = [];
            try { $map = json_decode($profit_json ?: '{}', true) ?: []; } catch (\Throwable $je) { $map = []; }

            // 允许的角色键集（金额或比例）：
            // - 金额/比例：platform, supplier, promoter, installer, owner, merchant, shop, agent, member, city
            // - 区域固定金额：province, city, district
            // - 比例字段兼容：xxx_percent 或 { role: { percent: x } }
            $allowed_roles = ['platform','supplier','promoter','installer','owner','merchant','shop','agent','member','city','province','district'];

            // 解析比例值与固定额，并做范围与总额提示
            $percent_roles = ['platform','supplier','promoter','installer','owner','merchant','agent','member','city'];
            $fixed_roles   = ['province','city','district'];

            $percents = [];
            $fixed_sum = 0.0; // 固定金额合计（用于与价档价比对提示）
            $unknown_keys = [];

            // 提取 xxx_percent 与对象形态 percent
            $getPercent = function($role) use ($map) {
                $raw = null;
                // 仅当显式提供百分比键或对象形态时，才视为比例
                if (isset($map[$role.'_percent'])) { $raw = $map[$role.'_percent']; }
                elseif (isset($map[$role]) && is_array($map[$role]) && isset($map[$role]['percent'])) { $raw = $map[$role]['percent']; }
                if (!is_null($raw) && is_numeric($raw)) {
                    $v = floatval($raw);
                    // 支持 0-1 或 0-100 两种写法
                    return ($v > 1.000001) ? ($v / 100.0) : max(0.0, $v);
                }
                return 0.0;
            };
            // 提取固定金额（兼容 role_fixed / role_profit 字段）
            $getFixed = function($role) use ($map) {
                $cands = [ $role, $role.'_fixed', $role.'_profit' ];
                foreach ($cands as $k) {
                    if (isset($map[$k]) && is_numeric($map[$k])) {
                        $m = round(floatval($map[$k]), 2);
                        return ($m > 0) ? $m : 0.0;
                    }
                }
                return 0.0;
            };

            // 汇总比例
            $percent_sum = 0.0; $max_percent = 0.0;
            foreach ($percent_roles as $r) {
                $p = $getPercent($r);
                $percents[$r] = $p;
                if ($p > 0) { $percent_sum += $p; if ($p > $max_percent) $max_percent = $p; }
            }
            // 汇总固定金额（省/市/区）
            foreach ($fixed_roles as $r2) { $fixed_sum += $getFixed($r2); }

            // 检测未知键（仅提示，不阻断）
            foreach ($map as $k => $v) {
                $is_percent_key = (substr($k, -8) === '_percent');
                $base = $is_percent_key ? substr($k, 0, -8) : $k;
                if (strpos($base, 'group_') === 0) continue; // 兼容旧用户组写法
                if (!in_array($base, $allowed_roles) && !in_array($k, $allowed_roles)) {
                    $unknown_keys[] = strval($k);
                }
            }

            // 比例范围校验：单项[0,1]，总和<=1（宽容浮点）
            if ($max_percent > 1.000001 || $percent_sum > 1.000001) {
                return success(-1, '分润比例非法：单项或总和超过 100%', [ 'percent_sum' => round($percent_sum, 4), 'max_item' => round($max_percent,4) ]);
            }

            // 计算按金额配置的总额（系统/核心角色）
            // - 固定区域金额：province/city/district
            // - 角色金额：当数值>1 视为固定金额（而非比例）
            $role_fixed_sum = 0.0;
            foreach ($percent_roles as $r) {
                if (isset($map[$r]) && is_numeric($map[$r])) {
                    $val = floatval($map[$r]); if ($val > 1.000001) $role_fixed_sum += $val;
                }
                if (isset($map[$r.'_fixed']) && is_numeric($map[$r.'_fixed'])) {
                    $role_fixed_sum += max(0.0, floatval($map[$r.'_fixed']));
                }
                if (isset($map[$r.'_profit']) && is_numeric($map[$r.'_profit'])) {
                    $role_fixed_sum += max(0.0, floatval($map[$r.'_profit']));
                }
            }

            // 旧写法：各用户组金额合计不可超过价档金额（仍保留）
            $group_sum = 0.0;
            foreach ($map as $k => $v) {
                if (strpos($k, 'group_') === 0) {
                    $num = floatval($v); if ($num > 0) $group_sum += $num;
                }
            }
            // 新规则：核心 + 系统（固定金额）合计不可超过价档金额
            $fixed_total = $fixed_sum + $role_fixed_sum + $group_sum;
            if ($price_f > 0 && ($fixed_total - $price_f) > 1e-6) {
                return success(-1, '分润合计不能超过价档金额', [ 'fixed_sum' => round($fixed_sum,2), 'role_fixed_sum' => round($role_fixed_sum,2), 'group_sum' => round($group_sum,2) ]);
            }

            // 额外：将校验摘要写入 data 以便回显（非必需）
            $validation_summary = [
                'percent_sum' => round($percent_sum, 4),
                'fixed_sum' => round($fixed_sum, 2),
                'role_fixed_sum' => round($role_fixed_sum, 2),
                'group_sum' => round($group_sum, 2),
                'unknown_keys' => $unknown_keys,
            ];

            // 区间校验
            $min_f = floatval($min_price);
            $max_f = floatval($max_price);
            if ($min_f < 0 || $max_f < 0) {
                return success(-1, '区间价格不能为负数', null);
            }
            if ($max_f > 0 && $min_f > $max_f) {
                return success(-1, '最小价格不能大于最大价格', null);
            }

            // 跨价档区间重叠校验（同站点内）：
            // 规则：
            // - 若两者均为0视为“未设置区间”，不参与重叠判断；
            // - 仅设置下限或上限时，视为开区间：
            //   [min, +∞) 或 (0, max]；
            // - 两边都设置时，视为闭区间 [min, max]；
            // 重叠判定为区间有交集。
            $tiers = model('lottery_price_tier')->getList([
                ['site_id', '=', $this->site_id]
            ], 'tier_id,title,min_price,max_price', 'tier_id desc');
            $conflicts = [];
            foreach (($tiers ?: []) as $t) {
                $tmin = floatval($t['min_price'] ?? 0);
                $tmax = floatval($t['max_price'] ?? 0);
                // 跳过未设置区间的价档
                if (($tmin <= 0 && $tmax <= 0) || ($min_f <= 0 && $max_f <= 0)) continue;

                // 归一化：构造可比较的范围
                // 当前价档范围
                $cur_lo = ($min_f > 0) ? $min_f : 0.0;
                $cur_hi = ($max_f > 0) ? $max_f : INF;
                // 既有价档范围
                $old_lo = ($tmin > 0) ? $tmin : 0.0;
                $old_hi = ($tmax > 0) ? $tmax : INF;

                // 判断是否有交集：lo <= hi 且两段相交
                $lo = max($cur_lo, $old_lo);
                $hi = min($cur_hi, $old_hi);
                if ($lo <= $hi) {
                    $conflicts[] = [
                        'tier_id' => intval($t['tier_id']),
                        'title'   => strval($t['title']),
                        'min'     => $tmin,
                        'max'     => $tmax,
                    ];
                }
            }
            if (!empty($conflicts)) {
                // 返回首个冲突提示（前端可进一步细化展示）
                $c = $conflicts[0];
                $range_text = '区间 ' . (($c['min'] > 0) ? $c['min'] : '0') . '-' . (($c['max'] > 0) ? $c['max'] : '+∞');
                return success(-1, '跨价档区间重叠：与【'.$c['title'].'】发生冲突（'.$range_text.'）', [ 'conflicts' => $conflicts ]);
            }

            // 规范化键名与缺省补 0
            $canonical = ['platform','supplier','promoter','installer','owner','merchant','agent','member','city','province','district'];
            $norm = [];
            // 兼容 shop -> merchant 合并
            $shop_val = 0.0;
            if (isset($map['shop']) && is_numeric($map['shop'])) { $shop_val += floatval($map['shop']); }
            if (isset($map['shop_percent']) && is_numeric($map['shop_percent'])) {
                $shop_val += floatval($map['shop_percent']);
            }
            foreach ($canonical as $ck) {
                if ($ck === 'merchant') {
                    $val = 0.0;
                    if (isset($map['merchant']) && is_numeric($map['merchant'])) $val = floatval($map['merchant']);
                    if (isset($map['merchant_percent']) && is_numeric($map['merchant_percent'])) $val = floatval($map['merchant_percent']);
                    $norm['merchant'] = $val + $shop_val;
                } else {
                    // 百分比或金额原样保留为单值（比例≤1，金额>1）
                    if (isset($map[$ck]) && is_numeric($map[$ck])) {
                        $norm[$ck] = floatval($map[$ck]);
                    } elseif (isset($map[$ck.'_percent']) && is_numeric($map[$ck.'_percent'])) {
                        $norm[$ck] = floatval($map[$ck.'_percent']);
                    } else {
                        $norm[$ck] = 0.0;
                    }
                }
            }
            // 保留 group_* 项（数值型）
            foreach ($map as $k => $v) { if (strpos($k, 'group_') === 0 && is_numeric($v)) { $norm[$k] = floatval($v); } }

            $profit_json_norm = json_encode($norm, JSON_UNESCAPED_UNICODE);

            $data = [
                'site_id' => $this->site_id,
                'title' => $title,
                'price' => (float)$price,
                'min_price' => $min_f,
                'max_price' => $max_f,
                'status' => $status,
                'profit_json' => $profit_json_norm,
                'create_time' => time(),
                'update_time' => time(),
            ];

            // 可选：在扩展字段中存储校验摘要，便于审计（若表结构允许）
            // 此处仅保留 data，不写库

            $model = model('lottery_price_tier');
            $res = $model->add($data);
            if ($res) {
                return success(0, '添加成功', [ 'tier_id' => $res ]);
            }
            return success(-1, '添加失败', null);
        } else {
            // 提供角色（用户组）列表用于按角色分润设置
            $group_model = new GroupModel();
            $groups_res = $group_model->getGroupList([["site_id", "=", $this->site_id], ["app_module", "=", $this->app_module]]);
            $group_list = $groups_res['data'] ?? [];
            $this->assign('group_list', $group_list);
            return $this->fetch('pricetier/add');
        }
    }

    /**
     * 编辑价档
     */
    public function edit()
    {
        $tier_id = (int)input('tier_id', 0);
        $model = model('lottery_price_tier');

        if (request()->isAjax()) {
            if ($tier_id <= 0) {
                return success(-1, '参数错误', null);
            }
            $title = input('title', '');
            $price = input('price', '0');
            $min_price = input('min_price', '0');
            $max_price = input('max_price', '0');
            $status = (int)input('status', 1);
            $profit_json = input('profit_json', '');

            if ($title === '') {
                return success(-1, '标题不能为空', null);
            }

            // 统一 profit_json 严密校验（与结算规则一致）
            $price_f = floatval($price);
            $map = [];
            try { $map = json_decode($profit_json ?: '{}', true) ?: []; } catch (\Throwable $je) { $map = []; }

            $allowed_roles = ['platform','supplier','promoter','installer','owner','merchant','shop','agent','member','city','province','district'];
            $percent_roles = ['platform','supplier','promoter','installer','owner','merchant','agent','member','city'];
            $fixed_roles   = ['province','city','district'];

            $getPercent = function($role) use ($map) {
                $raw = null;
                // 仅显式百分比字段参与比例校验，避免小额(≤1元)误判为百分比
                if (isset($map[$role.'_percent'])) { $raw = $map[$role.'_percent']; }
                elseif (isset($map[$role]) && is_array($map[$role]) && isset($map[$role]['percent'])) { $raw = $map[$role]['percent']; }
                if (!is_null($raw) && is_numeric($raw)) {
                    $v = floatval($raw);
                    return ($v > 1.000001) ? ($v / 100.0) : max(0.0, $v);
                }
                return 0.0;
            };
            $getFixed = function($role) use ($map) {
                $cands = [ $role, $role.'_fixed', $role.'_profit' ];
                foreach ($cands as $k) {
                    if (isset($map[$k]) && is_numeric($map[$k])) {
                        $m = round(floatval($map[$k]), 2);
                        return ($m > 0) ? $m : 0.0;
                    }
                }
                return 0.0;
            };

            $percent_sum = 0.0; $max_percent = 0.0;
            foreach ($percent_roles as $r) { $p = $getPercent($r); if ($p > 0) { $percent_sum += $p; if ($p > $max_percent) $max_percent = $p; } }
            $fixed_sum = 0.0; foreach ($fixed_roles as $r2) { $fixed_sum += $getFixed($r2); }

            if ($max_percent > 1.000001 || $percent_sum > 1.000001) {
                return success(-1, '分润比例非法：单项或总和超过 100%', [ 'percent_sum' => round($percent_sum,4), 'max_item' => round($max_percent,4) ]);
            }

            $role_fixed_sum = 0.0;
            foreach ($percent_roles as $r) {
                if (isset($map[$r]) && is_numeric($map[$r])) { $val = floatval($map[$r]); if ($val > 1.000001) $role_fixed_sum += $val; }
                if (isset($map[$r.'_fixed']) && is_numeric($map[$r.'_fixed'])) { $role_fixed_sum += max(0.0, floatval($map[$r.'_fixed'])); }
                if (isset($map[$r.'_profit']) && is_numeric($map[$r.'_profit'])) { $role_fixed_sum += max(0.0, floatval($map[$r.'_profit'])); }
            }
            $group_sum = 0.0; foreach ($map as $k => $v) { if (strpos($k, 'group_') === 0) { $num = floatval($v); if ($num > 0) $group_sum += $num; } }
            $fixed_total = $fixed_sum + $role_fixed_sum + $group_sum;
            if ($price_f > 0 && ($fixed_total - $price_f) > 1e-6) { return success(-1, '分润合计不能超过价档金额', [ 'fixed_sum' => round($fixed_sum,2), 'role_fixed_sum' => round($role_fixed_sum,2), 'group_sum' => round($group_sum,2) ]); }

            // 区间校验
            $min_f = floatval($min_price);
            $max_f = floatval($max_price);
            if ($min_f < 0 || $max_f < 0) {
                return success(-1, '区间价格不能为负数', null);
            }
            if ($max_f > 0 && $min_f > $max_f) {
                return success(-1, '最小价格不能大于最大价格', null);
            }

            // 跨价档区间重叠校验（同站点内，排除当前）：
            $tiers = model('lottery_price_tier')->getList([
                ['site_id', '=', $this->site_id],
                ['tier_id', '<>', $tier_id]
            ], 'tier_id,title,min_price,max_price', 'tier_id desc');
            $conflicts = [];
            foreach (($tiers ?: []) as $t) {
                $tmin = floatval($t['min_price'] ?? 0);
                $tmax = floatval($t['max_price'] ?? 0);
                if (($tmin <= 0 && $tmax <= 0) || ($min_f <= 0 && $max_f <= 0)) continue;
                $cur_lo = ($min_f > 0) ? $min_f : 0.0;
                $cur_hi = ($max_f > 0) ? $max_f : INF;
                $old_lo = ($tmin > 0) ? $tmin : 0.0;
                $old_hi = ($tmax > 0) ? $tmax : INF;
                $lo = max($cur_lo, $old_lo);
                $hi = min($cur_hi, $old_hi);
                if ($lo <= $hi) {
                    $conflicts[] = [
                        'tier_id' => intval($t['tier_id']),
                        'title'   => strval($t['title']),
                        'min'     => $tmin,
                        'max'     => $tmax,
                    ];
                }
            }
            if (!empty($conflicts)) {
                $c = $conflicts[0];
                $range_text = '区间 ' . (($c['min'] > 0) ? $c['min'] : '0') . '-' . (($c['max'] > 0) ? $c['max'] : '+∞');
                return success(-1, '跨价档区间重叠：与【'.$c['title'].'】发生冲突（'.$range_text.'）', [ 'conflicts' => $conflicts ]);
            }

            // 规范化键名与缺省补 0
            $canonical = ['platform','supplier','promoter','installer','owner','merchant','agent','member','city','province','district'];
            $norm = [];
            $shop_val = 0.0;
            if (isset($map['shop']) && is_numeric($map['shop'])) { $shop_val += floatval($map['shop']); }
            if (isset($map['shop_percent']) && is_numeric($map['shop_percent'])) { $shop_val += floatval($map['shop_percent']); }
            foreach ($canonical as $ck) {
                if ($ck === 'merchant') {
                    $val = 0.0;
                    if (isset($map['merchant']) && is_numeric($map['merchant'])) $val = floatval($map['merchant']);
                    if (isset($map['merchant_percent']) && is_numeric($map['merchant_percent'])) $val = floatval($map['merchant_percent']);
                    $norm['merchant'] = $val + $shop_val;
                } else {
                    if (isset($map[$ck]) && is_numeric($map[$ck])) { $norm[$ck] = floatval($map[$ck]); }
                    elseif (isset($map[$ck.'_percent']) && is_numeric($map[$ck.'_percent'])) { $norm[$ck] = floatval($map[$ck.'_percent']); }
                    else { $norm[$ck] = 0.0; }
                }
            }
            foreach ($map as $k => $v) { if (strpos($k, 'group_') === 0 && is_numeric($v)) { $norm[$k] = floatval($v); } }
            $profit_json_norm = json_encode($norm, JSON_UNESCAPED_UNICODE);

            $data = [
                'title' => $title,
                'price' => (float)$price,
                'min_price' => $min_f,
                'max_price' => $max_f,
                'status' => $status,
                'profit_json' => $profit_json_norm,
                'update_time' => time(),
            ];

            $res = $model->update($data, [['tier_id', '=', $tier_id], ['site_id', '=', $this->site_id]]);
            if ($res) {
                return success(0, '修改成功', null);
            }
            return success(-1, '修改失败', null);
        } else {
            $info = $model->getInfo([['tier_id', '=', $tier_id], ['site_id', '=', $this->site_id]]);
            $this->assign('info', $info);
            // 角色（用户组）列表与分润预填
            $group_model = new GroupModel();
            $groups_res = $group_model->getGroupList([["site_id", "=", $this->site_id], ["app_module", "=", $this->app_module]]);
            $group_list = $groups_res['data'] ?? [];
            $this->assign('group_list', $group_list);
            $profit_map = [];
            if (!empty($info)) {
                $profit_map = json_decode($info['profit_json'] ?? '{}', true);
            }
            $this->assign('profit_map', $profit_map);
            return $this->fetch('pricetier/edit');
        }
    }

    /**
     * 删除价档
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $tier_id = (int)input('tier_id', 0);
            if ($tier_id <= 0) {
                return success(-1, '参数错误', null);
            }
            $model = model('lottery_price_tier');
            $res = $model->delete([["tier_id", "=", $tier_id], ["site_id", "=", $this->site_id]]);
            if ($res) {
                return success(0, '删除成功', null);
            }
            return success(-1, '删除失败', null);
        }
    }
}