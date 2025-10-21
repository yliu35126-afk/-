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

namespace app\admin\controller;

use app\model\member\MemberAccount as MemberAccountModel;

/**
 * 会员账户管理 控制器
 */
class Memberaccount extends BaseAdmin
{
	/**
	 * 会员积分
	 * @return mixed
	 */
	public function point()
	{
		$member_account_model = new MemberAccountModel();
		if (request()->isAjax()) {
			$page = input('page', 1);
			$page_size = input('page_size', PAGE_LIST_ROWS);
			$search_text = input('search_text', '');
			$search_text_type = input('search_text_type', 'username|mobile|email');//可以传username mobile email
			$start_date = input('start_date', '');
			$end_date = input('end_date', '');
			$from_type = input('from_type', '');
			
			$condition = [];
			//下拉选择
			$condition[] = [ $search_text_type, 'like', "%" . $search_text . "%" ];
			if ($from_type) {
				$condition[] = [ 'from_type', '=', $from_type ];
			}
			//发生时间
			if ($start_date != '' && $end_date != '') {
				$condition[] = [ 'create_time', 'between', [ strtotime($start_date), strtotime($end_date) ] ];
			} else if ($start_date != '' && $end_date == '') {
				$condition[] = [ 'create_time', '>=', strtotime($start_date) ];
			} else if ($start_date == '' && $end_date != '') {
				$condition[] = [ 'create_time', '<=', strtotime($end_date) ];
			}
			$condition[] = [ 'account_type', '=', 'point' ];
			$order = 'create_time desc';
			$field = 'member_id, account_type, account_data, from_type, type_name, type_tag, remark, create_time, username, mobile, email';
			
			$list = $member_account_model->getMemberAccountPageList($condition, $page, $page_size, $order, $field);
			return $list;
		} else {
			
			//来源类型
			$from_type_arr = $member_account_model->getFromType();
			$this->assign('from_type_arr', $from_type_arr['point']);
			
			return $this->fetch("member_account/point");
		}
	}
	
	/**
	 * 会员余额
	 * @return mixed
	 */
	public function balance()
	{
		$member_account_model = new MemberAccountModel();
		if (request()->isAjax()) {
			$page = input('page', 1);
			$page_size = input('page_size', PAGE_LIST_ROWS);
			$search_text = input('search_text', '');
			$search_text_type = input('search_text_type', 'username|mobile|email');//可以传username mobile email
			$start_date = input('start_date', '');
			$end_date = input('end_date', '');
			$from_type = input('from_type', '');
			$balance_type = input('balance_type', 'balance');
			
			$condition = [];
			//下拉选择
			$condition[] = [ $search_text_type, 'like', "%" . $search_text . "%" ];
			if ($from_type) {
				$condition[] = [ 'from_type', '=', $from_type ];
			}
			//发生时间
			if ($start_date != '' && $end_date != '') {
				$condition[] = [ 'create_time', 'between', [ strtotime($start_date), strtotime($end_date) ] ];
			} else if ($start_date != '' && $end_date == '') {
				$condition[] = [ 'create_time', '>=', strtotime($start_date) ];
			} else if ($start_date == '' && $end_date != '') {
				$condition[] = [ 'create_time', '<=', strtotime($end_date) ];
			}
			$condition[] = [ 'account_type', '=', $balance_type ];
			$order = 'create_time desc';
			$field = 'member_id, account_type, account_data, from_type, type_name, type_tag, remark, create_time, username, mobile, email';
			
			$list = $member_account_model->getMemberAccountPageList($condition, $page, $page_size, $order, $field);
			return $list;
		} else {
			//来源类型
			$from_type_arr = $member_account_model->getFromType();
			$this->assign('from_type_arr', $from_type_arr['balance']);
			return $this->fetch("member_account/balance");
		}
		
	}
	
	/**
	 * 成长值
	 * @return mixed
	 */
	public function growth()
	{
		$member_account_model = new MemberAccountModel();
		if (request()->isAjax()) {
			$page = input('page', 1);
			$page_size = input('page_size', PAGE_LIST_ROWS);
			$search_text = input('search_text', '');
			$search_text_type = input('search_text_type', 'username|mobile|email');//可以传username mobile email
			$start_date = input('start_date', '');
			$end_date = input('end_date', '');
			$from_type = input('from_type', '');
			
			$condition = [];
			//下拉选择
			$condition[] = [ $search_text_type, 'like', "%" . $search_text . "%" ];
			if ($from_type) {
				$condition[] = [ 'from_type', '=', $from_type ];
			}
			//发生时间
			if ($start_date != '' && $end_date != '') {
				$condition[] = [ 'create_time', 'between', [ strtotime($start_date), strtotime($end_date) ] ];
			} else if ($start_date != '' && $end_date == '') {
				$condition[] = [ 'create_time', '>=', strtotime($start_date) ];
			} else if ($start_date == '' && $end_date != '') {
				$condition[] = [ 'create_time', '<=', strtotime($end_date) ];
			}
			$condition[] = [ 'account_type', '=', 'growth' ];
			$order = 'create_time desc';
			$field = 'member_id, account_type, account_data, from_type, type_name, type_tag, remark, create_time, username, mobile, email';
			
			$list = $member_account_model->getMemberAccountPageList($condition, $page, $page_size, $order, $field);
			return $list;
		} else {
			//来源类型
			$from_type_arr = $member_account_model->getFromType();
			$this->assign('from_type_arr', $from_type_arr['growth']);
			return $this->fetch("member_account/growth");
		}
		
	}
}