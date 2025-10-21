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

use app\model\shop\Config as ConfigModel;

/**
 * 商家入驻
 */
class Shopjoin extends BaseAdmin
{
	/**
	 * 入驻广告
	 */
	public function adv()
	{
		$config_model = new ConfigModel();
		if (request()->isAjax()) {
			$config_json = input('config_json', '');
			$adv_config = $config_json ? json_decode($config_json, true) : [];
			return $config_model->setShopJoinAdvConfig($adv_config);
		} else {
			$this->forthMenu();
			//广告详情 广告最多三张
			$adv_config = $config_model->getShopJoinAdvConfig();
			$this->assign('adv_config', $adv_config['data']['value']);
			return $this->fetch('shopjoin/adv');
		}
	}
	
	/**
	 * 入驻指南
	 */
	public function guide()
	{
		if (request()->isAjax()) {
			$config_model = new ConfigModel();
			return $config_model->getShopJoinGuide();
		} else {
			$this->forthMenu();
			return $this->fetch('shopjoin/guide');
		}
	}
	
	/**
	 * 修改指南
	 */
	public function editGuide()
	{
		$config_model = new ConfigModel();
		if (request()->isAjax()) {
			$title = input('title', '');
			$content = input('content', '');
			$guide_index = input('guide_index', 1);
			
			return $config_model->setShopJoinGuideDocument($title, $content, $guide_index);
		} else {
			//指南索引 1 2 3 4
			$guide_index = input('guide_index', 1);
			$this->assign('guide_index', $guide_index);
			
			//指南详情
			$guide_info = $config_model->getShopJoinGuideDocument($guide_index);
			$this->assign('guide_info', $guide_info['data']);
			return $this->fetch('shopjoin/edit_guide');
		}
	}
	
	
	/**
	 * 入驻协议
	 */
	public function shopAgreement()
	{
		$config_model = new ConfigModel();
		if (request()->isAjax()) {
			$title = input('title', '');//标题
			$content = input('content', '');//内容
			
			return $config_model->setShopApplyAgreement($title, $content);
			
		}
		
		$shop_apply_agreement = $config_model->getShopApplyAgreement();
		$this->assign('shop_apply_agreement', $shop_apply_agreement);
		
		return $this->fetch('shopjoin/shop_agreement');
	}
	
	
}