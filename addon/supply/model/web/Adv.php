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

namespace addon\supply\model\web;


use think\facade\Cache;
use app\model\BaseModel;

/**
 * 广告管理
 * @author Administrator
 *
 */
class Adv extends BaseModel
{
	/**
	 * 添加广告
	 * @param array $data
	 */
	public function addAdv($data)
	{
		$ap_id = model('supply_adv')->add($data);
		Cache::tag("supply_adv")->clear();
		return $this->success($ap_id);
	}
	
	/**
	 * 修改广告
	 * @param array $data
	 */
	public function editAdv($data, $condition)
	{
		$res = model('supply_adv')->update($data, $condition);
		Cache::tag("supply_adv")->clear();
		return $this->success($res);
	}
	
	/**
	 * 删除广告
	 * @param array $condition
	 */
	public function deleteAdv($condition)
	{
		$res = model('supply_adv')->delete($condition);
		Cache::tag("supply_adv")->clear();
		return $this->success($res);
	}
	
	/**
	 * 获取广告基础信息
	 * @param int $ap_id
	 * @return multitype:string mixed
	 */
	public function getAdvInfo($ap_id)
	{
		$cache = Cache::get("supply_adv_getAdvInfo_" . $ap_id);
		if (!empty($cache)) {
			return $this->success($cache);
		}
		$res = model('supply_adv')->getInfo([ [ 'adv_id', '=', $ap_id ] ], 'adv_id, adv_title, ap_id, adv_url, adv_image, slide_sort, background');
		if(!empty($res)){
            $res['adv_url_data'] = json_decode($res['adv_url'], true);
        }
		Cache::tag("supply_adv")->set("supply_adv_getAdvInfo_" . $ap_id, $res);
		return $this->success($res);
	}
	
	/**
	 * 获取广告列表
	 * @param array $condition
	 * @param string $field
	 * @param string $order
	 * @param string $limit
	 */
	public function getAdvList($condition = [], $field = 'adv_id, adv_title, ap_id, adv_url, adv_image, slide_sort, background', $order = 'slide_sort desc,adv_id desc', $limit = null)
	{
		$data = json_encode([ $condition, $field, $order, $limit ]);
		$cache = Cache::get("supply_adv_getAdvList_" . $data);
		if (!empty($cache)) {
			return $this->success($cache);
		}
		$list = model('supply_adv')->getList($condition, $field, $order, '', '', '', $limit);
		Cache::tag("supply_adv")->set("supply_adv_getAdvList_" . $data, $list);
		
		return $this->success($list);
	}
	
	/**
	 * 获取广告分页列表
	 * @param array $condition
	 * @param number $page
	 * @param string $page_size
	 * @param string $order
	 * @param string $field
	 */
	public function getAdvPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'a.adv_id desc', $field = 'a.adv_id, a.ap_id, a.adv_title, a.adv_url, a.adv_image, a.slide_sort, a.background, ap.ap_name')
	{
		$data = json_encode([ $condition, $field, $order, $page, $page_size ]);
		$cache = Cache::get("supply_adv_getAdvPageList_" . $data);
		if (!empty($cache)) {
			return $this->success($cache);
		}
		$join = [
			[
				'supply_adv_position ap',
				'a.ap_id = ap.ap_id',
				'left'
			]
		];
		
		$list = model('supply_adv')->pageList($condition, $field, $order, $page, $page_size, 'a', $join);
		Cache::tag("supply_adv")->set("supply_adv_getAdvPageList_" . $data, $list);
		return $this->success($list);
	}
	
}