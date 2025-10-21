<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace app\event;

use app\model\shop\ShopSettlement;
use Carbon\Carbon;
/**
 * 店铺账期转账
 */
class ShopWithdrawPeriodCalc
{
	// 行为扩展的执行入口必须是run
	public function handle($data)
	{
        $model = new ShopSettlement();
        $time = Carbon::today()->timestamp;

        $res = $model->shopSettlement($time);

        return $res;
	}
	
}