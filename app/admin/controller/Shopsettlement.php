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

namespace app\admin\controller;

use app\model\order\OrderCommon;
use app\model\shop\Shop as ShopModel;
use app\model\shop\ShopSettlement as ShopSettlementModel;
use app\model\web\Account as AccountModel;

/**
 * 店铺结算
 * @author Administrator
 *
 */
class Shopsettlement extends BaseAdmin
{

    /**
     * 店铺结算列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $start_date = input('start_date', '');
            $end_date = input('end_date', '');
            $condition = [];
            if ($start_date != '' && $end_date != '') {
                $condition[] = [ 'period_start_time', '>=', strtotime($start_date) ];
                $condition[] = [ 'period_end_time', '<=', strtotime($end_date) ];
            } else if ($start_date != '' && $end_date == '') {
                $condition[] = [ 'period_start_time', '>=', strtotime($start_date) ];
            } else if ($start_date == '' && $end_date != '') {
                $condition[] = [ 'period_end_time', '<=', strtotime($end_date) ];
            }
            $order = 'create_time desc';
            $shop_settlement_model = new ShopSettlementModel();
            $res = $shop_settlement_model->getShopSettlementPeriodPageList($condition, $page, $page_size, $order);
            if (!empty($res[ 'data' ][ 'list' ])) {
                foreach ($res[ 'data' ][ 'list' ] as $k => $v) {
                    $shop_money_actual = $v[ 'shop_money' ] - $v[ 'refund_shop_money' ] - $v[ 'commission' ] + $v[ 'platform_coupon_money' ] - $v[ 'refund_platform_coupon_money' ];
                    $res[ 'data' ][ 'list' ][ $k ][ 'shop_money_actual' ] = number_format($shop_money_actual, 2, '.', '');

                    $money_actual = $v[ 'platform_money' ] - $v[ 'refund_platform_money' ];
                    $res[ 'data' ][ 'list' ][ $k ][ 'money_actual' ] = number_format($money_actual, 2, '.', '');
                }
            }
            return $res;
        } else {

            $account_model = new AccountModel();
            $shop_settlement_sum = $account_model->getShopSettlementSum();
            $settlement_sum = $shop_settlement_sum[ 'data' ][ 'shop_money' ] - $shop_settlement_sum[ 'data' ][ 'refund_shop_money' ] - $shop_settlement_sum[ 'data' ][ 'commission' ] + $shop_settlement_sum[ 'data' ][ 'platform_coupon_money' ] - $shop_settlement_sum[ 'data' ][ 'refund_platform_coupon_money' ];
            $this->assign('shop_settlement', number_format($settlement_sum, 2, '.', ''));
            $this->assign('platform_money', number_format($shop_settlement_sum[ 'data' ][ 'platform_money' ], 2, '.', ''));

            $is_addon_city = addon_is_exit('city');
            $this->assign('is_addon_city', $is_addon_city);
            if ($is_addon_city == 1) {
                //分站总抽成
                $this->assign('website_commission', number_format($shop_settlement_sum[ 'data' ][ 'website_commission' ], 2, '.', ''));
            }

            return $this->fetch("shopsettlement/lists");
        }
    }

    /**
     * 店铺结算详情(显示店铺结算店铺列表)
     */
    public function detail()
    {
        $shop_settlement_model = new ShopSettlementModel();
        if (request()->isAjax()) {
            $period_id = input('period_id', '');
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $start_date = input('start_date', '');
            $end_date = input('end_date', '');
            $condition = [];

            if ($search_text) {
                $condition[] = [ 'site_name', 'like', '%' . $search_text . '%' ];
            }
            if ($start_date != '' && $end_date != '') {
                $condition[] = [ 'create_time', 'between', [ strtotime($start_date), strtotime($end_date) ] ];
            } else if ($start_date != '' && $end_date == '') {
                $condition[] = [ 'create_time', '>=', strtotime($start_date) ];
            } else if ($start_date == '' && $end_date != '') {
                $condition[] = [ 'create_time', '<=', strtotime($end_date) ];
            }
            $condition[] = [ 'period_id', '=', $period_id ];
            $order = 'id desc';

            $res = $shop_settlement_model->getShopSettlementPageList($condition, $page, $page_size, $order);
            if (!empty($res[ 'data' ][ 'list' ])) {
                foreach ($res[ 'data' ][ 'list' ] as $k => $v) {
                    $shop_money_actual = $v[ 'shop_money' ] - $v[ 'refund_shop_money' ] - $v[ 'commission' ] + $v[ 'platform_coupon_money' ] - $v[ 'refund_platform_coupon_money' ];
                    $res[ 'data' ][ 'list' ][ $k ][ 'shop_money_actual' ] = number_format($shop_money_actual, 2, '.', '');

                    $money_actual = $v[ 'platform_money' ] - $v[ 'refund_platform_money' ];
                    $res[ 'data' ][ 'list' ][ $k ][ 'money_actual' ] = number_format($money_actual, 2, '.', '');
                }
            }
            return $res;
        } else {
            $period_id = input('period_id', 0);
            $this->assign("period_id", $period_id);
            $shop_settlement_period_info = $shop_settlement_model->getShopSettlementPeriodInfo([ 'period_id' => $period_id ], '*');

            $shop_settlement_period = $shop_settlement_period_info[ 'data' ];

            //店铺收入
            $shop_money = $shop_settlement_period[ 'shop_money' ] - $shop_settlement_period[ 'refund_shop_money' ] - $shop_settlement_period[ 'commission' ] + $shop_settlement_period[ 'platform_coupon_money' ] - $shop_settlement_period[ 'refund_platform_coupon_money' ];
            $this->assign('shop_money', number_format($shop_money, 2, '.', ''));
            //平台收入
            $money = $shop_settlement_period[ 'platform_money' ] - $shop_settlement_period[ 'refund_platform_money' ];
            $this->assign('money', number_format($money, 2, '.', ''));

            $is_addon_city = addon_is_exit('city');
            $this->assign('is_addon_city', $is_addon_city);
            $this->assign("info", $shop_settlement_period);
            return $this->fetch("shopsettlement/detail");
        }
    }

