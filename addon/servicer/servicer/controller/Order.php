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

namespace addon\servicer\servicer\controller;

use app\model\order\OrderCommon as OrderCommonModel;

/**
 * 订单相关
 * Class Order
 * @package addon\servicer\servicer\controller
 */
class Order extends BaseServicer
{
    /**
     * 订单列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page         = input('page', 1);
            $page_size    = input('page_size', PAGE_LIST_ROWS);
            $search_type  = input('search_type', 'order_no');
            $search_text  = input('search_text', '');
            $order_status = input('order_status', '');
            $member_id    = input('member_id', 0);

            $alias                 = '';
            $alias_dot             = '';
            $join                  = [];
            $order_goods_alias_dot = '';
            $condition             = [];

            if ($order_status !== '') {

                if ($order_status === 'refunding') {
                    // 退款中
                    $alias                 = 'o';
                    $alias_dot             = $alias . '.';
                    $order_goods_alias     = 'og';
                    $order_goods_alias_dot = $order_goods_alias . '.';
                    $join                  = [
                        ['order_goods ' . $order_goods_alias, $order_goods_alias_dot . 'order_id = ' . $alias_dot . 'order_id', 'left']
                    ];
                    $condition[]           = [$order_goods_alias_dot . 'refund_status', 'not in', [0, 3]];
                } else {
                    // 正常状态
                    $alias       = '';
                    $alias_dot   = '';
                    $condition[] = [$alias_dot . 'order_status', '=', $order_status];
                }
            }
            $condition[] = [$alias_dot . 'member_id', '=', $member_id];
            if (!empty($this->site_id)) {
                $condition[] = [$alias_dot . 'site_id', '=', $this->site_id];
            }
            if (!empty($search_text) && in_array($search_type, ['order_no', 'order_name'])) {
                $condition[] = [$alias_dot . $search_type, 'like', '%' . $search_text . '%'];
            }

            $order_by          = $alias_dot . 'order_id desc';
            $field             = [
                $alias_dot . 'order_id, ' . $alias_dot . 'order_no, ' . $alias_dot . 'create_time, ' . $alias_dot . 'pay_type_name',
                $alias_dot . 'pay_time, ' . $alias_dot . 'order_status_name, ' . $alias_dot . 'remark',
                $alias_dot . 'goods_money, ' . $alias_dot . 'delivery_money, ' . $alias_dot . 'order_money'
            ];
            $order_goods_field = [
                'order_goods_id, goods_id, sku_id, sku_name, sku_image, is_present, price, num, refund_status_name',
                'goods_name'
            ];

            $order_common_model = new OrderCommonModel();
            $res                = $order_common_model->getOrderPageList($condition, $page, $page_size, $order_by, $field, $alias, $join, $order_goods_field);
            return $res;
        }
    }

    /**
     * 订单详情
     */
    public function detail()
    {
        if (request()->isAjax()) {
            $order_common_model = new OrderCommonModel();
            $res                = $order_common_model->getOrderDetail([['order_id', '=', input('order_id', 0)]]);
            return $res;
        }
    }

    /**
     * 会员已购商品列表
     */
    public function memberOrderGoodsList()
    {
        if (request()->isAjax()) {
            $page        = input('page', 1);
            $page_size   = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $member_id   = input('member_id', 0);

            $alias     = 'og';
            $join      = [
                ['goods_sku gs', 'gs.sku_id = og.sku_id', 'inner']
            ];
            $condition = [['og.member_id', '=', $member_id], ['og.refund_status', '=', 0]];
            if (!empty($this->site_id)) {
                $condition[] = ['og.site_id', '=', $this->site_id];
            }
            if (!empty($search_text)) {
                $condition[] = ['og.sku_name|gs.sku_name|gs.keywords', 'like', '%' . $search_text . '%'];
            }
            $order_by = 'og.order_goods_id desc';
            $field    = [
                'gs.sku_id, gs.goods_id, gs.sku_name, gs.sku_image, gs.price, gs.goods_state, gs.stock, gs.sale_num',
                'og.num'
            ];

            $order_common_model = new OrderCommonModel();
            $res                = $order_common_model->getOrderGoodsPageList($condition, $page, $page_size, $order_by, $field, $alias, $join);
            return $res;
        }
    }
}