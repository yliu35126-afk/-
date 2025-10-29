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
            $only_latest = (int)input('only_latest', 1);

            $model = model('device_price_bind');
            // 若请求仅要最新记录：
            // 1) 当指定设备时，返回该设备的最近一条；
            // 2) 当未指定设备时，按设备分组去重，返回每个设备最近一条（分页）。
            if ($only_latest === 1) {
                if ($device_id > 0) {
                    $row = \think\facade\Db::name('device_price_bind')
                        ->where($condition)
                        ->order('start_time desc')
                        ->find();
                    $result = [
                        'count' => empty($row) ? 0 : 1,
                        'list' => empty($row) ? [] : [ $row ]
                    ];
                } else {
                    // 取按开始时间倒序的所有记录，然后按 device_id 去重保留第一条
                    $rows = \think\facade\Db::name('device_price_bind')
                        ->where($condition)
                        ->order('start_time desc')
                        ->select();
                    $rows = is_array($rows) ? $rows : ($rows ? $rows->toArray() : []);
                    $seen = [];
                    $unique = [];
                    foreach ($rows as $r) {
                        $did = intval($r['device_id'] ?? 0);
                        if ($did <= 0) continue;
                        if (isset($seen[$did])) continue;
                        $seen[$did] = true;
                        $unique[] = $r;
                    }
                    // 分页切片
                    $count = count($unique);
                    $offset = max(0, ($page - 1) * $page_size);
                    $list = array_slice($unique, $offset, $page_size);
                    $result = [ 'count' => $count, 'list' => $list ];
                }
            } else {
                $result = $model->pageList($condition, '*', 'id desc', $page, $page_size);
            }
            // 补充价档信息（标题与供货价范围）
            if (!empty($result['list'])) {
                $tier_ids = array_values(array_unique(array_column($result['list'], 'tier_id')));
                if (!empty($tier_ids)) {
                    try {
                        $tier_list = \think\facade\Db::name('lottery_price_tier')
                            ->where([ ['tier_id','in',$tier_ids] ])
                            ->field('tier_id,title,min_price,max_price')
                            ->select()->toArray();
                        $map = [];
                        foreach ($tier_list as $t) { $map[$t['tier_id']] = $t; }
                        foreach ($result['list'] as &$row) {
                            $tid = $row['tier_id'];
                            if (isset($map[$tid])) {
                                $row['tier_title'] = $map[$tid]['title'];
                                $row['tier_min_price'] = $map[$tid]['min_price'] ?? 0;
                                $row['tier_max_price'] = $map[$tid]['max_price'] ?? 0;
                            }
                        }
                        unset($row);
                    } catch (\Throwable $e) {}
                }
            }
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

            // 保证同一设备仅有一个当前生效的绑定：若存在旧的“未结束且生效”绑定，则自动结束
            try {
                $now = time();
                \think\facade\Db::name('device_price_bind')
                    ->where([
                        ['device_id', '=', $device_id],
                        ['status', '=', 1],
                        ['end_time', '=', 0]
                    ])
                    ->update(['end_time' => $now]);
            } catch (\Throwable $e) {}

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