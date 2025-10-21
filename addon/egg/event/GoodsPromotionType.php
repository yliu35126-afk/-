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


namespace addon\egg\event;

/**
 * 活动类型
 */
class GoodsPromotionType
{
    /**
     * 活动类型
     * @return array
     */
	public function handle()
	{
		return ["name" => "砸金蛋", "short" => "", "type" => "egg", "color" => "#4CB130", 'shop_url' => 'egg://shop/egg/lists', 'admin_url' => 'egg://admin/egg/lists'];
	}
}