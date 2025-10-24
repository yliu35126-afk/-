<?php
/**
 * 奖励单管理（基于 lottery_record 轻量奖励单）
 */
namespace addon\turntable\admin\controller;

use app\admin\controller\BaseAdmin;

class Reward extends BaseAdmin
{
    /**
     * 奖励单列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [
                ['prize_type', '=', 'goods'],
                ['order_id', '>', 0]
            ];

            $member_id = input('member_id', '');
            if ($member_id !== '') {
                $condition[] = ['member_id', '=', intval($member_id)];
            }
            $goods_id = input('goods_id', '');
            if ($goods_id !== '') {
                $condition[] = ['goods_id', '=', intval($goods_id)];
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $list = model('lottery_record')->pageList($condition, 'record_id desc', '*', $page, $page_size);
            // 提取奖励单状态与核销码
            if (!empty($list['data']['list'])) {
                foreach ($list['data']['list'] as &$row) {
                    $ext = json_decode($row['ext'] ?: '{}', true);
                    $order = $ext['order'] ?? [];
                    $row['order_status'] = $order['status'] ?? '';
                    $row['verify_code'] = $order['verify_code'] ?? '';
                }
            }
            return $list;
        } else {
            return $this->fetch('reward/lists');
        }
    }
}