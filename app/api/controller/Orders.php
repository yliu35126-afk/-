<?php
namespace app\api\controller;

use app\api\controller\BaseApi;
use think\facade\Db;

class Orders extends BaseApi
{
    /**
     * 查询订单的抽奖记录
     * 请求：order_id
     * 返回：order_id、prize_id、lottery_status
     */
    public function getLotteryRecords()
    {
        $order_id = intval($this->params['order_id'] ?? 0);
        if ($order_id <= 0) return $this->response($this->error('', '缺少或非法的 order_id'));

        $rows = Db::name('lottery_record')->where('order_id', $order_id)->order('record_id desc')->select()->toArray();
        $list = [];
        foreach ($rows as $r) {
            $prize_id = intval($r['goods_id'] ?? 0);
            if (!$prize_id) $prize_id = intval($r['slot_id'] ?? 0);
            $list[] = [
                'order_id' => $order_id,
                'prize_id' => $prize_id,
                'lottery_status' => ($r['result'] ?? 'miss'),
            ];
        }
        return $this->response($this->success(['list' => $list]));
    }
}