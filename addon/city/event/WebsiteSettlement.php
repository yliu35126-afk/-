<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\city\event;

use addon\city\model\CitySettlement;
use Carbon\Carbon;

/**
 * 分站结算
 */
class WebsiteSettlement
{
	// 行为扩展的执行入口必须是run
	public function handle($data)
	{
        $model = new CitySettlement();
        $time = Carbon::today()->timestamp+60*30;
        $res = $model->citySettlement($time);

        return $res;
	}
	
}