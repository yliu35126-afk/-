<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace app\event;

use app\model\order\Order as OrderModel;
use app\model\shop\ShopMember as ShopMemberModel;

/**
 * 创建订单后添加店铺关注
 */
class OrderCreateShopMember
{
	/**
	 * 传入订单信息
	 * @param unknown $data
	 */
	public function handle($data)
	{
		$order_model = new OrderModel();
		$order_info = $order_model->getOrderInfo([ [ 'order_id', '=', $data['order_id'] ] ], 'site_id,member_id');
		$order_info = $order_info['data'];
		if (!empty($order_info)) {
			//添加店铺关注记录
			$shop_member_model = new ShopMemberModel();
			$res = $shop_member_model->addShopMember($order_info['site_id'], $order_info['member_id'], 0);
			return $res;
		}
	}
	
}