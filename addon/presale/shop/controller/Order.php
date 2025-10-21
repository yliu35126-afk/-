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

namespace addon\presale\shop\controller;

use addon\presale\model\PresaleOrder;
use addon\presale\model\PresaleOrderCommon;
use app\shop\controller\BaseShop;
use app\model\order\OrderCommon as OrderCommonModel;
use think\facade\Config;

/**
 * 预售订单
 */
class Order extends BaseShop
{

	/*
	 *  订单列表
	 */
	public function lists()
	{
	    $presale_id = input('presale_id',0);
        $presale_order_model = new PresaleOrder();
		$condition = [
            [ 'site_id', '=', $this->site_id ],
//            [ 'refund_status', '=', 0 ]
        ];
		if($presale_id > 0){
            $condition[] = ['presale_id','=',$presale_id];
		}
		//获取续签信息
		if (request()->isAjax()) {

			$page = input('page', 1);
			$page_size = input('page_size', PAGE_LIST_ROWS);

			//搜索类型（订单号、商品名称）
			$order_label = input('order_label','order_no');
			$search = input('search','');
			if($search){
                $condition[] = [$order_label,'like','%'.$search.'%'];
            }
            //订单状态
            $order_status = input('order_status','');
            if($order_status !== ''){
                $condition[] = [ 'order_status', '=', $order_status ];
            }
            //订单来源
            $order_from = input("order_from", '');
            if ($order_from) {
                $condition[] = [ "order_from", "=", $order_from ];
            }
            //定金支付方式
            $deposit_pay_type = input("deposit_pay_type", '');
            if ($deposit_pay_type) {
                $condition[] = [ "deposit_pay_type", "=", $deposit_pay_type ];
            }
            //尾款支付方式
            $final_pay_type = input("final_pay_type", '');
            if ($final_pay_type) {
                $condition[] = [ "final_pay_type", "=", $final_pay_type ];
            }
            //订单类型
            $order_type = input('order_type','');
            if ($order_type && $order_type != 'all') {
                $condition[] = [ "order_type", "=", $order_type ];
            }
            //创建时间
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && $end_time) {
                $condition[] = [ 'create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'create_time', '<=', date_to_time($end_time) ];

            } elseif ($start_time && !$end_time) {
                $condition[] = [ 'create_time', '>=', date_to_time($start_time) ];
            }
            $alias = 'o';
            $join = [
                ['member m', 'o.member_id = m.member_id', 'inner']
            ];
            $field = "o.*,m.nickname";
			$list = $presale_order_model->getPresaleOrderPageList($condition, $page, $page_size, 'id desc', $field, $alias, $join);
            if(!empty($list['data']['list'])){
                foreach($list['data']['list'] as $k=>$v){
                    if($v['pay_start_time']<=time()){
                        $action_array = json_decode($v['order_status_action'], true);
                        $list['data']['list'][$k]['action'] = $action_array['action'];
                    }
                }
            }
			return $list;
		} else {
            //搜索方式
            $order_label_list = array(
                "order_no" => "订单号",
                "goods_name" => "商品名称",
                "name" => "收货人姓名",
                "mobile" => "收货人手机号",
            );
            $this->assign('order_label_list',$order_label_list);
            //订单状态
            $order_model = new PresaleOrderCommon();
            $order_status_list = $order_model->order_status;
            $this->assign("order_status_list", $order_status_list);

            //订单来源 (支持端口)
            $order_from = Config::get("app_type");
            $this->assign('order_from_list', $order_from);

            $order_common_model = new OrderCommonModel();
            $order_type_list = $order_common_model->getOrderTypeStatusList();
            $this->assign("order_type_list", $order_type_list);

            $pay_type = $order_common_model->getPayType();
            $this->assign("pay_type_list", $pay_type);

            $this->assign('presale_id',$presale_id);
            return $this->fetch("order/lists");
		}
	}

    /**
     * 订单详情
     * @return mixed
     */
	public function detail()
    {
        $presale_order_model = new PresaleOrder();

        $id = input('id','');

        $condition = [
            [ 'site_id', '=', $this->site_id ],
            [ 'id', '=', $id ]
        ];

        $info = $presale_order_model -> getPresaleOrderInfo($condition);

        $this->assign('order_detail',$info['data']);

        return $this->fetch("order/detail");
    }

    /**
     * 关闭订单
     */
    public function close()
    {
        if(request()->isAjax()){
            $id = input('order_id');
            $order_common_model = new PresaleOrderCommon();

            $condition = [
                ['id', '=', $id],
                ['site_id','=',$this->site_id]
            ];

            $res = $order_common_model->depositOrderClose($condition);
            return $res;
        }
    }

    /**
     * 删除订单
     */
    public function deleteOrder()
    {
        if(request()->isAjax()){

            $id = input('order_id');

            $order_common_model = new PresaleOrderCommon();
            $condition = [
                ['id', '=', $id],
                ['site_id','=',$this->site_id]
            ];

            $res = $order_common_model->deleteOrder($condition);
            return $res;
        }
    }

    /**
     * 线下支付定金
     */
    public function offlinePayDeposit()
    {
//        if(request()->isAjax()){
//
//            $id = input('order_id');
//            $order_common_model = new PresaleOrderCommon();
//
//            $res = $order_common_model->offlinePayDeposit($id,$this->site_id);
//            return $res;
//        }
    }

    /**
     * 线下支付尾款
     */
    public function offlinePayFinal()
    {
//        if(request()->isAjax()){
//
//            $id = input('order_id');
//            $order_common_model = new PresaleOrderCommon();
//
//            $res = $order_common_model->offlinePayFinal($id,$this->site_id);
//            return $res;
//        }
    }

}