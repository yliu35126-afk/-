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

namespace app\shop\controller;

use app\model\shop\Config as ConfigModel;

/**
 * 商家入驻
 */
class Shopjoin extends BaseShop
{

	/**
	 * 入驻指南
	 */
	public function guide()
	{
		if (request()->isAjax()) {
			$config_model = new ConfigModel();
			return $config_model->getShopJoinGuide();
		} else {
            $this->assign('menu_info', ['title' => '入驻指南']);
			return $this->fetch('shopjoin/guide');
		}
	}
	
	/**
	 * 入驻指南详情
	 */
	public function guideDetail()
	{
        //指南详情
        $guide_index = input('guide_index', 1);
        $config_model = new ConfigModel();
        $guide_info = $config_model->getShopJoinGuideDocument($guide_index);

        $this->assign("guide_info", $guide_info['data']);
        $this->assign('menu_info', ['title' => $guide_info['data']['title']]);
        return $this->fetch("shopjoin/guide_detail");
	}

}