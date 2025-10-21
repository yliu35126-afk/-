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

namespace addon\supply\model;

use app\model\BaseModel;

/**
 * 供应商入驻费用
 */
class OpenAccount extends BaseModel
{
    /**
     * 获取入驻费用列表
     * @param array $where
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getOpenAccountList($where = [], $field = '*', $order = '', $limit = null)
    {

        $list = model('supply_open_account')->getList($where, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取入驻费用分页列表
     * @param array $where
     * @param int $page
     * @param int $size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getOpenAccountPageList($where = [], $page = 1, $size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {

        $list = model('supply_open_account')->pageList($where, $field, $order, $page, $size);
        return $this->success($list);
    }
}
