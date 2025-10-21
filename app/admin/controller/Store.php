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

use app\model\store\Store as StoreModel;

/**
 * 门店管理 控制器
 */
class Store extends BaseAdmin
{
	/******************************* 门店列表及相关操作 ***************************/
	
	/**
	 * 门店列表
	 */
	public function lists()
	{
		if (request()->isAjax()) {
			$page = input('page', 1);
			$page_size = input('page_size', PAGE_LIST_ROWS);
			$search_text = input('search_text', '');
			$status = input('status', '');
			$site_id = input('site_id', '');
			$condition = [];
			$condition[] = [ 'store_name', 'like', '%' . $search_text . '%' ];
			
			//门店状态
			if ($status != '') {
				$condition[] = [ 'status', '=', $status ];
			}
			if ($site_id != '') {
				$condition[] = [ 'site_id', '=', $site_id ];
			}
			$order = 'store_id desc';
			$field = '*';
			
			$store_model = new StoreModel();
			
			return $store_model->getStorePageList($condition, $page, $page_size, $order, $field);
			
		} else {
			return $this->fetch('store/lists');
		}
	}
}