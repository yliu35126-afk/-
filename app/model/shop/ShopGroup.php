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

use app\model\BaseModel;
use app\model\system\Menu as MenuModel;
use app\model\system\Promotion as PromotionModel;
use think\facade\Cache;

/**
 * 开店套餐
 */
class ShopGroup extends BaseModel
{
    /**
     * 添加开店套餐
     * @param array $data
     */
    public function addGroup($data)
    {
        $res = model('shop_group')->add($data);
        Cache::tag("shop_group")->clear();
        return $this->success($res);
    }

    /**
     * 修改开店套餐
     * @param array $data
     */
    public function editGroup($data)
    {
        $res = model('shop_group')->update($data, [ [ 'group_id', '=', $data[ 'group_id' ] ] ]);
        //修改对应店铺
        model('shop')->update([ 'group_name' => $data[ 'group_name' ] ], [ [ 'group_id', '=', $data[ 'group_id' ] ] ]);
        model('shop_apply')->update([ 'group_name' => $data[ 'group_name' ] ], [ [ 'group_id', '=', $data[ 'group_id' ] ] ]);
        Cache::tag("shop_group")->clear();
        return $this->success($res);
    }

    /**
     * 删除开店套餐
     * @param unknown $condition
     */
    public function deleteGroup($condition)
    {
        $check_condition = array_column($condition, 2, 0);
        $group_id = $check_condition[ 'group_id' ];
        $count = model('shop')->getCount([ [ 'group_id', 'in', $group_id ] ]);

        if ($count == 0) {
            $res = model('shop_group')->delete($condition);
            Cache::tag("shop_group")->clear();
            return $this->success($res);
        } else {
            return $this->error('', '该开店套餐存在其他店铺，无法删除');
        }
    }

    /**
     * 获取开店套餐信息
     * @param $condition
     * @param string $field
     */
    public function getGroupInfo($condition, $field = '*')
    {
        $data = json_encode([ $condition, $field ]);
        $cache = Cache::get("shop_group_getGroupInfo_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $res = model('shop_group')->getInfo($condition, $field);
        Cache::tag("shop_group")->set("shop_group_getGroupInfo_" . $data, $res);
        return $this->success($res);
    }

    /**
     * 获取开店套餐列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getGroupList($condition = [], $field = '*', $order = '', $limit = null)
    {

        $data = json_encode([ $condition, $field, $order, $limit ]);
        $cache = Cache::get("shop_group_getGroupList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('shop_group')->getList($condition, $field, $order, '', '', '', $limit);
        Cache::tag("shop_group")->set("shop_group_getGroupList_" . $data, $list);

        return $this->success($list);
    }

    /**
     * 获取开店套餐分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getGroupPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $data = json_encode([ $condition, $field, $order, $page, $page_size ]);
        $cache = Cache::get("shop_group_getGroupPageList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('shop_group')->pageList($condition, $field, $order, $page, $page_size);
        Cache::tag("shop_group")->set("shop_group_getGroupPageList_" . $data, $list);
        return $this->success($list);
    }

    /**
     * 刷新开店套餐权限
     */
    public function refreshGroup()
    {
        $shop_group_list = $this->getGroupList();
        if ($shop_group_list[ 'data' ]) {
            $promotion_model = new PromotionModel();
            $promotions = $promotion_model->getPromotions();
            $all_promotion_name = array_column($promotions[ 'shop' ], 'name');
            $menu_model = new MenuModel();
            $menu_list = $menu_model->getMenuList([ [ 'app_module', "=", 'shop' ], [ 'addon', 'NOT IN', $all_promotion_name ] ], "name");
            foreach ($shop_group_list[ 'data' ] as $shop_group) {
                $data = [ 'group_id' => $shop_group[ 'group_id' ], 'group_name' => $shop_group[ 'group_name' ] ];
                $data[ 'menu_array' ] = join(',', array_column($menu_list[ 'data' ], 'name'));
                if (!empty($shop_group[ 'addon_array' ])) {
                    $addon_name = explode(',', $shop_group[ 'addon_array' ]);
                    $addon_menu = $menu_model->getMenuList([ [ 'app_module', "=", 'shop' ], [ 'addon', 'in', $addon_name ] ], 'name');
                    $addon_name = array_column($addon_menu[ 'data' ], 'name');
                    $data[ 'menu_array' ] .= ',' . join(',', $addon_name);
                }
                $this->editGroup($data);
            }
        }
        $this->success();
    }
}