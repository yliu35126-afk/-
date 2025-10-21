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

namespace app\shopapi\controller;

use app\model\shop\ShopAccount;
use app\model\shop\Shop as ShopModel;

class Shopwithdraw extends BaseApi
{


    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) {
            echo $this->response($token);
            exit;
        }
    }

    /**
     * 账户信息
     * @return false|string
     */
    public function info()
    {
        $shop_model = new ShopModel();
        //获取店铺的账户信息
        $condition = array (
            [ "site_id", "=", $this->site_id ]
        );

        $info = $shop_model->getShopInfo($condition, 'account, account_withdraw,account_withdraw_apply,shop_open_fee,shop_baozhrmb');
        return $this->response($info);
    }

    /**
     * 申请提现
     * */
    public function apply()
    {
        $money = isset($this->params[ 'apply_money' ]) ? $this->params[ 'apply_money' ] : '';
        $shop_account_model = new ShopAccount();
        $result = $shop_account_model->applyWithdraw($this->site_id, $money);

        return $this->response($result);
    }

    /**
     * 获取提现记录
     */
    public function lists()
    {
        $account_model = new ShopAccount();

        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        $status = isset($this->params[ 'status' ]) ? $this->params[ 'status' ] : '';

        $condition[] = [ 'site_id', '=', $this->site_id ];
        if (!empty($status)) {
            if ($status == 3) {//待审核
                $condition[] = [ 'status', '=', 0 ];
            } else {
                $condition[] = [ 'status', '=', $status ];
            }
        }

        $start_time = isset($this->params[ 'start_time' ]) ? $this->params[ 'start_time' ] : '';
        $end_time = isset($this->params[ 'end_time' ]) ? $this->params[ 'end_time' ] : '';
        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ 'apply_time', '>=', $start_time ];
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'apply_time', '<=', $end_time ];
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'apply_time', 'between', [ $start_time, $end_time ] ];
        }

        $order = "id desc";

        $list = $account_model->getShopWithdrawPageList($condition, $page, $page_size, $order);

        return $this->response($list);
    }

    /**
     * 提现信息
     */
    public function detail()
    {
        $id = isset($this->params[ 'id' ]) ? $this->params[ 'id' ] : '';

        $account_model = new ShopAccount();
        $info = $account_model->getShopWithdrawInfo([ [ 'site_id', '=', $this->site_id ], [ 'id', '=', $id ] ]);
        return $this->response($info);
    }

}