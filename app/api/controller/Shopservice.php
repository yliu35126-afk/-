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

use app\model\shop\ShopService as ShopServiceModel;

/**
 * 店铺服务
 * Class Shopcategory
 * @package app\api\controller
 */
class Shopservice extends BaseApi
{

	public function lists()
	{
		$shop_service_model = new ShopServiceModel();
		$list = $shop_service_model->getServiceNameList();
		foreach ($list as $k => $v) {
			unset($list[ $k ][ 'icon' ]);
		}
		return $this->response($this->success($list));
	}

}