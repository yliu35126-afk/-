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

namespace addon\presale\model;

use app\model\member\MemberAccount;
use app\model\BaseModel;
use addon\coupon\model\Coupon;
use app\model\system\Pay;


/**
 * 订单退款
 *
 * @author Administrator
 *
 */
class PresaleOrderRefund extends BaseModel
{
    //已申请退款
    const REFUND_APPLY = 1;
    //已完成
    const REFUND_COMPLETE = 2;
    //已拒绝
    const REFUND_REFUSE = -1;

    /**
     * 订单退款状态
     * @var unknown
     */
    public $order_refund_status = [
        self::REFUND_APPLY => [
            'status' => self::REFUND_APPLY,
            'name' => '申请退款',
        ],
        self::REFUND_COMPLETE => [
            'status' => self::REFUND_COMPLETE,
            'name' => '退款成功',
        ],
        self::REFUND_REFUSE => [
            'status' => self::REFUND_REFUSE,
            'name' => '退款拒绝',
        ]
    ];

    /*************************************************************************** 用户申请退款操作（start）**********************************/

    /**
     * 用户申请退款
     * @param $data
     * @return array
     */
    public function applyRefund($data)
    {
        $order_info = model('promotion_presale_order')->getInfo(
            [
                ['id', '=', $data['id']],
//                ['site_id', '=', $data['site_id']]
            ],
            'pay_end_time,order_status,refund_status,member_id,balance_final_money,pay_final_money'
        );

        if (empty($order_info) || $order_info['member_id'] != $data['member_id']) {
            return $this->error('', '非法请求！');
        }
        //不能重复申请退款
        if ($order_info['refund_status'] == self::REFUND_APPLY) {
            return $this->error('', '请不要重复申请退款');
        }

        //支付尾款了不能申请退款
        if ($order_info['order_status'] != PresaleOrderCommon::WAIT_FINAL_PAY) {
            return $this->error();
        }

        $pay_model = new Pay();
        $refund_no = $pay_model->createRefundNo($data['id']);

        if ($order_info['refund_status'] == self::REFUND_REFUSE) {
            $order_data = [
                'refund_status' => self::REFUND_APPLY,
                'refund_status_name' => $this->order_refund_status[self::REFUND_APPLY]['name'],
            ];
        } else {
            $order_data = [
                'deposit_refund_no' => $refund_no,
                'refund_status' => self::REFUND_APPLY,
                'refund_status_name' => $this->order_refund_status[self::REFUND_APPLY]['name'],
                'apply_refund_time' => time(),
            ];
        }

        $res = model('promotion_presale_order')->update($order_data, [['id', '=', $data['id']]]);
        return $this->success($res);
    }


    /**
     * 用户取消申请退款
     * @param array $condition
     */
    public function cancelRefund($condition = [])
    {
        $order_info = model('promotion_presale_order')->getInfo(
            $condition,
            'pay_end_time,order_status,refund_status,'
        );

        if (empty($order_info)) {
            return $this->error('', '非法请求！');
        }

        //不能重复申请退款
        if ($order_info['refund_status'] != self::REFUND_APPLY) {
            return $this->error('', '已退款或已取消');
        }

        $order_data = [
            'deposit_refund_no' => '',
            'refund_status' => '',
            'refund_status_name' => '',
            'apply_refund_time' => '',
        ];

        $res = model('promotion_presale_order')->update($order_data, $condition);
        return $this->success($res);
    }


    /**
     * 拒绝退款
     * @param $data
     * @return array
     */
    public function refuseRefund($data)
    {
        $order_info = model('promotion_presale_order')->getInfo(
            [
                ['id', '=', $data['id']], ['site_id', '=', $data['site_id']]
            ]
            , 'refund_status'
        );

        if ($order_info['refund_status'] == self::REFUND_REFUSE) {
            return $this->success();
        }

        $order_data = [
            'refund_status' => self::REFUND_REFUSE,
            'refund_status_name' => $this->order_refund_status[self::REFUND_REFUSE]['name'],
            'refuse_reason' => $data['refuse_reason'],
            'refund_time' => time()
        ];

        $res = model('promotion_presale_order')->update($order_data, [['id', '=', $data['id']], ['site_id', '=', $data['site_id']]]);
        return $this->success($res);
    }

