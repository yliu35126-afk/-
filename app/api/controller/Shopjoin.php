<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\api\controller;

use app\model\shop\Config as ConfigModel;

/**
 * 入驻指南
 * Class Shopjoin
 * @package app\api\controller
 */
class Shopjoin extends BaseApi
{
	
	/**
	 * 基础信息
	 */
	public function info()
	{
		//指南索引 1 2 3 4
		$guide_index = isset($this->params['guide_index']) ? $this->params['guide_index'] : 1;
		$config_model = new ConfigModel();
		$info = $config_model->getShopJoinGuideDocument($guide_index);
		return $this->response($info);
	}
	
	/**
	 * 列表信息
	 */
	public function lists()
	{
		$config_model = new ConfigModel();
		$list = $config_model->getShopJoinGuide();
		return $this->response($list);
	}
	
}