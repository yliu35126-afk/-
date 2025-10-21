<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\supply\event;

use addon\supply\model\Supplier;
use think\facade\Db;

/**
 * 供应商自动关闭(每日执行一次)
 */
class CronSupplyClose
{
    // 行为扩展的执行入口必须是run
    public function handle($param = [])
    {

        $supply_model = new Supplier();
        $now_time = time();
        $condition = array(
            ["status", "=", 1],
            ["expire_time", "exp", Db::raw('<= ' . $now_time . ' and expire_time != 0')]
        );
        $supplyData = $supply_model->getSupplyList($condition, "supplier_site_id");
        if (!empty($supplyData["data"])) {
            $supply_list = $supplyData["data"];
            foreach ($supply_list as $k => $v) {
                $result = $supply_model->supplyClose($v["supplier_site_id"]);
                if($result['code'] < 0){
                    return $result;
                }
            }
        }
    }

}