    /**
     * 同意退款
     * @param $data
     * @return array
     */
    public function agreeRefund($data)
    {
        $order_info = model('promotion_presale_order')->getInfo(
            [
                ['id', '=', $data['id']], ['site_id', '=', $data['site_id']]
            ]
        );

        if ($order_info['refund_status'] == self::REFUND_COMPLETE) {
            return $this->success();
        }

        model('promotion_presale_order')->startTrans();
        try {

            //增加库存
            model('promotion_presale')->setInc([['presale_id', '=', $order_info['presale_id']]], 'presale_stock', $order_info['num']);

            //增加门店库存
            if ($order_info['delivery_store_id'] > 0) {
                $presale_order_common = new PresaleOrderCommon();
                $store_data = [
                    'delivery_store_id' => $order_info['delivery_store_id'],
                    'num' => $order_info['num'],
                    'sku_id' => $order_info['sku_id']
                ];
                $store_result = $presale_order_common->incStoreGoodsStock($store_data);
                if ($store_result['code'] < 0) {
                    model('promotion_presale_order')->rollback();
                    return $store_result;
                }
            }

            //返还店铺优惠券
            $coupon_id = $order_info["coupon_id"];
            if ($coupon_id > 0) {
                $coupon_model = new Coupon();
                $coupon_model->refundCoupon($coupon_id, $order_info["member_id"]);
            }
            //平台优惠券

            //平台余额  退还余额（定金余额）
            $balance_money = 0;
            if ($order_info["balance_deposit_money"] > 0) {
                $balance_money += $order_info["balance_deposit_money"];
            }
            if ($order_info["balance_final_money"] > 0) {
                $balance_money += $order_info["balance_final_money"];
            }
            if ($balance_money > 0) {

                $member_account_model = new MemberAccount();
                $member_account_model->addMemberAccount( $order_info["member_id"],"balance", $balance_money, "refund", "余额返还", "订单关闭,返还余额:" . $balance_money);

            }

            //现金原路退还
            if ($order_info["pay_deposit_money"] > 0) {
                $pay_model = new Pay();
                $refund_result = $pay_model->refund($order_info["deposit_refund_no"], $order_info['pay_deposit_money'], $order_info["deposit_out_trade_no"], '', $order_info['pay_deposit_money'], $order_info["site_id"], 1);
                if ($refund_result["code"] < 0) {
                    model('promotion_presale_order')->rollback();
                    return $refund_result;
                }
            }

            $order_common = new PresaleOrderCommon();
            //修改订单退款状态
            $order_data = [
                'order_status' => $order_common::ORDER_CLOSE,
                'order_status_name' => $order_common->order_status[$order_common::ORDER_CLOSE]['name'],
                'order_status_action' => json_encode($order_common->order_status[$order_common::ORDER_CLOSE], JSON_UNESCAPED_UNICODE),
                'refund_status' => self::REFUND_COMPLETE,
                'refund_status_name' => $this->order_refund_status[self::REFUND_COMPLETE]['name'],
                'refund_money' => $balance_money + $order_info["pay_deposit_money"],
                'refund_time' => time(),
            ];
            model('promotion_presale_order')->update($order_data, [['id', '=', $data['id']], ['site_id', '=', $data['site_id']]]);

            //减少预约数量
            model('promotion_presale')->setDec([['presale_id', '=', $order_info['presale_id']]],'sale_num',$order_info['num']);

            model('promotion_presale_order')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('promotion_presale_order')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /*************************************************************************** 用户申请退款操作（end）**********************************/

    /*************************************************************************** 订单退款相关操作（start）**********************************/

    /**
     * 订单退款
     * @param $data
     * @return array|mixed|void
     */
    public function refundPresaleOrder($order_no)
    {
        $order_info = model('promotion_presale_order')->getInfo(
            [
                ['order_no', '=', $order_no]
            ]
        );
        if (empty($order_info)) {
            return $this->success();
        }
        if ($order_info['refund_status'] == self::REFUND_COMPLETE) {
            return $this->success();
        }

        model('promotion_presale_order')->startTrans();
        try {

            //修改订单退款状态
            $pay_model = new Pay();
            $deposit_refund_no = $pay_model->createRefundNo($order_info['order_no']);
            $final_refund_no = $pay_model->createRefundNo($order_info['order_no']);

            //定金原路退还
            if ($order_info["pay_deposit_money"] > 0) {
                $pay_model = new Pay();
                $refund_result = $pay_model->refund($deposit_refund_no, $order_info['pay_deposit_money'], $order_info["deposit_out_trade_no"], '', $order_info['pay_deposit_money'], $order_info["site_id"], 1);
                if ($refund_result["code"] < 0) {
                    model('promotion_presale_order')->rollback();
                    return $refund_result;
                }
            }

            //尾款原路退还
            if ($order_info["pay_final_money"] > 0) {
                $pay_model = new Pay();

                $refund_result = $pay_model->refund($final_refund_no, $order_info['pay_final_money'], $order_info["final_out_trade_no"], '', $order_info['pay_final_money'], $order_info["site_id"], 1);
                if ($refund_result["code"] < 0) {
                    model('promotion_presale_order')->rollback();
                    return $refund_result;
                }
            }

            //平台余额  退还余额（定金余额）
            $balance_money = 0;
            if ($order_info["balance_deposit_money"] > 0) {
                $balance_money += $order_info["balance_deposit_money"];
            }
            if ($order_info["balance_final_money"] > 0) {
                $balance_money += $order_info["balance_final_money"];
            }
            if ($balance_money > 0) {

                $member_account_model = new MemberAccount();
                $member_account_model->addMemberAccount($order_info['site_id'], $order_info["member_id"], "balance", $balance_money, "refund", "余额返还", "订单关闭,返还余额:" . $balance_money);
            }

            $order_common = new PresaleOrderCommon();
            $order_data = [
                'order_status' => $order_common::ORDER_CLOSE,
                'order_status_name' => $order_common->order_status[$order_common::ORDER_CLOSE]['name'],
                'order_status_action' => json_encode($order_common->order_status[$order_common::ORDER_CLOSE], JSON_UNESCAPED_UNICODE),
                'deposit_refund_no' => $deposit_refund_no,
                'final_refund_no' => $final_refund_no,
                'refund_status' => self::REFUND_COMPLETE,
                'refund_status_name' => $this->order_refund_status[self::REFUND_COMPLETE]['name'],
                'refund_money' => $order_info["pay_deposit_money"] + $order_info["pay_final_money"] + $balance_money,
                'refuse_time' => time(),
            ];

            model('promotion_presale_order')->update($order_data, [['order_no', '=', $order_no]]);
            model('promotion_presale_order')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('promotion_presale_order')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /*************************************************************************** 订单退款相关操作（end）**********************************/

}