    /**
     * 具体店铺详情
     */
    public function shopDetail()
    {
        if (request()->isAjax()) {
            $settlement_id = input('settlement_id', '');
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $start_date = input('start_date', '');
            $end_date = input('end_date', '');
            $condition = [];

            if ($start_date != '' && $end_date != '') {
                $condition[] = [ 'finish_time', 'between', [ strtotime($start_date), strtotime($end_date) ] ];
            } else if ($start_date != '' && $end_date == '') {
                $condition[] = [ 'finish_time', '>=', strtotime($start_date) ];
            } else if ($start_date == '' && $end_date != '') {
                $condition[] = [ 'finish_time', '<=', strtotime($end_date) ];
            }
            $condition[] = [ 'settlement_id', '=', $settlement_id ];
            $order = 'finish_time desc';
            $order_common_model = new OrderCommon();

            return $order_common_model->getOrderPageList($condition, $page, $page_size, $order);
        } else {
            $id = input('settlement_id', 0);
            $this->assign("settlement_id", $id);
            $shop_settlement_model = new ShopSettlementModel();
            $shop_settlement_info = $shop_settlement_model->getShopSettlementInfo([ 'id' => $id ], '*');
            $shop_settlement_period = $shop_settlement_info[ 'data' ];

            //店铺名称
            $shop_model = new ShopModel();
            $shop_info = $shop_model->getShopInfo([ [ 'site_id', '=', $shop_settlement_period[ 'site_id' ] ] ], 'site_name');
            $this->assign('shop_info', $shop_info[ 'data' ]);
            //店铺收入
            $shop_money = $shop_settlement_period[ 'shop_money' ] - $shop_settlement_period[ 'refund_shop_money' ] - $shop_settlement_period[ 'commission' ];
            $this->assign('shop_money', number_format($shop_money, 2, '.', ''));
            //平台收入
            $money = $shop_settlement_period[ 'platform_money' ] - $shop_settlement_period[ 'refund_platform_money' ];
            $this->assign('money', number_format($money, 2, '.', ''));
            $this->assign("info", $shop_settlement_info[ 'data' ]);
            return $this->fetch("shopsettlement/shop_detail");
        }
    }
}