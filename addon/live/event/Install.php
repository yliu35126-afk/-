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


namespace addon\live\event;

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
            $cron_model = new Cron();
            $execute_time = strtotime(date("Y-m-d 00:00:00", strtotime('+1 day')));
            $item_result = $cron_model->addCron(2, 1, '轮询更新同步直播间', 'LiveRoomStatus', $execute_time, 0, 1);
            if($item_result['code'] < 0)
                return $item_result;

            $item_result = $cron_model->addCron(2, 1, '轮询更新同步直播商品', 'LiveGoodsStatus', $execute_time, 0, 1);
            if($item_result['code'] < 0)
                return $item_result;

	        return success();
	    }catch (\Exception $e)
	    {
	        return error('', $e->getMessage());
	    }
	}
}