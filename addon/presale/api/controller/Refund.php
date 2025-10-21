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
use addon\presale\model\PresaleOrderRefund;
use app\api\controller\BaseApi;

/**
 * 预售订单退款
 */
class Refund extends BaseApi
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
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $condition = [
            ['site_id', '=', $this->site_id],
            ['member_id','=',$this->member_id],
        ];

        $refund_status  = isset($this->params[ 'refund_status' ]) ? $this->params[ 'refund_status' ] : 0;
        if($refund_status){
            $condition[] = ['refund_status','=',$refund_status];
        }else{
            $condition[] = ['refund_status','<>',0];
        }

        $presale_order_model = new PresaleOrder();
        $list = $presale_order_model->getPresaleOrderPageList($condition, $page, $page_size);

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
		return $this->response($order_info);
	}

    /**
     * 申请退款（定金）
     * @return false|string
     */
    public function applyRefund()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        if (empty($id)) {
            return $this->response($this->error('', '缺少必须参数order_id'));
        }
      // $site_id=$this->params['site_id'];//站点id
        $data = [
            'id' => $id,
         //   'site_id' => $site_id,
            'member_id' => $this->member_id
        ];

        $presale_order_refund_model = new PresaleOrderRefund();
        $res = $presale_order_refund_model -> applyRefund($data);
        return $this->response($res);
    }


    /**
     * 取消申请退款
     */
    public function cancelRefund()
    {

        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $id = isset($this->params[ 'order_id' ]) ? $this->params[ 'order_id' ] : 0;
        if (empty($id)) {
            return $this->response($this->error('', '缺少必须参数order_id'));
        }

        $condition = [
            ['id', '=',  $id],
            ['member_id', '=', $this->member_id]
        ];

        $presale_order_refund_model = new PresaleOrderRefund();
        $res = $presale_order_refund_model -> cancelRefund($condition);
        return $this->response($res);
    }

}