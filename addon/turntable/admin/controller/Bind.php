<?php
/**
 * 设备-价档绑定管理
 */
namespace addon\turntable\admin\controller;

use app\admin\controller\BaseAdmin;

class Bind extends BaseAdmin
{
    /**
     * 绑定列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [ ['status','=', 1] ];
            $device_id = (int)input('device_id', 0);
            if ($device_id > 0) {
                $condition[] = ['device_id', '=', $device_id];
            }
            $tier_id = (int)input('tier_id', 0);
            if ($tier_id > 0) {
                $condition[] = ['tier_id', '=', $tier_id];
            }
            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);

            $model = model('device_price_bind');
            $result = $model->pageList($condition, '*', 'id desc', $page, $page_size);
            return success(0, '', $result);
        } else {
            $device_id = (int)input('device_id', 0);
            $this->assign('device_id', $device_id);
            return $this->fetch('bind/lists');
        }
    }

    /**
     * 新增绑定
     */
    public function add()
    {
        if (request()->isAjax()) {
            $device_id = (int)input('device_id', 0);
            $tier_id = (int)input('tier_id', 0);
            $start_time = strtotime(input('start_time', '')) ?: time();
            $end_time = strtotime(input('end_time', '')) ?: 0;
            $status = (int)input('status', 1);

            if ($device_id <= 0 || $tier_id <= 0) {
                return success(-1, '设备或价档不能为空', null);
            }

            // 唯一限制：同设备同价档同起始时间唯一
            $exists = model('device_price_bind')->getInfo([
                ['device_id', '=', $device_id],
                ['tier_id', '=', $tier_id],
                ['start_time', '=', $start_time]
            ]);
            if (!empty($exists)) {
                return success(-1, '重复绑定记录', null);
            }

            $data = [
                'device_id' => $device_id,
                'tier_id' => $tier_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'status' => $status,
                'create_time' => time(),
            ];
            $res = model('device_price_bind')->add($data);
            if ($res) return success(0, '添加成功', ['id' => $res]);
            return success(-1, '添加失败', null);
        } else {
            $device_id = (int)input('device_id', 0);
            $this->assign('device_id', $device_id);
            return $this->fetch('bind/add');
        }
    }

    /**
     * 删除绑定
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $id = (int)input('id', 0);
            if ($id <= 0) return success(-1, '参数错误', null);
            $res = model('device_price_bind')->delete([['id', '=', $id]]);
            if ($res) return success(0, '删除成功', null);
            return success(-1, '删除失败', null);
        }
    }
}