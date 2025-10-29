<?php
namespace addon\device_turntable\shop\controller;

use app\shop\controller\BaseShop;

class Bind extends BaseShop
{
    /**
     * 商家端设备-价档绑定列表（仅显示当前店铺的设备）
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [ ['status','=', 1] ];

            // 仅筛选当前店铺拥有的设备
            try {
                $device_ids = \think\facade\Db::name('device_info')
                    ->where([ ['site_id', '=', $this->site_id] ])
                    ->column('device_id');
            } catch (\Throwable $e) {
                $device_ids = [];
            }

            if (!empty($device_ids)) {
                $condition[] = ['device_id', 'in', $device_ids];
            } else {
                // 无设备时返回空结果
                return success(0, '', [ 'count' => 0, 'list' => [] ]);
            }

            $device_id = (int)input('device_id', 0);
            if ($device_id > 0) $condition[] = ['device_id', '=', $device_id];
            $tier_id = (int)input('tier_id', 0);
            if ($tier_id > 0) $condition[] = ['tier_id', '=', $tier_id];

            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);
            $result = model('device_price_bind')->pageList($condition, '*', 'id desc', $page, $page_size);

            // 价档信息补充（标题与供货范围）
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
                                $row['tier_min_price'] = $map[$tid]['min_price'];
                                $row['tier_max_price'] = $map[$tid]['max_price'];
                            }
                        }
                    } catch (\Throwable $e) { /* ignore */ }
                }
            }

            return success(0, '', $result);
        } else {
            $device_id = (int)input('device_id', 0);
            $this->assign('device_id', $device_id);
            return $this->fetch('bind/lists');
        }
    }
}