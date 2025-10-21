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

namespace addon\supply\supply\controller;

use addon\supply\model\SupplySettlement;
use addon\supply\model\order\OrderCommon as OrderCommonModel;

/**
 * 供应商结算
 * @author Administrator
 *
 */
class Settlement extends BaseSupply
{

    /**
     *结算列表 
     */
    public function lists()
    {
        if(request()->isAjax()){

            $model = new SupplySettlement();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition[] = ['site_id','=',$this->supply_id];

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
            $field = 'id,settlement_no,site_id,period_id,site_name,order_money,supply_money,refund_platform_money,platform_money,refund_supply_money,
        refund_money,create_time,period_start_time,period_end_time,commission';
            $list = $model->getSupplySettlementPageList($condition, $page, $page_size, $order, $field);
            return $list;
        }

        return $this->fetch('settlement/lists');
    }
    
    /**
     * 结算详情
     */
    public function detail()
    {
        $model = new SupplySettlement();
        $id = input('id', '');
        if (request()->isAjax()) {
            $order_model = new OrderCommonModel();
            $condition[] = ['settlement_id','=',$id];
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $field = 'order_id,order_no,order_money,order_status,supply_money,platform_money,refund_money,refund_supply_money,refund_platform_money,commission,finish_time';
            $list = $order_model->getOrderPageList($condition, $page, $page_size, 'finish_time desc', $field);

            return $list;
        }else{
            $field = 'id,settlement_no,site_id,period_id,site_name,order_money,supply_money,refund_platform_money,platform_money,refund_supply_money,
        refund_money,create_time,period_start_time,period_end_time,commission';
            $info = $model->getSupplySettlementInfo([['id', '=', $id]], $field);

            $info = $info['data'];
            //供应商收入
            $supply_money =$info['supply_money'] - $info['refund_supply_money'] - $info['commission'];
            $this->assign('supply_money', number_format($supply_money, 2, '.', ''));
            //平台收入
            $money = $info['platform_money'] - $info['refund_platform_money'];
            $this->assign('money', number_format($money, 2, '.', ''));

            $this->assign('info', $info);
            return $this->fetch('settlement/detail');
        }

    }
}