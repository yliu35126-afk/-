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

namespace addon\city\city\controller;

use addon\city\model\CityWithdraw;

class Withdraw extends BaseCity
{

    /**
     * 获取提现记录
     */
    public function lists()
    {

        if (request()->isAjax()) {

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition[] = [ 'website_id', '=', $this->site_id ];

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ 'apply_time', '>=', $start_time ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'apply_time', '<=', $end_time ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'apply_time', 'between', [ $start_time, $end_time ] ];
            }

            $order = "id desc";

            $model = new CityWithdraw();
            $list = $model->getCityWithdrawPageList($condition, $page, $page_size, $order);

            return $list;
        }

        return $this->fetch('withdraw/lists', [], $this->replace);
    }

}