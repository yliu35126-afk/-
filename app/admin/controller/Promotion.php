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

namespace app\admin\controller;


use app\model\system\Promotion as PromotionModel;

/**
 * 营销管理 控制器
 */
class Promotion extends BaseAdmin
{

	/**
	 * 营销中心
	 */
	public function config()
	{
		$promotion_model = new PromotionModel();
		$promotions = $promotion_model->getPromotions();
		$this->assign("promotion", $promotions['admin']);
		return $this->fetch('promotion/config');
	}
	
	/**
	 * 店铺营销
	 */
	public function shop()
	{
		$promotion_model = new PromotionModel();
		$promotions = $promotion_model->getPromotions();
		$this->assign("promotion", $promotions['admin']);
		return $this->fetch('promotion/shop');
	}
	
	/**
	 * 会员营销
	 */
	public function member()
	{
		$promotion_model = new PromotionModel();
		$promotions = $promotion_model->getPromotions();
		$this->assign("promotion", $promotions['admin']);
		return $this->fetch('promotion/member');
	}
	
	/**
	 * 平台营销
	 */
	public function platform()
	{
		$promotion_model = new PromotionModel();
		$promotions = $promotion_model->getPromotions();
		$this->assign("promotion", $promotions['admin']);
		return $this->fetch('promotion/platform');
	}
	
	/**
	 * 应用工具
	 */
	public function tool()
	{
		$promotion_model = new PromotionModel();
		$promotions = $promotion_model->getPromotions();
		$this->assign("promotion", $promotions['admin']);
		return $this->fetch('promotion/tool');
	}
	
}