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

namespace addon\memberrecharge\api\controller;

use addon\memberrecharge\model\MemberrechargeOrder as MemberRechargeOrderModel;
use app\api\controller\BaseApi;

/**
 * 充值订单
 */
class Order extends BaseApi
{
	
	/**
	 * 计算信息
	 */
	public function page()
	{
		$token = $this->checkToken();
		if ($token['code'] < 0) return $this->response($token);
		$page = isset($this->params['page']) ? $this->params['page'] : 1;
		$page_size = isset($this->params['page_size']) ? $this->params['page_size'] : PAGE_LIST_ROWS;
		$field = 'order_id,recharge_id,recharge_name,order_no,cover_img,buy_price,create_time,out_trade_no,face_value,point,growth,coupon_id';
		$member_recharge_order_model = new MemberrechargeOrderModel();
		$list = $member_recharge_order_model->getMemberRechargeOrderPageList([ [ 'status', '=', 2 ], [ 'member_id', '=', $this->member_id ] ], $page, $page_size, 'create_time desc', $field);
		return $this->response($list);
	}
}