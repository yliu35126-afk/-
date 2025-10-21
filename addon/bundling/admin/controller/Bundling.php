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

namespace addon\bundling\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\bundling\model\Bundling as BundlingModel;

/**
 * 优惠套餐
 */
class Bundling extends BaseAdmin
{
	/**
	 * 活动列表
	 */
	public function lists()
	{
		if (request()->isAjax()) {
			$page = input('page', 1);
			$page_size = input('page_size', PAGE_LIST_ROWS);
			$bl_name = input('bl_name', '');
			$search_text = input('search_text', '');
			$status = input('status', '');
			$site_id = input("site_id", "");
			$condition = [];
			$condition[] = [ 'bl_name', 'like', '%' . $bl_name . '%' ];
			$condition[] = [ 'site_name', 'like', '%' . $search_text . '%' ];
			if ($site_id != "") {
				$condition[] = [ 'site_id', '=', $site_id ];
			}
			if ($status != '') {
				$condition[] = [ 'status', '=', $status ];
			}
			$order = 'update_time desc';
			$field = '*';
			
			$bundling_model = new BundlingModel();
			$res = $bundling_model->getBundlingPageList($condition, $page, $page_size, $order, $field);
			return $res;
		} else {
			return $this->fetch("bundling/lists");
		}
	}
	
	/**
	 * 活动详情
	 */
	public function detail()
	{
		$bl_id = input("bl_id", 0);
		$site_id = input("site_id", 0);
		$bundling_model = new BundlingModel();
		$condition = [ [ 'bl_id', '=', $bl_id ], [ 'site_id', '=', $site_id ] ];
		$info_result = $bundling_model->getBundlingDetail($condition);
		$this->assign("info", $info_result["data"]);
		return $this->fetch("bundling/detail");
	}
	
	/**
	 * 删除优惠套餐
	 */
	public function delete()
	{
		if (request()->isAjax()) {
			$bl_id = input('bl_id', 0);
			$site_id = input("site_id", 0);
			$this->addLog("删除优惠套餐id:" . $bl_id);
			$bundling_model = new BundlingModel();
			$res = $bundling_model->deleteBundling($bl_id, $site_id);
			return $res;
		}
	}
	
}