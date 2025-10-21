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

namespace addon\memberrecharge\model;

use addon\coupon\model\CouponType;
use app\model\BaseModel;
use think\facade\Cache;
use app\model\system\Config as ConfigModel;

/**
 * 会员充值
 */
class Memberrecharge extends BaseModel
{
	
	/**
	 * 添加套餐
	 * @param $data
	 * @return array
	 */
	public function addMemberRecharge($data)
	{
		$data['create_time'] = time();
		$data['status'] = 1;
		
		$res = model('member_recharge')->add($data);
		Cache::tag("member_recharge")->clear();
		return $this->success($res);
	}
	
	/**
	 * 编辑套餐
	 * @param array $condition
	 * @param $data
	 * @return array
	 */
	public function editMemberRecharge($condition = [], $data)
	{
		$data['update_time'] = time();
		
		$res = model('member_recharge')->update($data, $condition);
		Cache::tag("member_recharge")->clear();
		return $this->success($res);
	}
	
	
	/**
	 * 删除套餐详情
	 * @param array $condition
	 * @return mixed
	 */
	public function deleteMemberRecharge($condition = [])
	{
		$res = model('member_recharge')->delete($condition);
		Cache::tag("member_recharge")->clear();
		return $this->success($res);
	}
	
	
	/**
	 * 套餐详情
	 * @param array $condition
	 * @param string $field
	 * @return array
	 */
	public function getMemberRechargeInfo($condition = [], $field = '*')
	{
		$recharge = model('member_recharge')->getInfo($condition, $field);
		if ($recharge) {
			//获取优惠券信息
			if ($recharge['coupon_id']) {
				//优惠券字段
				$coupon_field = 'coupon_type_id,coupon_name,money,count,lead_count,max_fetch,at_least,end_time,image,validity_type,fixed_term';
				
				$model = new CouponType();
				$coupon = $model->getCouponTypeList([ [ 'coupon_type_id', 'in', $recharge['coupon_id'] ] ], $coupon_field);
				$recharge['coupon_list'] = $coupon;
			}
		}
		Cache::tag("member_recharge")->clear();
		return $this->success($recharge);
	}
	
	/**
	 * 套餐列表
	 * @param array $condition
	 * @param int $page
	 * @param int $page_size
	 * @param string $order
	 * @param string $field
	 * @return array
	 */
	public function getMemberRechargePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
	{
		$list = model('member_recharge')->pageList($condition, $field, $order, $page, $page_size);
		
		Cache::tag("member_recharge")->clear();
		return $this->success($list);
	}
	
	/**
	 * 设置会员充值配置
	 * @param $data
	 * @param $is_use
	 * @return array
	 */
	public function setConfig($data,$is_use)
	{
		$config = new ConfigModel();
		$res = $config->setConfig($data, '会员充值配置', $is_use, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'MEMBER_RECHARGE_CONFIG' ] ]);
		return $res;
	}
	
	/**
	 * 获取会员充值配置
	 */
	public function getConfig()
	{
		$config = new ConfigModel();
		$res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'MEMBER_RECHARGE_CONFIG' ] ]);
		return $res;
	}
	
}