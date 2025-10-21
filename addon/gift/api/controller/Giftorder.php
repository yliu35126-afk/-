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

namespace addon\gift\api\controller;

use app\api\controller\BaseApi;
use addon\gift\model\GiftOrder as GiftOrderModel;

/**
 * 礼品订单
 */
class Giftorder extends BaseApi
{
	
	/**
	 * 基础信息
	 */
	public function info()
	{
		$token = $this->checkToken();
		if ($token['code'] < 0) return $this->response($token);
		$order_id = isset($this->params['order_id']) ? $this->params['order_id'] : 0;
		if (empty($order_id)) {
			return $this->response($this->error('', 'REQUEST_ORDER_ID'));
		}
		$condition = [
			[ 'order_id', '=', $order_id ],
			[ 'member_id', '=', $this->member_id ]
		];
		$field = 'order_id,order_no,gift_id,gift_name,gift_image,num,remark,create_time,express_status,express_no,express_company_id,express_company_name,express_time,member_id,member_name,mobile,full_address';
		$gift_order_model = new GiftOrderModel();
		$list = $gift_order_model->getOrderInfo($condition, $field);
		return $this->response($list);
	}
	
	public function page()
	{
		$token = $this->checkToken();
		if ($token['code'] < 0) return $this->response($token);
		
		$page = isset($this->params['page']) ? $this->params['page'] : 1;
		$page_size = isset($this->params['page_size']) ? $this->params['page_size'] : PAGE_LIST_ROWS;
		$condition = [
			[ 'member_id', '=', $this->member_id ]
		];
		$order = 'create_time desc';
		$field = 'order_id,order_no,gift_id,gift_name,gift_image,num,remark,create_time,express_status,express_no,express_company_id,express_company_name,express_time,member_name,mobile,full_address';
		
		$gift_order_model = new GiftOrderModel();
		$list = $gift_order_model->getOrderPageList($condition, $page, $page_size, $order, $field);
		return $this->response($list);
	}
	
}