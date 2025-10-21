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

namespace addon\fenxiao\api\controller;

use addon\fenxiao\model\FenxiaoWithdraw;
use app\api\controller\BaseApi;


/**
 * 分销提现
 */
class Withdraw extends BaseApi
{

    /**
     * 申请提现
     */
    public function apply()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $member_id = $this->member_id;
        $money = isset($this->params[ 'money' ]) ? $this->params[ 'money' ] : '';

        if (empty($money)) {
            return $this->response($this->error('', 'REQUEST_MONEY'));
        }

        $data = [
            'member_id' => $member_id,
            'money' => $money
        ];

        $withdraw_model = new FenxiaoWithdraw();
        $res = $withdraw_model->addFenxiaoWithdraw($data);

        return $this->response($res);
    }

    /**
     * 提现记录分页
     * @return false|string
     */
    public function page()
    {

        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $status = isset($this->params[ 'status' ]) ? $this->params[ 'status' ] : 0;// 当前状态 1待审核 2待转账 3已转账 -1 已拒绝

        $condition = [
            [ 'member_id', '=', $this->member_id ]
        ];
        if (!empty($status)) {
            $condition[] = [ 'status', '=', $status ];
        }

        $order = 'id desc';
        $withdraw_model = new FenxiaoWithdraw();
        $list = $withdraw_model->getFenxiaoWithdrawPageList($condition, $page, $page_size, $order);
        return $this->response($list);
    }

}