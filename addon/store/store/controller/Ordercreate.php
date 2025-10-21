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

namespace addon\store\store\controller;

use addon\store\model\StoreOrderCreate as OrderCreateModel;

/**
 * 订单
 * Class Order
 * @package app\shop\controller
 */
class Ordercreate extends Cash
{
	
	public function __construct()
	{
		//执行父类构造函数
		parent::__construct();
		
	}
	
	/**
	 * 创建订单
	 */
	public function create()
	{
		//通过传递的sku_id数据,模拟操作购物车
		$order_create = new OrderCreateModel();
		$cart = input("cart", "");
		$coupon_id = input("coupon_id", 0);
		$cart_data_result = $this->tranSkuData($cart);
		if ($cart_data_result["code"] < 0) {
			return $cart_data_result;
		}
		$cart_data = $cart_data_result["data"];
		$sku_ids = $cart_data["sku_ids"];
		$nums = $cart_data["nums"];
		$buyer_member_id = $this->buyer();
		if ($buyer_member_id <= 0)
			return $order_create->error([], "当前没有登录会员");
		
		$data = [
			'sku_ids' => $sku_ids,
			'nums' => $nums,
            'site_id' => $this->site_id,
			'store_id' => $this->store_id,
			'member_id' => $buyer_member_id,
			'is_balance' => input('is_balance', 0),//是否使用余额
			'order_from' => "pc",
			'order_from_name' => "PC",
			'coupon' => [ $this->site_id => ["coupon_id" => $coupon_id ]],
			'member_address' => [],
			"buyer_message" => [ $this->site_id => '' ],
            'invoice' => []
		];
		$res = $order_create->create($data);
		
		return $res;
	}
	
	/**
	 * 计算信息
	 */
	public function calculate()
	{
		//通过传递的sku_id数据,模拟操作购物车
		$order_create = new OrderCreateModel();
		$cart = input("cart", "");
		$coupon_id = input("coupon_id", 0);
		$cart_data_result = $this->tranSkuData($cart);
		if ($cart_data_result["code"] < 0) {
			return $cart_data_result;
		}
		$cart_data = $cart_data_result["data"];
		$sku_ids = $cart_data["sku_ids"];
		$nums = $cart_data["nums"];
		$buyer_member_id = $this->buyer();
		if ($buyer_member_id <= 0)
			return $order_create->error([], "当前没有登录会员");
		
		$data = [
			'sku_ids' => $sku_ids,
			'nums' => $nums,
			'store_id' => $this->store_id,
			'member_id' => $buyer_member_id,
			'is_balance' => input('is_balance', 0),//是否使用余额
			'order_from' => "pc",
			'order_from_name' => "PC",
			'coupon' => [ $this->site_id => ["coupon_id" => $coupon_id ]],
            'invoice' => []
		];
		$res = $order_create->calculate($data);
		return $order_create->success($res);
		
	}
	
	/**
	 * 待支付订单 数据初始化
	 * @return string
	 */
	public function payment()
	{
		if (request()->isAjax()) {
			//通过传递的sku_id数据,模拟操作购物车
			$order_create = new OrderCreateModel();
			$cart = input("cart", "");
			$cart_data_result = $this->tranSkuData($cart);
			if ($cart_data_result["code"] < 0) {
				return $cart_data_result;
			}
			
			$cart_data = $cart_data_result["data"];
			$sku_ids = $cart_data["sku_ids"];
			$nums = $cart_data["nums"];
			
			//查看是否登陆了会员
			$buyer_member_id = $this->buyer();
			if ($buyer_member_id <= 0)
				return $order_create->error([], "当前没有登录会员");
			
			$data = [
				'sku_ids' => $sku_ids,
				'nums' => $nums,
				'store_id' => $this->store_id,
				'member_id' => $buyer_member_id,
				'is_balance' => input('is_balance', 0),//是否使用余额
				'order_from' => "pc",
				'order_from_name' => "PC"
			];
			
			$res = $order_create->orderPayment($data);
			return $res;
		}
	}
	
	/**
	 * "格式化"  购物车数据
	 * @param $cart
	 * @return array
	 */
	public function tranSkuData($cart)
	{
		$order_create = new OrderCreateModel();
		if (empty($cart)) {
			return $order_create->error([], "'购物车中还没有商品!");
		}
		$cart = json_decode($cart, true);
		$sku_ids = [];
		$nums = [];
		foreach ($cart as $k => $v) {
			$sku_ids[] = $v["sku_id"];
			$nums[ $v["sku_id"] ] = $v["num"];
		}
		return $order_create->success([ "sku_ids" => $sku_ids, "nums" => $nums ]);
	}
	
	
	public function test()
	{

//        $this->loginBuyer();
		$order_create = new OrderCreateModel();
		$temp_array = array(
			[
				"sku_id" => 586,
				"num" => 1,
			
			]
		);
		$cart = input("cart", json_encode($temp_array));
		$cart_data_result = $this->tranSkuData($cart);
		if ($cart_data_result["code"] < 0) {
			return $cart_data_result;
		}
		
		$cart_data = $cart_data_result["data"];
		$sku_ids = $cart_data["sku_ids"];
		$nums = $cart_data["nums"];
		
		//查看是否登陆了会员
		$buyer_member_id = $this->buyer();
		$data = [
			'sku_ids' => $sku_ids,
			'nums' => $nums,
			'store_id' => $this->store_id,
			'member_id' => $buyer_member_id,
			'is_balance' => input('is_balance', 0),//是否使用余额
			'order_from' => "pc",
			'order_from_name' => "PC",
			'coupon' => [ "coupon_id" => 0 ],
			"buyer_message" => ''
//            'member_address' => []
		];
//        $res = $order_create->calculate($data);
		$res = $order_create->create($data);

//        $res = $order_create->orderPayment($data);
		var_dump($res);
//        return $res;
	}
	
	
}