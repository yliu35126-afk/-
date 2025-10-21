<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */


namespace addon\platformcoupon\event;

use app\model\system\Cron;

/**
 * 应用安装
 */
class Install
{
	/**
	 * 执行安装
	 */
	public function handle()
	{
	    try{
	        execute_sql('addon/platformcoupon/data/install.sql');

            $cron = new Cron();
            $cron->deleteCron([ ['event', '=', 'CronPlatformcouponEnd'] ]);
            $cron->addCron(2, 1, '平台优惠券过期自动关闭', 'CronPlatformcouponEnd', time(), 0);

	        return success();
	    }catch (\Exception $e)
	    {
	        return error('', $e->getMessage());
	    }
	}
}