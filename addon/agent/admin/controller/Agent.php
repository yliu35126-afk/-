<?php
namespace addon\agent\admin\controller;

use think\facade\Db;
use app\model\system\Address as AddressModel;
use app\admin\controller\BaseAdmin;

class Agent extends BaseAdmin
{
    /**
     * 代理列表（视图/接口）
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $level = input('level', '');
            $status = input('status', '');
            $title = input('title', '');
            $province_id = input('province_id', '');
            $city_id = input('city_id', '');
            $district_id = input('district_id', '');

            $query = Db::name('ns_agent');
            $condition = [];
            if ($level !== '') $condition[] = ['level', '=', (int)$level];
            if ($status !== '') $condition[] = ['status', '=', (int)$status];
            if ($title !== '') $condition[] = ['title', 'like', '%' . $title . '%'];
            if ($province_id !== '') $condition[] = ['province_id', '=', (int)$province_id];
            if ($city_id !== '') $condition[] = ['city_id', '=', (int)$city_id];
            if ($district_id !== '') $condition[] = ['district_id', '=', (int)$district_id];

            $total = $query->where($condition)->count();
            $list = $query->where($condition)->order('agent_id desc')->page($page, $page_size)->select()->toArray();

            // 增强展示字段：地区名称、级别名称
            $address_model = new AddressModel();
            foreach ($list as &$row) {
                $row['level_name'] = $row['level'] == 1 ? '省级' : ($row['level'] == 2 ? '市级' : ($row['level'] == 3 ? '区县' : '未知'));
                $row['province_name'] = $row['province_id'] ? (($address_model->getAreaInfo([['id', '=', $row['province_id']]])['data']['name'] ?? '') ) : '';
                $row['city_name'] = $row['city_id'] ? (($address_model->getAreaInfo([['id', '=', $row['city_id']]])['data']['name'] ?? '') ) : '';
                $row['district_name'] = $row['district_id'] ? (($address_model->getAreaInfo([['id', '=', $row['district_id']]])['data']['name'] ?? '') ) : '';
            }

            return success(0, 'success', [
                'page' => $page,
                'page_size' => $page_size,
                'list' => $list,
                'count' => $total,
            ]);
        } else {
            return $this->fetch('agent/lists');
        }
    }

    /** 地图分布视图 */
    public function map()
    {
        // 仅渲染视图，数据在前端通过 lists 接口聚合
        return $this->fetch('agent/map');
    }

    /** 价档下拉数据 */
    public function priceTiers()
    {
        if (!request()->isAjax()) return error(-1, '非法请求');
        try {
            $list = Db::name('lottery_price_tier')->field('id,tier_name,commission_rate,province_commission,city_commission,district_commission')->order('id desc')->select()->toArray();
            return success(0, 'ok', $list);
        } catch (\Throwable $e) {
            return error(-1, $e->getMessage());
        }
    }

