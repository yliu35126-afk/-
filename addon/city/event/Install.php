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


namespace addon\city\event;

use app\model\system\Cron;
use app\model\system\Menu;
use Carbon\Carbon;

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
	    
        $menu = new Menu();
        $menu->refreshMenu("city", "city");
        //创建自动执行事件
        $cron = new Cron();
        $execute_time = Carbon::now()->addMonth()->firstOfMonth()->timestamp + 30*60;
        $cron->deleteCron([ [ 'event', '=', 'WebsiteSettlement' ] ]);
        $cron->addCron('2','1','分站周期结算','WebsiteSettlement',$execute_time,'0',3);
        return success();
	}
}