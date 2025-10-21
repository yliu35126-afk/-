<?php

namespace app\component\controller;

use app\model\shop\ShopService as ShopServiceModel;

/**
 * 店铺内容·组件
 */
class ShopContent extends BaseDiyView
{
	/**
	 * 后台编辑界面
	 */
	public function design()
	{
		return $this->fetch("shop_content/design.html");
	}
}