<?php
namespace addon\device_turntable\admin\controller;

use app\admin\controller\BaseAdmin;
use think\facade\Db;

class Record extends BaseAdmin
{
    /**
     * 抽奖记录列表（设备版）
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [];

            // 中奖状态：1=命中(hit)，0=未中(miss/lose)
            $status = input('status', '');
            if ($status !== '') {
                if ($status == 1) {
                    $condition[] = ['result', '=', 'hit'];
                } elseif ($status === '0' || $status == 0) {
                    $condition[] = ['result', 'in', ['miss', 'lose']];
                }
            }

            // 会员昵称模糊查询 → 转为 member_id 列表筛选
            $member_nick_name = input('member_nick_name', '');
            if ($member_nick_name !== '') {
                $ids = Db::name('member')
                    ->whereLike('nickname|member_name', '%' . $member_nick_name . '%')
                    ->limit(200)
                    ->column('member_id');
                if (!empty($ids)) {
                    $condition[] = ['member_id', 'in', $ids];
                } else {
                    // 无匹配会员，强制为空结果
                    $condition[] = ['member_id', '=', -1];
                }
            }

            // 时间范围
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && $end_time) {
                $condition[] = ['create_time', 'between', [date_to_time($start_time), date_to_time($end_time)]];
            } elseif (!$start_time && $end_time) {
                $condition[] = ['create_time', '<=', date_to_time($end_time)];
            } elseif ($start_time && !$end_time) {
                $condition[] = ['create_time', '>=', date_to_time($start_time)];
            }

            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);

            $list = model('lottery_record')->pageList($condition, 'record_id desc', '*', $page, $page_size);

            // 视图期望的字段补充：award_name、member_nick_name
            if (!empty($list['data']['list'])) {
                foreach ($list['data']['list'] as &$row) {
                    $ext = json_decode($row['ext'] ?: '{}', true);
                    $order = $ext['order'] ?? [];
                    // 奖项名称优先取 ext.award_name
                    $row['award_name'] = $ext['award_name'] ?? ($row['prize_type'] ?? '');
                    // 昵称兜底查询 member.nickname
                    if (empty($row['member_nick_name'])) {
                        $nick = Db::name('member')->where('member_id', intval($row['member_id']))->value('nickname');
                        $row['member_nick_name'] = $nick ?: '';
                    }
                    // 核销状态（供前端可能使用）
                    $row['verify_status'] = (($order['status'] ?? '') === 'verified') ? 'verified' : 'pending';
                }
            }

            return $list;
        } else {
            return $this->fetch('record/lists');
        }
    }
}