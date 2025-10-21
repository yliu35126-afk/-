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

namespace addon\presale\admin\controller;

use addon\presale\model\PresaleOrder;
use addon\presale\model\PresaleOrderRefund;
use app\admin\controller\BaseAdmin;
use app\model\order\OrderCommon as OrderCommonModel;
use think\facade\Config;

/**
 * 退款订单
 */
class Refund extends BaseAdmin
{
    /*
     *  订单列表
     */
    public function lists()
    {
        $presale_order_model = new PresaleOrder();

        $condition = [
//            [ 'site_id', '=', $this->site_id ],
        ];
        //获取续签信息
        if (request()->isAjax()) {

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            //搜索类型
            $order_label = input('order_label', 'order_no');
            $search = input('search', '');
            if ($search) {
                $condition[] = [ $order_label, 'like', '%' . $search . '%' ];
            }
            //退款状态
            $refund_status = input('refund_status', '');
            if ($refund_status !== '') {
                $condition[] = [ 'refund_status', '=', $refund_status ];
            } else {
                $condition[] = [ 'refund_status', '<>', 0 ];
            }
            //订单来源
            $order_from = input("order_from", '');
            if ($order_from != "") {
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
            $order_type = input('order_type', '');
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
            $list = $presale_order_model->getPresaleOrderPageList($condition, $page, $page_size, 'id desc');
            return $list;
        } else {
            //搜索方式
            $order_label_list = array (
                "deposit_refund_no" => "退款编号",
                "order_no" => "订单号",
                "goods_name" => "商品名称",
                "name" => "收货人姓名",
                "mobile" => "收货人手机号",
            );
            $this->assign('order_label_list', $order_label_list);
            //订单状态
            $order_model = new PresaleOrderRefund();
            $order_status_list = $order_model->order_refund_status;
            $this->assign("order_status_list", $order_status_list);

            //订单来源 (支持端口)
            $order_from = Config::get("app_type");
            $this->assign('order_from_list', $order_from);

            $order_common_model = new OrderCommonModel();
            $order_type_list = $order_common_model->getOrderTypeStatusList();
            $this->assign("order_type_list", $order_type_list);

            $pay_type = $order_common_model->getPayType();
            $this->assign("pay_type_list", $pay_type);

            return $this->fetch("refund/lists");
        }
    }

    /**
     * 订单详情
     * @return mixed
     */
    public function detail()
    {
        $presale_order_model = new PresaleOrder();

        $id = input('id', '');

        $condition = [
//            [ 'site_id', '=', $this->site_id ],
            [ 'id', '=', $id ]
        ];

        $info = $presale_order_model->getPresaleOrderInfo($condition);
        $this->assign('detail', $info['data']);

        return $this->fetch('refund/detail');
    }

    /**
     * 拒绝退款
     */
    public function refuse()
    {
        $presale_order_refund_model = new PresaleOrderRefund();

        $data = [
            'id' => input('id', ''),
//            'site_id' => $this->site_id,
            'refuse_reason' => input('refuse_reason', ''),
        ];
        $res = $presale_order_refund_model->refuseRefund($data);
        return $res;
    }

    /**
     * 同意退款
     */
    public function agree()
    {
        $presale_order_refund_model = new PresaleOrderRefund();

        $data = [
            'id' => input('id', ''),
//            'site_id' => $this->site_id,
        ];

        $res = $presale_order_refund_model->agreeRefund($data);
        return $res;
    }

}