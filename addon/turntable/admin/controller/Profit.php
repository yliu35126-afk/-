<?php
namespace addon\turntable\admin\controller;

use app\admin\controller\BaseAdmin;
use app\model\system\Group as GroupModel;

class Profit extends BaseAdmin
{
    /**
     * 分润账单列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [ ['site_id', '=', $this->site_id] ];
            $device_id = input('device_id', 0);
            $tier_id   = input('tier_id', 0);
            $start_time = input('start_time', '');
            $end_time   = input('end_time', '');
            $settle_status = input('settle_status', ''); // pending | verified | settled

            if ($device_id) $condition[] = ['device_id', '=', $device_id];
            if ($tier_id)   $condition[] = ['tier_id', '=', $tier_id];
            if ($start_time && $end_time) {
                $condition[] = ['create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            } elseif (!$start_time && $end_time) {
                $condition[] = ['create_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && !$end_time) {
                $condition[] = ['create_time', '>=', date_to_time($start_time) ];
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $list = model('lottery_profit')->pageList($condition, 'id desc', '*', $page, $page_size);

            // 补充派发/核销状态（从抽奖记录ext.order派生）与结算标记（ext.profit_settle）以及分组分润明细（ext.profit_groups）
            $rows = $list['data']['list'] ?? [];
            $filtered = [];
            foreach ($rows as $row) {
                $record = model('lottery_record')->getInfo([ ['record_id', '=', $row['record_id']] ], 'ext');
                $ext = [];
                if (!empty($record)) {
                    $ext = json_decode($record['ext'] ?? '{}', true);
                }
                $order = $ext['order'] ?? [];
                $verified = (strtolower($order['status'] ?? '') === 'verified');
                $settled = (strtolower($ext['profit_settle'] ?? '') === 'settled');
                $row['settle_status'] = $settled ? 'settled' : ($verified ? 'verified' : 'pending');
                // 透出简化订单核销信息
                $row['verify_code'] = $order['verify_code'] ?? '';
                $row['order_status'] = $order['status'] ?? 'pending';
                // 注入用户组分润明细
                $row['group_profit'] = $ext['profit_groups'] ?? [];

                if ($settle_status === '' || $row['settle_status'] === $settle_status) {
                    $filtered[] = $row;
                }
            }
            $list['data']['list'] = $filtered;
            $list['data']['count'] = count($filtered);

            return $list;
        } else {
            // 下拉数据源：设备、价档（移除不存在的 device_name 字段）
            $devices = model('device_info')->getList([ ['site_id', '=', $this->site_id] ], 'device_id,device_sn');
            $tiers   = model('lottery_price_tier')->getList([], 'tier_id,title');
            $this->assign('devices', $devices['data'] ?? []);
            $this->assign('tiers', $tiers['data'] ?? []);
            // 用户组集合用于动态列选择
            $groupModel = new GroupModel();
            $groupList = $groupModel->getGroupList([], 'group_id,group_name', 'group_id asc');
            $this->assign('groups', $groupList['data'] ?? []);
            return $this->fetch('profit/lists');
        }
    }

    /**
     * 标记分润账单为已结算（写入 lottery_record.ext.profit_settle=settled）
     */
    public function settle()
    {
        $id = intval(input('id', 0));
        if (!$id) return $this->error('', '缺少参数');
        $info = model('lottery_profit')->getInfo([ ['id', '=', $id], ['site_id', '=', $this->site_id] ], '*');
        if (empty($info)) return $this->error('', '账单不存在');
        $record = model('lottery_record')->getInfo([ ['record_id', '=', $info['record_id']] ], '*');
        if (empty($record)) return $this->error('', '关联抽奖记录不存在');
        $ext = json_decode($record['ext'] ?? '{}', true);
        $ext['profit_settle'] = 'settled';
        model('lottery_record')->update([ 'ext' => json_encode($ext, JSON_UNESCAPED_UNICODE) ], [ ['record_id', '=', $record['record_id']] ]);
        return $this->success([ 'id' => $id ]);
    }

    /**
     * 导出当前筛选页的CSV（前端触发下载）
     */
    public function export()
    {
        // 复用 lists 的筛选
        $condition = [ ['site_id', '=', $this->site_id] ];
        $device_id = input('device_id', 0);
        $tier_id   = input('tier_id', 0);
        $start_time = input('start_time', '');
        $end_time   = input('end_time', '');
        $settle_status = input('settle_status', '');
        if ($device_id) $condition[] = ['device_id', '=', $device_id];
        if ($tier_id)   $condition[] = ['tier_id', '=', $tier_id];
        if ($start_time && $end_time) {
            $condition[] = ['create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
        } elseif (!$start_time && $end_time) {
            $condition[] = ['create_time', '<=', date_to_time($end_time) ];
        } elseif ($start_time && !$end_time) {
            $condition[] = ['create_time', '>=', date_to_time($start_time) ];
        }
        $page = input('page', 1);
        $page_size = input('page_size', PAGE_LIST_ROWS);
        $list = model('lottery_profit')->pageList($condition, 'id desc', '*', $page, $page_size);
        $rows = $list['data']['list'] ?? [];

        // 加载用户组名称映射
        $groupNames = [];
        $groupModel = new GroupModel();
        $gl = $groupModel->getGroupList([], 'group_id,group_name', 'group_id asc');
        if (!empty($gl['data'])) {
            foreach ($gl['data'] as $g) {
                $groupNames[$g['group_id']] = $g['group_name'];
            }
        }

        // 简单CSV（UTF-8），首行表头：改为用户组分润明细
        $csv = "ID,记录ID,设备ID,价档ID,订单金额,用户组分润,创建时间\n";
        foreach ($rows as $r) {
            // 读取关联抽奖记录 ext 中的分组分润
            $record = model('lottery_record')->getInfo([ ['record_id', '=', $r['record_id']] ], 'ext');
            $ext = [];
            if (!empty($record)) {
                $ext = json_decode($record['ext'] ?? '{}', true);
            }
            $groups = $ext['profit_groups'] ?? [];
            // 支持两种键：纯数字group_id或以 group_ 前缀的键
            $parts = [];
            foreach ($groups as $k => $amt) {
                $gid = 0;
                if (is_numeric($k)) { $gid = intval($k); }
                elseif (strpos($k, 'group_') === 0) { $gid = intval(substr($k, 6)); }
                $name = $groupNames[$gid] ?? ('组'.$gid);
                $parts[] = $name . ':' . $amt;
            }
            $groupStr = implode(';', $parts);

            $csv .= implode(',', [
                $r['id'], $r['record_id'], $r['device_id'], $r['tier_id'],
                $r['amount_total'], $groupStr,
                date('Y-m-d H:i:s', $r['create_time'] ?? time())
            ]) . "\n";
        }
        // 输出文本响应，由前端触发下载
        return $this->success([ 'filename' => 'profit_page_' . $page . '.csv', 'content' => $csv ]);
    }
}