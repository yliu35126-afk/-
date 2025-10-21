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

namespace addon\fenxiao\admin\controller;

use addon\fenxiao\model\FenxiaoOrder;
use app\admin\controller\BaseAdmin;
use addon\fenxiao\model\Config as ConfigModel;

/**
 *  分销订单
 */
class Order extends BaseAdmin
{

    /**
     * 分销等级列表
     */
    public function lists()
    {
        $model = new FenxiaoOrder();

        if (request()->isAjax()) {

            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $status = input('status', 0);
            $condition = [];
            if ($status == 3) {
                $condition[] = [ 'fo.is_refund', '=', 1 ];
            }
            if (in_array($status, [ 1, 2 ])) {
                $condition[] = [ 'fo.is_settlement', '=', $status - 1 ];
            }
            $search_text_type = input('search_text_type', "goods_name");//订单编号/店铺名称/商品名称
            $search_text = input('search_text', "");
            if (!empty($search_text)) {
                $condition[] = [ 'fo.' . $search_text_type, 'like', '%' . $search_text . '%' ];
            }
            //下单时间
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ 'o.create_time', '>=', date_to_time($start_time) ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'o.create_time', '<=', date_to_time($end_time) ];
            } elseif (!empty($start_time) && !empty(date_to_time($end_time))) {
                $condition[] = [ 'o.create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }

            $list = $model->getFenxiaoOrderPageList($condition, $page_index, $page_size);
            return $list;

        } else {
            //订单状态
            return $this->fetch('order/lists');
        }
    }

    public function detail()
    {
        $fenxiao_order_model = new FenxiaoOrder();
        $fenxiao_order_id = input('fenxiao_order_id', '');
        $order_info = $fenxiao_order_model->getFenxiaoOrderDetail([ [ 'fenxiao_order_id', '=', $fenxiao_order_id ] ]);
        $this->assign('order_info', $order_info[ 'data' ]);

        //获取系统配置
        $config_model = new ConfigModel();
        $basics = $config_model->getFenxiaoBasicsConfig();
        $this->assign("basics_info", $basics[ 'data' ][ 'value' ]);

        return $this->fetch('order/detail');
    }
}