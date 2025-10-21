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

use addon\memberrecharge\model\Memberrecharge as MemberRechargeModel;
use app\api\controller\BaseApi;

/**
 * 充值
 */
class Memberrecharge extends BaseApi
{
	
	/**
	 * 基础信息
	 */
	public function info()
	{
		$recharge_id = isset($this->params['recharge_id']) ? $this->params['recharge_id'] : 0;
		if (empty($recharge_id)) {
			return $this->response($this->error('', 'REQUEST_RECHARGE_ID'));
		}
		$field = 'recharge_id,recharge_name,cover_img,face_value,buy_price,point,growth,coupon_id,sale_num,status';
		$member_recharge_model = new MemberrechargeModel();
		$info = $member_recharge_model->getMemberRechargeInfo([ [ 'recharge_id', '=', $recharge_id ] ], $field);
		return $this->response($info);
		
	}
	
	/**
	 * 会员充值配置
	 */
	public function config()
	{
		$member_recharge_model = new MemberrechargeModel();
		$res = $member_recharge_model->getConfig();
		return $this->response($res);
		
	}
	
	/**
	 * 计算信息
	 */
	public function page()
	{
		$page = isset($this->params['page']) ? $this->params['page'] : 1;
		$page_size = isset($this->params['page_size']) ? $this->params['page_size'] : PAGE_LIST_ROWS;
		$field = 'recharge_id,recharge_name,cover_img,face_value,buy_price,point,growth,coupon_id,sale_num';
		$member_recharge_model = new MemberrechargeModel();
		$list = $member_recharge_model->getMemberRechargePageList([ [ 'status', '=', 1 ] ], $page, $page_size, 'create_time desc', $field);
		return $this->response($list);
	}
}