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

namespace addon\store\shopapi\controller;

use addon\store\model\Settlement as SettlementModel;
use app\model\order\OrderCommon as OrderCommonModel;
use app\shopapi\controller\BaseApi;

/**
 * 门店结算控制器
 */
class Settlement extends BaseApi
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
     * 门店结算列表
     */
    public function index()
    {
        $model = new SettlementModel();
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        $condition[] = [ 'site_id', '=', $this->site_id ];

        $start_time = isset($this->params[ 'start_time' ]) ? $this->params[ 'start_time' ] : '';
        $end_time = isset($this->params[ 'end_time' ]) ? $this->params[ 'end_time' ] : '';
        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ 'start_time', '>=', $start_time ];
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'end_time', '<=', $end_time ];
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'start_time', '>=', $start_time ];
            $condition[] = [ 'end_time', '<=', $end_time ];
        }
        $order = 'id desc';
        $field = 'id,settlement_no,site_id,site_name,store_name,order_money,shop_money,refund_platform_money,platform_money,refund_shop_money,
        refund_money,create_time,commission,is_settlement,offline_refund_money,offline_order_money,start_time,end_time,platform_coupon_money,refund_platform_coupon_money,offline_platform_coupon_money, offline_refund_platform_coupon_money';
        $list = $model->getStoreSettlementPageList($condition, $page, $page_size, $order, $field);

        return $this->response($list);
    }

    /**
     * 结算信息
     * @return false|string
     */
    public function info()
    {
        $settlement_id = isset($this->params[ 'settlement_id' ]) ? $this->params[ 'settlement_id' ] : 0;

        $settlement_model = new SettlementModel();
        $info = $settlement_model->getSettlementInfo([ [ 'id', '=', $settlement_id ] ]);
        return $this->response($info);
    }

    /**
     * 已结算
     * @return array|false|string
     */
    public function settlement()
    {
        $remark = isset($this->params[ 'remark' ]) ? $this->params[ 'remark' ] : '';
        $settlement_id = isset($this->params[ 'settlement_id' ]) ? $this->params[ 'settlement_id' ] : 0;
        if (empty($remark)) {
            return error(-1, '请填写备注！');
        }
        $settlement_model = new SettlementModel();
        $res = $settlement_model->editSettlement([ 'is_settlement' => 1, 'remark' => $remark ], [ [ 'id', '=', $settlement_id ] ]);
        return $this->response($res);

    }

    /**
     * detail 结算详情
     */
    public function detail()
    {
        $settlement_id = isset($this->params[ 'settlement_id' ]) ? $this->params[ 'settlement_id' ] : 0;
        $order_model = new OrderCommonModel();

        $condition[] = [ 'store_settlement_id', '=', $settlement_id ];
        $page = input('page', 1);
        $page_size = input('page_size', PAGE_LIST_ROWS);
        $field = 'order_id,order_no,pay_type,order_money,pay_type_name,order_status,shop_money,platform_money,refund_money,refund_shop_money,refund_platform_money,commission,finish_time,platform_coupon_money ,refund_platform_coupon_money';
        $list = $order_model->getOrderPageList($condition, $page, $page_size, 'finish_time desc', $field);

        return $this->response($list);

    }
}