    /** 添加代理（视图/接口） */
    public function add()
    {
        if (request()->isAjax()) {
            $site_id = input('site_id', 0);
            $title = input('title', '');
            $level = (int)input('level', 0);
            $province_id = (int)input('province_id', 0);
            $city_id = (int)input('city_id', 0);
            $district_id = (int)input('district_id', 0);
            $status = (int)input('status', 1);
            $member_id = (int)input('member_id', 0);
            $commission_rate = input('commission_rate', null);
            $price_tier_id = (int)input('price_tier_id', 0);
            $validity_start_date = trim((string)input('validity_start_date', '')) ?: null;
            $validity_end_date = trim((string)input('validity_end_date', '')) ?: null;
            $agent_phone = trim((string)input('agent_phone', '')) ?: null;
            $agent_contact_name = trim((string)input('agent_contact_name', '')) ?: null;

            if (!$level || !$title) {
                return error(-1, '请填写级别与标题');
            }
            // 唯一约束：同站点+级别+地区唯一
            $exists = Db::name('ns_agent')->where([
                ['site_id', '=', $site_id],
                ['level', '=', $level],
                ['province_id', '=', $province_id],
                ['city_id', '=', $city_id],
                ['district_id', '=', $district_id],
            ])->find();
            if ($exists) {
                return error(-1, '该地区对应级别的代理已存在');
            }

            // 若选择了价档，按级别自动绑定佣金比例（可被显式 commission_rate 覆盖）
            if ($price_tier_id) {
                try {
                    $tier = Db::name('lottery_price_tier')->where('id', $price_tier_id)->find();
                    if ($tier) {
                        if ($level == 1 && isset($tier['province_commission'])) $commission_rate = $tier['province_commission'];
                        elseif ($level == 2 && isset($tier['city_commission'])) $commission_rate = $tier['city_commission'];
                        elseif ($level == 3 && isset($tier['district_commission'])) $commission_rate = $tier['district_commission'];
                        elseif (isset($tier['commission_rate'])) $commission_rate = $tier['commission_rate'];
                    }
                } catch (\Throwable $e) {}
            }

            $data = [
                'site_id' => $site_id,
                'title' => $title,
                'level' => $level,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id' => $district_id,
                'member_id' => $member_id,
                'status' => $status,
                'commission_rate' => $commission_rate !== '' ? $commission_rate : null,
                'validity_start_date' => $validity_start_date,
                'validity_end_date' => $validity_end_date,
                'agent_phone' => $agent_phone,
                'agent_contact_name' => $agent_contact_name,
                'create_time' => time(),
                'update_time' => time(),
            ];
            Db::name('ns_agent')->insert($data);
            $this->addLog('新增代理：' . $title);
            return success(0, '新增成功');
        } else {
            return $this->fetch('agent/add');
        }
    }

    /** 编辑代理（视图/接口） */
    public function edit()
    {
        if (request()->isAjax()) {
            $agent_id = (int)input('agent_id', 0);
            if (!$agent_id) return error(-1, '参数错误');

            $title = input('title', '');
            $level = (int)input('level', 0);
            $province_id = (int)input('province_id', 0);
            $city_id = (int)input('city_id', 0);
            $district_id = (int)input('district_id', 0);
            $status = (int)input('status', 1);
            $member_id = (int)input('member_id', 0);
            $commission_rate = input('commission_rate', null);
            $price_tier_id = (int)input('price_tier_id', 0);
            $validity_start_date = trim((string)input('validity_start_date', '')) ?: null;
            $validity_end_date = trim((string)input('validity_end_date', '')) ?: null;
            $agent_phone = trim((string)input('agent_phone', '')) ?: null;
            $agent_contact_name = trim((string)input('agent_contact_name', '')) ?: null;

            if (!$level || !$title) {
                return error(-1, '请填写级别与标题');
            }
            // 唯一约束：同站点+级别+地区唯一（排除自己）
            $site_id = input('site_id', 0);
            $exists = Db::name('ns_agent')->where([
                ['site_id', '=', $site_id],
                ['level', '=', $level],
                ['province_id', '=', $province_id],
                ['city_id', '=', $city_id],
                ['district_id', '=', $district_id],
                ['agent_id', '<>', $agent_id],
            ])->find();
            if ($exists) {
                return error(-1, '该地区对应级别的代理已存在');
            }

            // 若选择了价档，按级别自动绑定佣金比例（可被显式 commission_rate 覆盖）
            if ($price_tier_id) {
                try {
                    $tier = Db::name('lottery_price_tier')->where('id', $price_tier_id)->find();
                    if ($tier) {
                        if ($level == 1 && isset($tier['province_commission'])) $commission_rate = $tier['province_commission'];
                        elseif ($level == 2 && isset($tier['city_commission'])) $commission_rate = $tier['city_commission'];
                        elseif ($level == 3 && isset($tier['district_commission'])) $commission_rate = $tier['district_commission'];
                        elseif (isset($tier['commission_rate'])) $commission_rate = $tier['commission_rate'];
                    }
                } catch (\Throwable $e) {}
            }

            $data = [
                'title' => $title,
                'level' => $level,
                'province_id' => $province_id,
                'city_id' => $city_id,
                'district_id' => $district_id,
                'member_id' => $member_id,
                'status' => $status,
                'commission_rate' => $commission_rate !== '' ? $commission_rate : null,
                'validity_start_date' => $validity_start_date,
                'validity_end_date' => $validity_end_date,
                'agent_phone' => $agent_phone,
                'agent_contact_name' => $agent_contact_name,
                'update_time' => time(),
            ];
            Db::name('ns_agent')->where('agent_id', $agent_id)->update($data);
            $this->addLog('编辑代理：' . $agent_id);
            return success(0, '已保存');
        } else {
            $agent_id = (int)input('agent_id', 0);
            if (!$agent_id) return $this->error('参数错误');
            $row = Db::name('ns_agent')->where('agent_id', $agent_id)->find();
            $this->assign('info', $row ?: []);
            return $this->fetch('agent/edit');
        }
    }

