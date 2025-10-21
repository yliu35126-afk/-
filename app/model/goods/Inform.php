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

namespace app\model\goods;

use think\facade\Cache;
use app\model\BaseModel;

/**
 * 举报
 */
class Inform extends BaseModel
{
    /**
     * 添加举报
     * @param array $data
     */
    public function addInform($data)
    {
        $inform_id = model('inform')->add($data);
        Cache::tag("inform")->clear();
        return $this->success($inform_id);
    }

    /**
     * 修改举报
     * @param array $data
     * @return multitype:string
     */
    public function editInform($data)
    {
        $res = model('inform')->update($data, [ [ 'inform_id', '=', $data['inform_id'] ] ]);
        Cache::tag("inform")->clear();
        return $this->success($res);
    }

    /**
     * 删除举报
     * @param array $condition
     */
    public function deleteInform($condition)
    {
        $res = model('inform')->delete($condition);
        Cache::tag("inform")->clear();
        return $this->success($res);
    }

    /**
     * 获取举报信息
     * @param array $condition
     * @param string $field
     */
    public function getInformInfo($condition, $field = '*')
    {
        $data = json_encode([ $condition, $field ]);
        $cache = Cache::get("inform_getInformInfo_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $res = model('inform')->getInfo($condition, $field);
        Cache::tag("inform")->set("inform_getInformInfo_" . $data, $res);
        return $this->success($res);
    }

    /**
     * 获取举报列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getInformList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $data = json_encode([ $condition, $field, $order, $limit ]);
        $cache = Cache::get("inform_getInformList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('inform')->getList($condition, $field, $order, '', '', '', $limit);
        Cache::tag("inform")->set("inform_getInformList_" . $data, $list);

        return $this->success($list);
    }

    /**
     * 获取举报分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getInformPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('inform')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 统计举报数量
     * @param array $condition
     * @return array
     */
    public function getInformCount($condition = [])
    {
        $count = model('inform')->getCount($condition);
        return $this->success($count);
    }
}