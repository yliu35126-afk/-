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

namespace addon\presale\api\controller;

use addon\presale\model\PresaleOrder;
use addon\presale\model\PresaleOrderCommon;
use addon\presale\model\PresaleOrderCreate as OrderCreateModel;
use app\api\controller\BaseApi;
use app\model\order\OrderCommon;

/**
 * 预售订单
 */
class Order extends BaseApi
{

    /**
     * 订单列表
     * @return false|string
     */
    public function page()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $site_id = isset($this->params[ 'site_id' ]) ? $this->params[ 'site_id' ] : '';
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $condition = [
            ['member_id','=',$this->member_id]
        ];
        if($site_id > 0){
            $condition[] = ['site_id', '=', $site_id];
        }
        $order_status  = isset($this->params[ 'order_status' ]) ? $this->params[ 'order_status' ] : '';
        if($order_status !== ''){
            $condition[] = ['order_status','=',$order_status];
        }

        $presale_order_model = new PresaleOrder();
        $list = $presale_order_model->getPresaleOrderPageList($condition, $page, $page_size);
        if (!empty($list['data']['list'])) {
            $order_model = new OrderCommon();
            foreach ($list['data']['list'] as $k => $v) {
                $action = empty($v["order_status_action"]) ? [] : json_decode($v["order_status_action"], true);
                $member_action = $action["member_action"] ?? [];
                $list['data']['list'][$k]['action'] = $member_action;
                unset($list['data']['list'][$k]['order_status_action']);
                if ($v["order_status"] >= PresaleOrderCommon::ORDER_PAY){

                    $order_condition = [
                        ['order_no','=',$v['order_no']]
                    ];
                    $order_info = $order_model->getOrderInfo($order_condition, 'order_status,order_status_name')['data'] ?? [];
                    if (!empty($order_info)){
                        $list['data']['list'][$k]['order_status'] = $order_info['order_status'];
                        $list['data']['list'][$k]['order_status_name'] = $order_info['order_status_name'];
                    }
                }
            }
        }

        return $this->response($list);
    }

	/**
	 * 订单详情
	 */
	public function detail()
	{
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
		if (empty($id)) {
			return $this->response($this->error('', '缺少必须参数order_id'));
		}

        $presale_order_model = new PresaleOrder();
		$condition = [
			['id', '=', $id],
			['member_id', '=', $this->member_id],
		];
		$order_info = $presale_order_model->getPresaleOrderInfo($condition);
		if (!empty($order_info['data'])) {
            $action = empty($order_info['data']["order_status_action"]) ? [] : json_decode($order_info['data']["order_status_action"], true);
            $member_action = $action["member_action"] ?? [];
            $order_info['data']['action'] = $member_action;
            unset($order_info['data']['order_status_action']);
        }
		if(isset($order_info['data']['order_status']) && $order_info['data']['order_status'] >= PresaleOrderCommon::ORDER_PAY){
		    $order_model = new OrderCommon();
		    $order_condition = [
		        ['order_no','=',$order_info['data']['order_no']]
            ];
            $order = $order_model->getOrderInfo($order_condition, 'order_status,order_status_name')['data'] ?? [];
		    if (!empty($order)){
                $order_info['data']['order_status'] = $order['order_status'];
                $order_info['data']['order_status_name'] = $order['order_status_name'];
            }
        }
		return $this->response($order_info);
	}

    /**
     * 关闭订单
     */
	public function close()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        if (empty($id)) {
            return $this->response($this->error('', '缺少必须参数order_id'));
        }

        $order_common_model = new PresaleOrderCommon();
        $condition = [
            ['id', '=', $id],
            ['member_id', '=', $this->member_id],
        ];

        $res = $order_common_model->depositOrderClose($condition);
        return $this->response($res);
    }

    /**
     * 删除订单
     */
    public function delete()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        if (empty($id)) {
            return $this->response($this->error('', '缺少必须参数order_id'));
        }

        $order_common_model = new PresaleOrderCommon();
        $condition = [
            ['id', '=', $id],
            ['member_id', '=', $this->member_id],
        ];

        $res = $order_common_model->deleteOrder($condition);
        return $this->response($res);
    }


    /**
     * 获取定金或尾款交易流水号
     */
    public function pay()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $id = isset($this->params['id']) ? $this->params['id'] : ''; //预售订单id
        $order_common_model = new PresaleOrderCommon();
        $res = $order_common_model->getPresaleOrderOutTradeNo($id,$this->member_id);
        return $this->response($res);
    }
}