    /** 删除代理 */
    public function delete()
    {
        $agent_id = (int)input('agent_id', 0);
        if (!$agent_id) return error(-1, '参数错误');
        Db::name('ns_agent')->where('agent_id', $agent_id)->delete();
        $this->addLog('删除代理ID:' . $agent_id);
        return success(0, '已删除');
    }

    /** 批量启用 */
    public function batchEnable()
    {
        $ids = input('ids', '');
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return error(-1, '请选择数据');
        Db::name('ns_agent')->whereIn('agent_id', $ids)->update(['status' => 1, 'update_time' => time()]);
        $this->addLog('批量启用代理: ' . implode(',', $ids));
        return success(0, '操作成功');
    }

    /** 批量停用 */
    public function batchDisable()
    {
        $ids = input('ids', '');
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return error(-1, '请选择数据');
        Db::name('ns_agent')->whereIn('agent_id', $ids)->update(['status' => 0, 'update_time' => time()]);
        $this->addLog('批量停用代理: ' . implode(',', $ids));
        return success(0, '操作成功');
    }

    /** 批量修改佣金 */
    public function batchUpdateCommission()
    {
        $ids = input('ids', '');
        $commission_rate = input('commission_rate', null);
        if ($commission_rate === null || $commission_rate === '') return error(-1, '请填写佣金比例');
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return error(-1, '请选择数据');
        Db::name('ns_agent')->whereIn('agent_id', $ids)->update(['commission_rate' => $commission_rate, 'update_time' => time()]);
        $this->addLog('批量修改佣金: ' . implode(',', $ids));
        return success(0, '操作成功');
    }

    /** 批量修改有效期 */
    public function batchUpdateValidity()
    {
        $ids = input('ids', '');
        $validity_start_date = trim((string)input('validity_start_date', '')) ?: null;
        $validity_end_date = trim((string)input('validity_end_date', '')) ?: null;
        $ids = is_array($ids) ? $ids : array_filter(array_map('intval', explode(',', (string)$ids)));
        if (!$ids) return error(-1, '请选择数据');
        Db::name('ns_agent')->whereIn('agent_id', $ids)->update([
            'validity_start_date' => $validity_start_date,
            'validity_end_date' => $validity_end_date,
            'update_time' => time()
        ]);
        $this->addLog('批量修改有效期: ' . implode(',', $ids));
        return success(0, '操作成功');
    }

    /** 启用 */
    public function enable()
    {
        $agent_id = (int)input('agent_id', 0);
        if (!$agent_id) return error(-1, '参数错误');
        Db::name('ns_agent')->where('agent_id', $agent_id)->update(['status' => 1, 'update_time' => time()]);
        $this->addLog('启用代理ID:' . $agent_id);
        return success(0, '已启用');
    }

    /** 停用 */
    public function disable()
    {
        $agent_id = (int)input('agent_id', 0);
        if (!$agent_id) return error(-1, '参数错误');
        Db::name('ns_agent')->where('agent_id', $agent_id)->update(['status' => 0, 'update_time' => time()]);
        $this->addLog('停用代理ID:' . $agent_id);
        return success(0, '已停用');
    }
}