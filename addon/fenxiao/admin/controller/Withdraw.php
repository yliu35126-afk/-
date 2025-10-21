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

namespace addon\fenxiao\admin\controller;

use addon\fenxiao\model\FenxiaoLevel;
use addon\fenxiao\model\FenxiaoWithdraw as FenxiaoWithdrawModel;
use app\admin\controller\BaseAdmin;

/**
 *  分销等级管理
 */
class Withdraw extends BaseAdmin
{

    /**
     * 分销等级列表
     */
    public function lists()
    {
        $model = new FenxiaoWithdrawModel();
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition = [];

            $withdraw_no = input('withdraw_no');//提现流水
            if (!empty($withdraw_no)) {
                $condition[] = [ 'withdraw_no', 'like', '%' . $withdraw_no . '%' ];
            }
            $fenxiao_name = input('fenxiao_name', '');//分销商店铺名
            if (!empty($fenxiao_name)) {
                $condition[] = [ 'fenxiao_name', 'like', '%' . $fenxiao_name . '%' ];
            }
            $level_id = input('level_id');//分销商等级id
            if (!empty($level_id)) {
                $condition[] = [ 'level_id', '=', $level_id ];
            }
            $withdraw_type = input('withdraw_type');//提现类型
            if (!empty($withdraw_type)) {
                $condition[] = [ 'withdraw_type', '=', $withdraw_type ];
            }
            $status = input('status');//提现类型
            if (!empty($status)) {
                $condition[] = [ 'status', '=', $status ];
            }

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && $end_time) {
                $condition[] = [ 'create_time', 'between', [ strtotime($start_time), strtotime($end_time) ] ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'create_time', '<=', strtotime($end_time) ];
            } elseif ($start_time && !$end_time) {
                $condition[] = [ 'create_time', '>=', strtotime($start_time) ];
            }

            $order = 'id desc';
            $list = $model->getFenxiaoWithdrawPageList($condition, $page, $page_size, $order);
            return $list;

        } else {

            //分销商等级
            $level_model = new FenxiaoLevel();
            $level = $level_model->getLevelList([ [ 'status', '=', 1 ] ], 'level_id,level_name');
            $this->assign('level', $level[ 'data' ]);

            return $this->fetch('withdraw/lists');
        }

    }

    /**
     * 审核通过
     */
    public function withdrawPass()
    {
        $ids = input('id');

        $model = new FenxiaoWithdrawModel();

        return $model->withdrawPass($ids);
    }

    /**
     * 审核拒绝
     */
    public function withdrawRefuse()
    {
        $id = input('id');

        $model = new FenxiaoWithdrawModel();

        return $model->withdrawRefuse($id);
    }

}