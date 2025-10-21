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
 * 店铺等级（暂不考虑）
 */
class ShopLevel extends BaseModel
{
    /**
     * 添加店铺等级
     * @param array $data
     */
    public function addLevel($data)
    {
        $res = model('shop_level')->add($data);
        Cache::tag("shop_level")->clear();
        return $this->success($res);
    }

    /**
     * 修改店铺等级
     * @param array $data
     */
    public function editLevel($data)
    {
        $res = model('shop_level')->update($data, [ 'level_id', '=', $data[ 'level_id' ] ]);
        //修改对应店铺
        model('shop')->update([ 'level_name' => $data[ 'level_name' ] ], [ 'level_id', '=', $data[ 'level_id' ] ]);
        Cache::tag("shop_level")->clear();
        return $this->success($res);
    }

    /**
     * 删除店铺等级
     * @param unknown $condition
     */
    public function deleteLevel($condition)
    {
        $res = model('shop_level')->delete($condition);
        Cache::tag("shop_level")->clear();
        return $this->success($res);
    }

    /**
     * 获取店铺等级信息
     * @param unknown $condition
     * @param string $field
     */
    public function getLevelInfo($condition, $field = '*')
    {
        $data = json_encode([ $condition, $field ]);
        $cache = Cache::get("shop_level_getLevelInfo_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $res = model('shop_level')->getInfo($condition, $field);
        Cache::tag("shop_level")->set("shop_level_getLevelInfo_" . $data, $res);
        return $this->success($res);
    }

    /**
     * 获取店铺等级列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getLevelList($condition = [], $field = '*', $order = '', $limit = null)
    {

        $data = json_encode([ $condition, $field, $order, $limit ]);
        $cache = Cache::get("shop_level_getLevelList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('shop_level')->getList($condition, $field, $order, '', '', '', $limit);
        Cache::tag("shop_level")->set("shop_level_getLevelList_" . $data, $list);

        return $this->success($list);
    }

    /**
     * 获取店铺等级分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getLevelPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $data = json_encode([ $condition, $field, $order, $page, $page_size ]);
        $cache = Cache::get("shop_level_getLevelPageList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('shop_level')->pageList($condition, $field, $order, $page, $page_size);
        Cache::tag("shop_level")->set("shop_level_getLevelPageList_" . $data, $list);
        return $this->success($list);
    }
}