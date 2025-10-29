<?php
/**
 * 设备管理控制器
 */
namespace addon\turntable\admin\controller;

use app\admin\controller\BaseAdmin;
use think\facade\Db;

class Device extends BaseAdmin
{
    /**
     * 设备列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [ ['site_id','=', $this->site_id] ];
            $status = input('status', '');
            if ($status !== '') {
                $condition[] = ['status', '=', $status];
            }
            $keyword = input('keyword', '');
            if ($keyword !== '') {
                $condition[] = ['device_sn', 'like', '%'.$keyword.'%'];
            }
            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);
            $model = model('device_info');
            // 按设备主键倒序
            $result = $model->pageList($condition, '*', 'device_id desc', $page, $page_size);
            return success(0, '', $result);
        } else {
            return $this->fetch('device/lists');
        }
    }

    /**
     * 新增设备
     */
    public function add()
    {
        if (request()->isAjax()) {
            $device_sn = input('device_sn', '');
            $board_id = (int)input('board_id', 0);
            $status = (int)input('status', 1);
            if ($device_sn === '') return success(-1, '设备SN不能为空', null);
            $data = [
                'site_id' => $this->site_id,
                'device_sn' => $device_sn,
                'board_id' => $board_id,
                'status' => $status,
                'create_time' => time(),
                'update_time' => time(),
            ];
            $model = model('device_info');
            $res = $model->add($data);
            if ($res) return success(0, '添加成功', ['id' => $res]);
            return success(-1, '添加失败', null);
        } else {
            return $this->fetch('device/add');
        }
    }

    /**
     * 编辑设备
     */
    public function edit()
    {
        $id = (int)input('id', 0);
        $model = model('device_info');
        if (request()->isAjax()) {
            if ($id <= 0) return success(-1, '参数错误', null);
            $device_sn = input('device_sn', '');
            $board_id = (int)input('board_id', 0);
            $status = (int)input('status', 1);
            // 归属角色字段
            $site_id     = (int)input('site_id', 0);
            $supplier_id = (int)input('supplier_id', 0);
            $promoter_id = (int)input('promoter_id', 0);
            $installer_id= (int)input('installer_id', 0);
            $owner_id    = (int)input('owner_id', 0);
            $agent_code  = input('agent_code', '');
            if ($device_sn === '') return success(-1, '设备SN不能为空', null);
            $data = [
                'device_sn' => $device_sn,
                'board_id' => $board_id,
                'status' => $status,
                // 归属角色字段写入
                'site_id' => $site_id,
                'supplier_id' => $supplier_id,
                'promoter_id' => $promoter_id,
                'installer_id'=> $installer_id,
                'owner_id'    => $owner_id,
                'agent_code'  => $agent_code,
                'update_time' => time(),
            ];
            // 依据设备主键更新
            $res = $model->update($data, [['device_id', '=', $id], ['site_id', '=', $this->site_id]]);
            if ($res) return success(0, '修改成功', null);
            return success(-1, '修改失败', null);
        } else {
            // 获取设备详情
            $info = $model->getInfo([['device_id', '=', $id], ['site_id', '=', $this->site_id]]);

            // 额外查询：当前绑定盘信息
            $board_info = [];
            if (!empty($info) && !empty($info['board_id'])) {
                $board_info = model('lottery_board')->getInfo([
                    ['board_id', '=', (int)$info['board_id']],
                    ['site_id', '=', $this->site_id]
                ]);
            }

            // 额外查询：当前有效的设备-价档绑定与价档信息
            $now = time();
            $bind_where = [
                ['device_id', '=', $id],
                ['status', '=', 1]
            ];
            // 有效期规则：开始时间<=现在，且(结束时间=0 或 结束时间>现在)
            $bind_where_effect = $bind_where;
            $bind_where_effect[] = ['start_time', '<=', $now];
            // 结束时间为0表示长期有效
            // ThinkPHP条件无法直接表达 or，这里先尝试取两类并择其一
            // 选最近创建的绑定记录
            $bind_info = model('device_price_bind')->getInfo($bind_where_effect, '*', 'create_time desc');
            if (empty($bind_info)) {
                $bind_where_open = $bind_where;
                $bind_where_open[] = ['end_time', '=', 0];
                $bind_info = model('device_price_bind')->getInfo($bind_where_open, '*', 'create_time desc');
            } else {
                // 若存在，且有结束时间需校验
                if (!empty($bind_info['end_time']) && $bind_info['end_time'] > 0 && $bind_info['end_time'] <= $now) {
                    $bind_info = [];
                }
            }

            $tier_info = [];
            if (!empty($bind_info) && !empty($bind_info['tier_id'])) {
                $tier_info = model('lottery_price_tier')->getInfo([
                    ['tier_id', '=', (int)$bind_info['tier_id']],
                    ['site_id', '=', $this->site_id]
                ]);
            }

            // 提供商家站点下拉选项（site_id, site_name）
            $shop_options = [];
            try {
                $shop_options = Db::name('shop')->field('site_id,site_name')->order('site_id asc')->select();
            } catch (\Throwable $e) {}

            $this->assign('info', $info);
            $this->assign('board_info', $board_info);
            $this->assign('bind_info', $bind_info);
            $this->assign('tier_info', $tier_info);
            $this->assign('shop_options', $shop_options);
            return $this->fetch('device/edit');
        }
    }

    /**
     * 删除设备
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $id = (int)input('id', 0);
            if ($id <= 0) return success(-1, '参数错误', null);
            $model = model('device_info');
            // 按主键删除
            $res = $model->delete([['device_id', '=', $id], ['site_id', '=', $this->site_id]]);
            if ($res) return success(0, '删除成功', null);
            return success(-1, '删除失败', null);
        }
    }
}