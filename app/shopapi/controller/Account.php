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

use app\model\order\OrderCommon as OrderCommonModel;
use app\model\shop\Shop as ShopModel;
use app\model\shop\ShopAccount;
use app\model\shop\ShopOpenAccount;
use app\model\shop\ShopReopen as ShopReopenModel;
use app\model\shop\ShopSettlement;

class Account extends BaseApi
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
     * 资产概况
     */
    public function index()
    {
        $shop_model = new ShopModel();
        $shop_account_model = new ShopAccount();

        $data = [];
        //获取商家转账设置
        $shop_withdraw_config = $shop_account_model->getShopWithdrawConfig();
        $data[ 'shop_withdraw_config' ] = $shop_withdraw_config[ 'data' ][ 'value' ];//商家转账设置

        //获取店铺的账户信息
        $condition = array (
            [ "site_id", "=", $this->site_id ]
        );
        $shop_info = $shop_model->getShopInfo($condition, 'site_name,logo,account, account_withdraw,account_withdraw_apply,shop_open_fee,shop_baozhrmb');
        $shop_info = $shop_info[ 'data' ];
        $data[ 'shop_info' ] = $shop_info;

        //余额
        $account = $shop_info[ 'account' ] - $shop_info[ 'account_withdraw_apply' ];
        $data[ 'account' ] = number_format($account, 2, '.', '');

        //累计收入
        $total = $shop_info[ 'account' ] + $shop_info[ 'account_withdraw' ];
        $data[ 'total' ] = number_format($total, 2, '.', '');

        //已提现
        $data[ 'account_withdraw' ] = number_format($shop_info[ 'account_withdraw' ], 2, '.', '');

        //提现中
        $data[ 'account_withdraw_apply' ] = number_format($shop_info[ 'account_withdraw_apply' ], 2, '.', '');

        //获取店家结算账户信息
        $shop_cert_result = $shop_model->getShopCert($condition, 'bank_type, settlement_bank_account_name, settlement_bank_account_number, settlement_bank_name, settlement_bank_address');
        $data[ 'shop_cert_info' ] = $shop_cert_result[ 'data' ];//店家结算账户信息

        //店铺的待结算金额
        $settlement_model = new ShopSettlement();
        $settlement_info = $settlement_model->getWaitSettlementInfo($this->site_id);
        $order_apply = $settlement_info[ 'shop_money' ] - $settlement_info[ 'refund_shop_money' ] - $settlement_info[ 'commission' ] + $settlement_info[ 'platform_coupon_money' ] - $settlement_info[ 'refund_platform_coupon_money' ];
        $data[ 'order_apply' ] = number_format($order_apply, 2, '.', '');

        return $this->response($this->success($data));
    }

    /**
     * 店铺账户面板
     */
    public function dashboard()
    {
        $shop_account_model = new ShopAccount();

        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        $condition[] = [ 'site_id', '=', $this->site_id ];
        $type = isset($this->params[ 'type' ]) ? $this->params[ 'type' ] : '';//收支类型（1收入  2支出）
        if (!empty($type)) {
            switch ( $type ) {
                case 1:
                    $condition[] = [ 'account_data', '>', 0 ];
                    break;
                case 2:
                    $condition[] = [ 'account_data', '<', 0 ];
                    break;
            }
        }
        $start_time = isset($this->params[ 'start_time' ]) ? $this->params[ 'start_time' ] : '';
        $end_time = isset($this->params[ 'end_time' ]) ? $this->params[ 'end_time' ] : '';
        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ 'create_time', '>=', $start_time ];
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'create_time', '<=', $end_time ];
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'create_time', 'between', [ $start_time, $end_time ] ];
        }

        $field = 'account_no,site_id,account_type,account_data,from_type,type_name,relate_tag,create_time,remark';
        $list = $shop_account_model->getAccountPageList($condition, $page, $page_size, 'id desc', $field);

        return $this->response($list);
    }

    /**
     * 账户交易记录
     */
    public function orderList()
    {
        $order_model = new OrderCommonModel();
        $condition[] = [ 'site_id', '=', $this->site_id ];

        //下单时间
        $start_time = isset($this->params[ 'start_time' ]) ? $this->params[ 'start_time' ] : '';
        $end_time = isset($this->params[ 'end_time' ]) ? $this->params[ 'end_time' ] : '';

        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ "finish_time", ">=", $start_time ];
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ "finish_time", "<=", $end_time ];
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'finish_time', 'between', [ $start_time, $end_time ] ];
        }

        //订单状态
        $order_status = isset($this->params[ 'order_status' ]) ? $this->params[ 'order_status' ] : '';
        if ($order_status != "") {
            switch ( $order_status ) {
                case 1://进行中

                    $condition[] = [ "order_status", "not in", [ 0, -1, 10 ] ];
                    $order = 'pay_time desc';
                    break;
                case 2://待结算

                    $condition[] = [ "order_status", "=", 10 ];
                    $condition[] = [ "is_settlement", "=", 0 ];
                    $order = 'finish_time desc';
                    break;
                case 3://已结算

                    $condition[] = [ "order_status", "=", 10 ];
                    $condition[] = [ "settlement_id", ">", 0 ];
                    $order = 'finish_time desc';
                    break;
                case 4://全部
                    $condition[] = [ "order_status", "not in", [ 0, -1 ] ];
                    $order = 'pay_time desc';
                    break;
            }
        } else {
            $condition[] = [ "order_status", "=", 10 ];
            $condition[] = [ "settlement_id", "=", 0 ];
            $order = 'finish_time desc';
        }
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        $field = 'order_id,order_no,order_money,order_status_name,shop_money,platform_money,refund_money,refund_shop_money,refund_platform_money,commission,finish_time,settlement_id';
        $list = $order_model->getOrderPageList($condition, $page, $page_size, $order, $field);

        return $this->response($list);
    }

    /**
     * 订单统计
     * @return false|string
     */
    public function orderStat()
    {
        $data = [];
        //店铺的待结算金额
        $settlement_model = new ShopSettlement();
        $settlement_info = $settlement_model->getWaitSettlementInfo($this->site_id);
        $wait_settlement = $settlement_info[ 'shop_money' ] - $settlement_info[ 'refund_shop_money' ] - $settlement_info[ 'commission' ];
        $data[ 'wait_settlement' ] = number_format($wait_settlement, 2, '.', '');

        //店铺的已结算金额
        $finish_condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'order_status', '=', 10 ],
            [ 'settlement_id', '>', 0 ]
        ];
        $settlement_info = $settlement_model->getShopSettlementData($finish_condition);
        $finish_settlement = $settlement_info[ 'shop_money' ] - $settlement_info[ 'refund_shop_money' ] - $settlement_info[ 'commission' ];
        $data[ 'finish_settlement' ] = number_format($finish_settlement, 2, '.', '');

        //店铺的进行中金额
        $settlement_condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'order_status', "not in", [ 0, -1, 10 ] ]
        ];
        $settlement_info = $settlement_model->getShopSettlementData($settlement_condition);
        $settlement = $settlement_info[ 'shop_money' ] - $settlement_info[ 'refund_shop_money' ] - $settlement_info[ 'commission' ];
        $data[ 'settlement' ] = number_format($settlement, 2, '.', '');

        return $this->response($this->success($data));
    }

    /**
     * 入驻费用统计
     * @return false|string
     */
    public function feeStat()
    {
        $data = [];

        $site_id = $this->site_id;//店铺ID
        //获取店铺信息
        $condition[] = [ 'site_id', '=', $site_id ];
        $apply_model = new ShopModel();
        $apply_info = $apply_model->getShopInfo($condition, '*');
        $apply_data = $apply_info[ 'data' ];

        //店铺的到期时间（0为永久授权）
        if ($apply_data != null) {
            if ($apply_data[ 'expire_time' ] == 0) {
                $apply_data[ 'is_reopen' ] = 1;//永久有效
            } elseif ($apply_data[ 'expire_time' ] > time()) {
                $cha = $apply_data[ 'expire_time' ] - time();
                $date = ceil(( $cha / 86400 ));
                if ($date < 30) {
                    $apply_data[ 'is_reopen' ] = 2;//离到期一月内才可以申请续签
                }
            } else {
                $apply_data[ 'is_reopen' ] = 2;
            }

            $apply_data[ 'expire_time' ] = $apply_data[ 'expire_time' ] == 0 ? '永久有效' : date("Y-m-d H:i:s", $apply_data[ 'expire_time' ]);
        }
        $data[ 'apply_data' ] = $apply_data;

        //判断是否有续签
        $reopen_model = new ShopReopenModel();
        $reopen_info = $reopen_model->getReopenInfo([ [ 'sr.site_id', '=', $this->site_id ], [ 'sr.apply_state', 'in', [ 1, -1 ] ] ]);
        if (empty($reopen_info[ 'data' ])) {
            $is_reopen = 1;
        } else {
            $is_reopen = 2;
        }
        $data[ 'is_reopen' ] = $is_reopen;

        return $this->response($this->success($data));
    }

    /**
     * 店铺费用明细
     */
    public function fee()
    {
        $shop_open = new ShopOpenAccount();
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $list = $shop_open->getShopOpenAccountPageList([ [ 'site_id', '=', $this->site_id ] ], $page, $page_size, 'id desc');
        return $this->response($list);
    }

    /**
     * 续签记录
     */
    public function reopenList()
    {
        $shop_reopen = new ShopReopenModel();
        //获取续签信息
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        $list = $shop_reopen->getReopenPageList([ [ 'site_id', '=', $this->site_id ] ], $page, $page_size);
        return $this->response($list);
    }

}