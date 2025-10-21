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

use addon\city\model\CitySettlement;
use app\model\shop\ShopSettlement;
use app\model\shop\ShopOpenAccount;

/**
 * 店铺结算
 * @author Administrator
 *
 */
class Settlement extends BaseCity
{

    /**
     *结算列表 
     */
    public function lists()
    {
        if(request()->isAjax()){
            $model = new CitySettlement();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition[] = ['website_id','=',$this->site_id];

            $start_time = input('start_time','');
            $end_time = input('end_time','');
            if(!empty($start_time) && empty($end_time)){
                $condition[] = ['period_start_time','>=',$start_time];
            }elseif(empty($start_time) && !empty($end_time)){
                $condition[] = ['period_end_time','<=',$end_time];
            }elseif(!empty($start_time) && !empty($end_time)){
                $condition[] = ['period_start_time','>=',$start_time];
                $condition[] = ['period_end_time','<=',$end_time];
            }

            $order = 'id desc';
            $list = $model->getCitySettlementPageList($condition, $page, $page_size, $order);
            return $list;
        }

        return $this->fetch('settlement/lists',[],$this->replace);
    }

    /**
     * 结算详情
     */
    public function orderDetail()
    {
        $settlement_id = input('settlement_id',0);

        if(request()->isAjax()){

            $order_model = new ShopSettlement();
            $condition[] = ['settlement_id','=',$settlement_id];
            $condition[] = ['website_id','=',$this->site_id];

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $list = $order_model->getShopSettlementPageList($condition,$page,$page_size);
            return $list;

        }else{

            $this->forthMenu(['settlement_id'=>$settlement_id]);

            $this->assign('settlement_id',$settlement_id);
            return $this->fetch('settlement/order_detail',[],$this->replace);
        }

    }

    /**
     * 店铺入驻
     */
    public function openShopAccount()
    {
        $settlement_id = input('settlement_id',0);
        if(request()->isAjax()){

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $site_name = input('site_name', '');

            $start_time = input("start_time", '');
            $end_time = input("end_time", '');

            $condition[] = ['settlement_id','=',$settlement_id];
            $condition[] = ['website_id','=',$this->site_id];

            if(!empty($site_name)){
                $condition[] = [ 'site_name', 'like', '%' . $site_name . '%' ];
            }

            if(!empty($start_time) && empty($end_time)){
                $condition[] = ['create_time', '>=', date_to_time($start_time)];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = ["create_time", "<=", date_to_time($end_time)];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }

            $shop_open = new ShopOpenAccount();
            $list = $shop_open->getShopOpenAccountPageList($condition, $page, $page_size,'id desc');
            return $list;

        }else{

            $this->forthMenu(['settlement_id'=>$settlement_id]);

            $this->assign('settlement_id',$settlement_id);
            return $this->fetch('settlement/shop_detail',[],$this->replace);
        }
    }

}