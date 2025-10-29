<?php
namespace addon\device_turntable\admin\controller;

use app\admin\controller\BaseAdmin;
use app\model\system\Group as GroupModel;

class PriceTier extends BaseAdmin
{
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [ ['site_id', '=', $this->site_id] ];
            $status = input('status', '');
            if ($status !== '') $condition[] = ['status', '=', $status];
            $title = input('title', '');
            if ($title !== '') $condition[] = ['title', 'like', '%' . $title . '%'];
            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);
            $result = model('lottery_price_tier')->pageList($condition, '*', 'tier_id desc', $page, $page_size);
            return success(0, '', $result);
        } else { return $this->fetch('pricetier/lists'); }
    }

    public function add()
    {
        if (request()->isAjax()) {
            $title = input('title', '');
            $price = input('price', '0');
            $status = (int)input('status', 1);
            $profit_json = input('profit_json', '');
            if ($title === '') return success(-1, '标题不能为空', null);
            $price_f = floatval($price);
            $sum = 0.0; $map = json_decode($profit_json ?: '{}', true);
            if (is_array($map)) { foreach ($map as $k => $v) { if (strpos($k, 'group_') === 0) { $num = floatval($v); if ($num > 0) $sum += $num; } } }
            if ($price_f > 0 && ($sum - $price_f) > 1e-6) return success(-1, '各用户组金额合计不可超过价档金额', null);
            $data = [ 'site_id' => $this->site_id, 'title' => $title, 'price' => (float)$price, 'status' => $status, 'profit_json' => $profit_json, 'create_time' => time(), 'update_time' => time() ];
            $res = model('lottery_price_tier')->add($data);
            if ($res) return success(0, '添加成功', [ 'tier_id' => $res ]);
            return success(-1, '添加失败', null);
        } else { $group_model = new GroupModel(); $groups_res = $group_model->getGroupList([["site_id", "=", $this->site_id], ["app_module", "=", $this->app_module]]); $group_list = $groups_res['data'] ?? []; $this->assign('group_list', $group_list); return $this->fetch('pricetier/add'); }
    }

    public function edit()
    {
        $tier_id = (int)input('tier_id', 0); $model = model('lottery_price_tier');
        if (request()->isAjax()) {
            if ($tier_id <= 0) return success(-1, '参数错误', null);
            $title = input('title', ''); $price = input('price', '0'); $status = (int)input('status', 1); $profit_json = input('profit_json', '');
            if ($title === '') return success(-1, '标题不能为空', null);
            $price_f = floatval($price); $sum = 0.0; $map = json_decode($profit_json ?: '{}', true);
            if (is_array($map)) { foreach ($map as $k => $v) { if (strpos($k, 'group_') === 0) { $num = floatval($v); if ($num > 0) $sum += $num; } } }
            if ($price_f > 0 && ($sum - $price_f) > 1e-6) return success(-1, '各用户组金额合计不可超过价档金额', null);
            $data = [ 'title' => $title, 'price' => (float)$price, 'status' => $status, 'profit_json' => $profit_json, 'update_time' => time() ];
            $res = $model->update($data, [['tier_id', '=', $tier_id], ['site_id', '=', $this->site_id]]);
            if ($res) return success(0, '修改成功', null);
            return success(-1, '修改失败', null);
        } else {
            $info = $model->getInfo([['tier_id', '=', $tier_id], ['site_id', '=', $this->site_id]]);
            $this->assign('info', $info);
            $group_model = new GroupModel(); $groups_res = $group_model->getGroupList([["site_id", "=", $this->site_id], ["app_module", "=", $this->app_module]]); $group_list = $groups_res['data'] ?? []; $this->assign('group_list', $group_list);
            $profit_map = []; if (!empty($info)) { $profit_map = json_decode($info['profit_json'] ?? '{}', true); }
            $this->assign('profit_map', $profit_map);
            return $this->fetch('pricetier/edit');
        }
    }

    public function delete()
    {
        if (request()->isAjax()) {
            $tier_id = (int)input('tier_id', 0);
            if ($tier_id <= 0) return success(-1, '参数错误', null);
            $res = model('lottery_price_tier')->delete([["tier_id", "=", $tier_id], ["site_id", "=", $this->site_id]]);
            if ($res) return success(0, '删除成功', null);
            return success(-1, '删除失败', null);
        }
    }
}