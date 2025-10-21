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

namespace app\model\shop;


use think\facade\Cache;
use app\model\BaseModel;

/**
 * 店铺主营行业类型
 */
 
class ShopCategory extends BaseModel
{
    
    /**
     * 添加店铺主营行业
     * @param array $data
     */
    public function addCategory($data)
    {
        $res = model('shop_category')->add($data);
        Cache::tag("shop_category")->clear();
        return $this->success($res);
    }
    
    /**
     * 修改店铺主营行业
     * @param array $data
     */
    public function editCategory($data)
    {
        $res = model('shop_category')->update($data, [['category_id', '=', $data['category_id'] ]]);
        //修改对应店铺
        model('shop')->update(['category_name' => $data['category_name']], [['category_id', '=', $data['category_id']]]);
        model('shop_apply')->update(['category_name' => $data['category_name']], [[ 'category_id', '=', $data['category_id'] ]]);
        Cache::tag("shop_category")->clear();
        return $this->success($res);
    }
    
    /**
     * 删除店铺主营行业
     * @param unknown $condition
     */
    public function deleteCategory($condition)
    {
	    $check_condition = array_column($condition, 2, 0);
	    $category_id = $check_condition['category_id'];
	    $count = model('shop')->getCount([['category_id','in',$category_id]]);
	    if($count==0) {
		    $res = model('shop_category')->delete($condition);
		    Cache::tag("shop_category")->clear();
		    return $this->success($res);
	    }else{
		    return $this->error('','该主营行业存在其他店铺，无法删除');
	    }
    }
    
    /**
     * 获取店铺主营行业信息
     * @param unknown $condition
     * @param string $field
     */
    public function getCategoryInfo($condition, $field = '*')
    {
        $data = json_encode([ $condition, $field]);
        $cache = Cache::get("shop_category_getCategoryInfo_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $res = model('shop_category')->getInfo( $condition, $field);
        Cache::tag("shop_category")->set("shop_category_getCategoryInfo_" . $data, $res);
        return $this->success($res);
    }
    /**
     * 获取店铺主营行业列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getCategoryList($condition = [], $field = '*', $order = '', $limit = null)
    {
    
        $data = json_encode([ $condition, $field, $order, $limit ]);
        $cache = Cache::get("shop_category_getCategoryList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('shop_category')->getList($condition, $field, $order, '', '', '', $limit);
        Cache::tag("shop_category")->set("shop_category_getCategoryList_" . $data, $list);
    
        return $this->success($list);
    }
    
    /**
     * 获取店铺主营行业分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getCategoryPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $data = json_encode([ $condition, $field, $order, $page, $page_size ]);
        $cache = Cache::get("shop_category_getCategoryPageList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('shop_category')->pageList($condition, $field, $order, $page, $page_size);
        Cache::tag("shop_category")->set("shop_category_getCategoryPageList_" . $data, $list);
        return $this->success($list);
    }
}