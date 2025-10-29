<?php
namespace addon\agent\admin\controller;

use app\admin\controller\BaseAdmin;
use think\facade\Db;

class Ledger extends BaseAdmin
{
    /**
     * 流水列表（视图/接口），支持筛选与汇总
     * 过滤：agent_id、role、record_id、时间区间（start_time/end_time）
     * summary：none|balance|monthly
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);
            $agent_id = (int)input('agent_id', 0);
            $role = trim((string)input('role', ''));
            $record_id = (int)input('record_id', 0);
            $start_time = trim((string)input('start_time', ''));
            $end_time = trim((string)input('end_time', ''));
            $summary = trim((string)input('summary', 'none')) ?: 'none';

            $condition = [];
            if ($agent_id > 0) $condition[] = ['agent_id', '=', $agent_id];
            if ($role !== '') $condition[] = ['role', '=', $role];
            if ($record_id > 0) $condition[] = ['record_id', '=', $record_id];
            if ($start_time !== '' && $end_time !== '') {
                $st = ns_date_to_time($start_time); $et = ns_date_to_time($end_time);
                if ($st && $et) $condition[] = ['create_time', 'between', [$st, $et]];
            }

            try {
                if ($summary === 'balance') {
                    // 余额统计：按代理+角色聚合金额
                    $rows = Db::name('ns_agent_ledger')
                        ->field('agent_id, role, SUM(amount) AS amount, COUNT(1) AS cnt')
                        ->where($condition)->group('agent_id, role')->order('agent_id desc')
                        ->page($page, $page_size)->select()->toArray();
                    $count = Db::name('ns_agent_ledger')->where($condition)->count();
                    return success(0, 'ok', [
                        'page' => $page, 'page_size' => $page_size,
                        'list' => $rows, 'count' => $count,
                        'summary' => 'balance',
                        'total_amount' => floatval(Db::name('ns_agent_ledger')->where($condition)->sum('amount')),
                    ]);
                } elseif ($summary === 'monthly') {
                    // 月度汇总：按月份聚合金额
                    $query = Db::name('ns_agent_ledger')
                        ->field("DATE_FORMAT(FROM_UNIXTIME(create_time),'%Y-%m') AS ym, SUM(amount) AS amount, COUNT(1) AS cnt")
                        ->where($condition)->group('ym')->order('ym desc');
                    // 手动分页：MySQL 不支持对格式化列直接二次分页统计，先取全集后切片
                    $all = $query->select()->toArray();
                    $count = count($all);
                    $offset = max(0, ($page - 1) * $page_size);
                    $rows = array_slice($all, $offset, $page_size);
                    return success(0, 'ok', [
                        'page' => $page, 'page_size' => $page_size,
                        'list' => $rows, 'count' => $count,
                        'summary' => 'monthly',
                        'total_amount' => floatval(Db::name('ns_agent_ledger')->where($condition)->sum('amount')),
                    ]);
                } else {
                    // 明细列表
                    $query = Db::name('ns_agent_ledger');
                    $count = $query->where($condition)->count();
                    $list = $query->where($condition)->order('id desc')->page($page, $page_size)->select()->toArray();
                    return success(0, 'ok', [
                        'page' => $page, 'page_size' => $page_size,
                        'list' => $list, 'count' => $count,
                        'summary' => 'none',
                        'total_amount' => floatval(Db::name('ns_agent_ledger')->where($condition)->sum('amount')),
                    ]);
                }
            } catch (\Throwable $e) {
                return error(-1, $e->getMessage());
            }
        } else {
            return $this->fetch('ledger/lists');
        }
    }

    /**
     * 导出 CSV（明细/余额/月度均支持，以 summary 参数决定格式）
     */
    public function export()
    {
        $agent_id = (int)input('agent_id', 0);
        $role = trim((string)input('role', ''));
        $record_id = (int)input('record_id', 0);
        $start_time = trim((string)input('start_time', ''));
        $end_time = trim((string)input('end_time', ''));
        $summary = trim((string)input('summary', 'none')) ?: 'none';

        $condition = [];
        if ($agent_id > 0) $condition[] = ['agent_id', '=', $agent_id];
        if ($role !== '') $condition[] = ['role', '=', $role];
        if ($record_id > 0) $condition[] = ['record_id', '=', $record_id];
        if ($start_time !== '' && $end_time !== '') {
            $st = ns_date_to_time($start_time); $et = ns_date_to_time($end_time);
            if ($st && $et) $condition[] = ['create_time', 'between', [$st, $et]];
        }

        try {
            $filename = 'agent_ledger_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            echo "\xEF\xBB\xBF"; // BOM for Excel

            if ($summary === 'balance') {
                $rows = Db::name('ns_agent_ledger')->field('agent_id, role, SUM(amount) AS amount, COUNT(1) AS cnt')->where($condition)->group('agent_id, role')->order('agent_id desc')->select()->toArray();
                echo "agent_id,role,amount,count\n";
                foreach ($rows as $r) {
                    echo sprintf("%d,%s,%.2f,%d\n", intval($r['agent_id']), $r['role'], floatval($r['amount']), intval($r['cnt']));
                }
            } elseif ($summary === 'monthly') {
                $rows = Db::name('ns_agent_ledger')->field("DATE_FORMAT(FROM_UNIXTIME(create_time),'%Y-%m') AS ym, SUM(amount) AS amount, COUNT(1) AS cnt")->where($condition)->group('ym')->order('ym desc')->select()->toArray();
                echo "month,amount,count\n";
                foreach ($rows as $r) {
                    echo sprintf("%s,%.2f,%d\n", $r['ym'], floatval($r['amount']), intval($r['cnt']));
                }
            } else {
                $rows = Db::name('ns_agent_ledger')->where($condition)->order('id desc')->limit(5000)->select()->toArray();
                echo "id,agent_id,role,record_id,amount,site_id,device_id,type,create_time\n";
                foreach ($rows as $r) {
                    echo sprintf("%d,%d,%s,%d,%.2f,%d,%d,%s,%s\n",
                        intval($r['id']), intval($r['agent_id']), $r['role'], intval($r['record_id']), floatval($r['amount']), intval($r['site_id']), intval($r['device_id']), $r['type'], date('Y-m-d H:i:s', intval($r['create_time']))
                    );
                }
            }
            exit;
        } catch (\Throwable $e) {
            return error(-1, $e->getMessage());
        }
    }
}

// 安全的日期字符串转时间戳（避免未加载 ns.date_to_time 导致空值）
if (!function_exists('ns_date_to_time')) {
    function ns_date_to_time($string) {
        $string = trim((string)$string);
        if ($string === '') return 0;
        $ts = strtotime($string);
        return $ts ?: 0;
    }
}