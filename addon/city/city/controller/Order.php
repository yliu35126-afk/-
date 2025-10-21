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

use addon\city\model\OrderExport;
use app\model\order\Config as ConfigModel;
use app\model\order\Order as OrderModel;
use app\model\order\OrderCommon as OrderCommonModel;
use app\model\system\Promotion as PromotionModel;
use phpoffice\phpexcel\Classes\PHPExcel;
use phpoffice\phpexcel\Classes\PHPExcel\Writer\Excel2007;
use think\facade\Config;

/**
 * 订单管理 控制器
 */
class Order extends BaseCity
{

    /**
     * 订单列表
     */
    public function lists()
    {
        $order_label_list = array (
            "order_no" => "订单号",
            "out_trade_no" => "外部单号",
            "name" => "收货人姓名",
            "mobile" => "收货人手机号",
            "order_name" => "商品名称",
            "site_name" => "店铺名称",
        );
        $order_status = input("order_status", "");//订单状态
        $order_name = input("order_name", '');
        $pay_type = input("pay_type", '');
        $order_from = input("order_from", '');
        $start_time = input("start_time", '');
        $end_time = input("end_time", '');
        $site_id = input("site_id", "");
        $order_label = !empty($order_label_list[ input("order_label") ]) ? input("order_label") : "";
        $search_text = input("search", '');
        $promotion_type = input("promotion_type", '');
        $order_type = input("order_type", 'all');//营销类型
        $settlement_state = input("settlement_state", "");//结算状态
        $order_common_model = new OrderCommonModel();
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('limit', PAGE_LIST_ROWS);
            $alias = 'o';

            $condition[] = [ 'website_id', '=', $this->site_id ];
            //订单状态
            if ($order_status != "") {
                $condition[] = [ "order_status", "=", $order_status ];
            }
            //订单内容 模糊查询
            if ($order_name != "") {
                $condition[] = [ "order_name", 'like', "%$order_name%" ];
            }
            //订单来源
            if ($order_from != "") {
                $condition[] = [ "order_from", "=", $order_from ];
            }
            //订单支付
            if ($pay_type != "") {
                $condition[] = [ "pay_type", "=", $pay_type ];
            }
            //订单类型
            if ($order_type != 'all') {
                $condition[] = [ "order_type", "=", $order_type ];
            }
            //结算状态
            if ($settlement_state == 1) {
                $condition[] = [ "o.is_settlement", "=", '1' ];
            } elseif ($settlement_state == 2) {
                $condition[] = [ "o.is_settlement", "=", '0' ];
            }
            //营销类型
            if ($promotion_type != "") {
                if ($promotion_type == 'empty') {
                    $condition[] = [ "promotion_type", "=", '' ];
                } else {
                    $condition[] = [ "promotion_type", "=", $promotion_type ];
                }
            }
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ "create_time", ">=", date_to_time($start_time) ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ "create_time", "<=", date_to_time($end_time) ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }
            if ($search_text != "") {
                $condition[] = [ $order_label, 'like', "%$search_text%" ];
            }
            if (!empty($site_id)) {
                $condition[] = [ "site_id", '=', $site_id ];
            }

            $list = $order_common_model->getOrderPageList($condition, $page_index, $page_size, "create_time desc", $field = 'o.*', $alias);
            return $list;
        } else {
            $this->assign("order_label_list", $order_label_list);
            $order_model = new OrderModel();
            $order_status_list = $order_model->order_status;
            $this->assign("order_status_list", $order_status_list);//订单状态
            //订单来源 (支持端口)
            $order_from = Config::get("app_type");
            $this->assign('order_from_list', $order_from);

            $pay_type = $order_common_model->getPayType();
            $this->assign("pay_type_list", $pay_type);

            $order_type_list = $order_common_model->getOrderTypeStatusList();
            $this->assign("order_type_list", $order_type_list);

            //营销活动类型
            $promotion_model = new PromotionModel();
            $promotion_type = $promotion_model->getPromotionType();
            $this->assign("promotion_type", $promotion_type);
            return $this->fetch('order/lists', [], $this->replace);
        }

    }

    /**
     * 快递订单详情
     */
    public function detail()
    {
        $order_id = input("order_id", 0);
        $order_common_model = new OrderCommonModel();
        $condition = array (
            [ 'order_id', '=', $order_id ],
            [ 'website_id', '=', $this->site_id ]
        );
        $order_detail_result = $order_common_model->getOrderDetail($condition);
        $order_detail = $order_detail_result[ "data" ];
        if(empty($order_detail))
            $this->error("查询不到此订单信息!", addon_url('city://city/order/lists'));

        $this->assign("order_detail", $order_detail);

        switch ( $order_detail[ "order_type" ] ) {
            case 1 :
                $template = "order/detail";
                break;
            case 2 :
                $template = "storeorder/detail";
                break;
            case 3 :
                $template = "localorder/detail";
                break;
            case 4 :
                $template = "virtualorder/detail";
                break;
        }

        return $this->fetch($template, [], $this->replace);
    }

    /**
     * 订单设置
     */
    public function config()
    {
        $config_model = new ConfigModel();
        if (request()->isAjax()) {
            //订单事件时间设置数据
            $order_event_time_config_data = [
                'auto_close' => input('order_auto_close_time', 0),//订单未付款自动关闭时间 数字 单位(天)
                'auto_take_delivery' => input('order_auto_take_delivery_time', 0),//订单发货后自动收货时间 数字 单位(天)
                'auto_complete' => input('order_auto_complete_time', 0),//订单收货后自动完成时间 数字 单位(天)
            ];
            // //订单发票设置数据
            // $order_invoice_config_data = [
            //     'is_use' => input('invoice_is_use', 0),//是否启用发票 1:启用 0:不启用
            //     'tax' => input('invoice_tax', 0),//发票税率 0-100 不取小数
            //     'content' => input('invoice_content', ''),//发票内容 文具,服装,水果,建材 格式为逗号分隔的字符串 textarea
            // ];
            $res = $config_model->setOrderEventTimeConfig($order_event_time_config_data);
            // $config_model->setOrderInvoiceConfig($order_invoice_config_data);

            return $res;
        } else {

            //订单事件时间设置
            $order_event_time_config = $config_model->getOrderEventTimeConfig();
            $this->assign('order_event_time_config', $order_event_time_config[ 'data' ][ 'value' ]);

            //订单发票设置
//            $order_invoice_config = $config_model->getOrderInvoiceConfig();
//            $this->assign('order_invoice_config', $order_invoice_config['data']['value']);

            return $this->fetch('order/config', [], $this->replace);
        }
    }

    /**
     * 订单导出（已订单为主）
     */
    public function exportOrder()
    {
        $order_label_list = array (
            "order_no" => "订单号",
            "out_trade_no" => "外部单号",
            "name" => "收货人姓名",
            "mobile" => "收货人手机号",
            "order_name" => "商品名称",
        );
        $order_status = input("order_status", "");//订单状态
        $order_name = input("order_name", '');
        $pay_type = input("pay_type", '');
        $order_from = input("order_from", '');
        $start_time = input("start_time", '');
        $end_time = input("end_time", '');
        $order_label = !empty($order_label_list[ input("order_label") ]) ? input("order_label") : "";
        $search_text = input("search", '');
        $promotion_type = input("promotion_type", '');
        $order_type = input("order_type", 'all');
        $site_id = input("site_id", "");

        $condition_desc = [];
        if (!empty($site_id)) {
            $condition[] = [ "o.site_id", '=', $site_id ];
        }
        $order_common_model = new OrderCommonModel();
        $condition[] = [ 'o.website_id', '=', $this->site_id ];
        //订单类型
        $order_type_name = '全部';
        if ($order_type != 'all') {
            $condition[] = [ "o.order_type", "=", $order_type ];

            $order_type_list = $order_common_model->getOrderTypeStatusList();
            $order_type_list = array_column($order_type_list, 'name', 'type');
            $order_type_name = $order_type_list[ $order_type ];
        }
        $condition_desc[] = [ 'name' => '订单类型', 'value' => $order_type_name ];

        //订单状态
        $order_status_name = '全部';
        if ($order_status != "") {
            if ($order_status != 'refunding') {
                $condition[] = [ "o.order_status", "=", $order_status ];
                $order_status_list = $order_common_model->order_status;
                $order_status_name = $order_status_list[ $order_status ][ 'name' ] ?? '';
            } else {
                $join = [
                    [
                        'order_goods og',
                        'og.order_id = o.order_id',
                        'left'
                    ]
                ];
                $condition[] = [ "og.refund_status", "not in", [ 0, 3 ] ];
                $order_status_name = '维权中';
            }
        }
        $condition_desc[] = [ 'name' => '订单状态', 'value' => $order_status_name ];

        //订单内容 模糊查询
        if ($order_name != "") {
            $condition[] = [ "o.order_name", 'like', "%$order_name%" ];
        }
        //订单来源
        $order_from_name = '全部';
        if ($order_from != "") {
            $condition[] = [ "o.order_from", "=", $order_from ];
            //订单来源 (支持端口)
            $order_from_list = Config::get("app_type");
            $order_from_name = $order_from_list[ $order_from ][ 'name' ] ?? '';
        }
        $condition_desc[] = [ 'name' => '订单来源', 'value' => $order_from_name ];


        //订单支付
        $pay_type_name = '全部';
        if ($pay_type != "") {
            $condition[] = [ "o.pay_type", "=", $pay_type ];
            $pay_type_list = $order_common_model->getPayType();
            $pay_type_name = $pay_type_list[ $pay_type ] ?? '';
        }
        $condition_desc[] = [ 'name' => '支付方式', 'value' => $pay_type_name ];

        //营销类型
        $promotion_type_name = '全部';
        if ($promotion_type != "") {
            if ($promotion_type == 'empty') {
                $condition[] = [ "o.promotion_type", "=", '' ];
            } else {
                $condition[] = [ "o.promotion_type", "=", $promotion_type ];
            }
            //营销活动类型
            $promotion_model = new PromotionModel();
            $promotion_type_list = $promotion_model->getPromotionType();
            $promotion_type_list = array_column($promotion_type_list, 'name', 'type');
            $promotion_type_name = $promotion_type_list[ $promotion_type ] ?? '';
        }
        $condition_desc[] = [ 'name' => '营销活动', 'value' => $promotion_type_name ];
        $time_name = '';
        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ "o.create_time", ">=", date_to_time($start_time) ];
            $time_name = $start_time . '起';
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ "o.create_time", "<=", date_to_time($end_time) ];
            $time_name = '至' . $end_time;
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'o.create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            $time_name = $start_time . ' 至 ' . $end_time;
        }
        $condition_desc[] = [ 'name' => '下单时间', 'value' => $time_name ];

        if ($search_text != "") {
            $condition[] = [ 'o.' . $order_label, 'like', "%$search_text%" ];
        }
        foreach ($order_label_list as $k => $v) {
            $order_label_name = $v;
            if ($k == $order_label) {
                $condition_desc[] = [ 'name' => $order_label_name, 'value' => $search_text ];
            } else {
                $condition_desc[] = [ 'name' => $order_label_name, 'value' => '' ];
            }
        }

        $order_export_model = new OrderExport();
        $result = $order_export_model->orderExport($condition, $condition_desc, $this->site_id,$this->app_module, $join ?? null);
        return $result;
    }
    /**
     * 订单导出（已订单商品为主）
     */
    public function exportOrderGoods()
    {
        $order_label_list = array (
            "order_no" => "订单号",
            "out_trade_no" => "外部单号",
            "name" => "收货人姓名",
            "mobile" => "收货人手机号",
            "order_name" => "商品名称",
        );

        $condition = [];
        $order_status = input("order_status", "");//订单状态
        $order_name = input("order_name", '');
        $pay_type = input("pay_type", '');
        $order_from = input("order_from", '');
        $start_time = input("start_time", '');
        $end_time = input("end_time", '');
        $site_id = input("site_id", "");

        $order_label = !empty($order_label_list[ input("order_label") ]) ? input("order_label") : "";

        $search_text = input("search", '');
        $promotion_type = input("promotion_type", '');
        $order_type = input("order_type", 'all');
        $condition_desc = [];

        $order_common_model = new OrderCommonModel();
        if (!empty($site_id)) {
            $condition[] = [ "o.site_id", '=', $site_id ];
        }
        $condition[] = [ 'o.website_id', '=', $this->site_id ];
        //订单类型
        $order_type_name = '全部';
        if ($order_type != 'all') {
            $condition[] = [ "o.order_type", "=", $order_type ];

            $order_type_list = $order_common_model->getOrderTypeStatusList();
            $order_type_list = array_column($order_type_list, 'name', 'type');
            $order_type_name = $order_type_list[ $order_type ];
        }
        $condition_desc[] = [ 'name' => '订单类型', 'value' => $order_type_name ];

        //订单状态
        $order_status_name = '全部';
        if ($order_status != "") {
            if ($order_status != 'refunding') {
                $condition[] = [ "o.order_status", "=", $order_status ];
                $order_status_list = $order_common_model->order_status;
                $order_status_name = $order_status_list[ $order_status ][ 'name' ] ?? '';
            } else {
                $condition[] = [ "og.refund_status", "not in", [ 0, 3 ] ];
                $order_status_name = '维权中';
            }
        }
        $condition_desc[] = [ 'name' => '订单状态', 'value' => $order_status_name ];

        //订单内容 模糊查询
        if ($order_name != "") {
            $condition[] = [ "o.order_name", 'like', "%$order_name%" ];
        }
        //订单来源
        $order_from_name = '全部';
        if ($order_from != "") {
            $condition[] = [ "o.order_from", "=", $order_from ];
            //订单来源 (支持端口)
            $order_from_list = Config::get("app_type");
            $order_from_name = $order_from_list[ $order_from ][ 'name' ] ?? '';
        }
        $condition_desc[] = [ 'name' => '订单来源', 'value' => $order_from_name ];

        //订单支付
        $pay_type_name = '全部';
        if ($pay_type != "") {
            $condition[] = [ "o.pay_type", "=", $pay_type ];
            $pay_type_list = $order_common_model->getPayType();
            $pay_type_name = $pay_type_list[ $pay_type ] ?? '';
        }
        $condition_desc[] = [ 'name' => '支付方式', 'value' => $pay_type_name ];

        //营销类型
        $promotion_type_name = '全部';
        if ($promotion_type != "") {
            if ($promotion_type == 'empty') {
                $condition[] = [ "o.promotion_type", "=", '' ];
            } else {
                $condition[] = [ "o.promotion_type", "=", $promotion_type ];
            }
            //营销活动类型
            $promotion_model = new PromotionModel();
            $promotion_type_list = $promotion_model->getPromotionType();
            $promotion_type_list = array_column($promotion_type_list, 'name', 'type');
            $promotion_type_name = $promotion_type_list[ $promotion_type ] ?? '';
        }
        $condition_desc[] = [ 'name' => '营销活动', 'value' => $promotion_type_name ];

        $time_name = '';
        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ "o.create_time", ">=", date_to_time($start_time) ];
            $time_name = $start_time . '起';
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ "o.create_time", "<=", date_to_time($end_time) ];
            $time_name = '至' . $end_time;
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'o.create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            $time_name = $start_time . ' 至 ' . $end_time;
        }
        $condition_desc[] = [ 'name' => '下单时间', 'value' => $time_name ];

        if ($search_text != "") {
            $condition[] = [ 'o.' . $order_label, 'like', "%$search_text%" ];
        }
        foreach ($order_label_list as $k => $v) {
            $order_label_name = $v;
            if ($k == $order_label) {
                $condition_desc[] = [ 'name' => $order_label_name, 'value' => $search_text ];
            } else {
                $condition_desc[] = [ 'name' => $order_label_name, 'value' => '' ];
            }
        }
        $order_export_model = new OrderExport();
        $result = $order_export_model->orderGoodsExport($condition, $condition_desc, $this->site_id,$this->app_module);
        return $result;
    }
    /**
     * 订单导出记录
     * @return mixed
     */
    public function export()
    {
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $start_time = input("start_time", '');
            $end_time = input("end_time", '');
            $export_model = new OrderExport();
            $condition = array (
                [ 'site_id', '=', $this->site_id ],
                [ 'app_module', '=', $this->app_module]
            );
            //对时间判断
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ "create_time", ">=", date_to_time($start_time) ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ "create_time", "<=", date_to_time($end_time) ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }
            $result = $export_model->getExportPageList($condition, $page_index, $page_size, 'create_time desc', '*');
            return $result;
        } else {
            return $this->fetch("order/export", [], $this->replace);
        }
    }
    /**
     * 删除订单导出记录
     */
    public function deleteExport()
    {

        if (request()->isAjax()) {
            $export_ids = input('export_ids', '');

            $export_model = new OrderExport();
            $condition = array (
                [ 'site_id', '=', $this->site_id ],
                [ 'app_module', '=', $this->app_module ],
                [ 'export_id', 'in', (string) $export_ids ]
            );
            $result = $export_model->deleteExport($condition);
            return $result;
        }
    }

    /**
     * 订单导出（维权订单）
     */
    public function exportRefundOrder()
    {
        $refund_status = input("refund_status", "");//退款状态
        $sku_name = input("sku_name", '');//商品名称
        $refund_type = input("refund_type", '');//退款方式
        $start_time = input("start_time", '');//开始时间
        $end_time = input("end_time", '');//结束时间
        $order_no = input("order_no", '');//订单编号
        $delivery_status = input("delivery_status", '');//物流状态
        $refund_no = input("refund_no", '');//退款编号

        $delivery_no = input("delivery_no", '');//物流编号
        $refund_delivery_no = input("refund_delivery_no", '');//退款物流编号

        $order_common_model = new OrderCommonModel();

        //退款状态
        if ($refund_status != "") {
            $condition[] = [ "og.refund_status", "=", $refund_status ];
        } else {
            $condition[] = [ "og.refund_status", "<>", 0 ];
        }
        //物流状态
        if ($delivery_status != "") {
            $condition[] = [ "og.delivery_status", "=", $delivery_status ];
        }
        //商品名称
        if ($sku_name != "") {
            $condition[] = [ "og.sku_name", "like", "%$sku_name%" ];
        }
        //退款方式
        if ($refund_type != "") {
            $condition[] = [ "og.refund_type", "=", $refund_type ];
        }
        //退款编号
        if ($refund_no != "") {
            $condition[] = [ "og.refund_no", "like", "%$refund_no%" ];
        }
        //订单编号
        if ($order_no != "") {
            $condition[] = [ "og.order_no", "like", "%$order_no%" ];
        }
        //物流编号
        if ($delivery_no != "") {
            $condition[] = [ "og.delivery_no", "like", "%$delivery_no%" ];
        }
        //退款物流编号
        if ($refund_delivery_no != "") {
            $condition[] = [ "og.refund_delivery_no", "like", "%$refund_delivery_no%" ];
        }

        if (!empty($start_time) && empty($end_time)) {
            $condition[] = [ "og.refund_action_time", ">=", date_to_time($start_time) ];
        } elseif (empty($start_time) && !empty($end_time)) {
            $condition[] = [ "og.refund_action_time", "<=", date_to_time($end_time) ];
        } elseif (!empty($start_time) && !empty($end_time)) {
            $condition[] = [ 'og.refund_action_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
        }
        $order_export_model = new OrderExport();

        $field = array_merge($order_export_model->order_field, $order_export_model->order_goods_field);
        //接收需要展示的字段
        $input_field = input('field', implode(',', array_keys($field)));
        $order = $order_common_model->getOrderGoodsDetailList($condition);
        $header_arr = array (
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ',
            'BA', 'BB', 'BC', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BK', 'BL', 'BM', 'BN', 'BO', 'BP', 'BQ', 'BR', 'BS', 'BT', 'BU', 'BV', 'BW', 'BX', 'BY', 'BZ'
        );

        $input_field = explode(',', $input_field);
        //处理数据

        if (!empty($order[ 'data' ])) {
            $order_list = $order_export_model->handleData($order[ 'data' ], $input_field);
        }

        $count = count($input_field);
        // 实例化excel
        $phpExcel = new \PHPExcel();

        $phpExcel->getProperties()->setTitle("退款维权订单");
        $phpExcel->getProperties()->setSubject("退款维权订单");
        //单独添加列名称
        $phpExcel->setActiveSheetIndex(0);

        for ($i = 0; $i < $count; $i++) {
            $phpExcel->getActiveSheet()->setCellValue($header_arr[ $i ] . '1', $field[ $input_field[ $i ] ]);
        }

        if (!empty($order_list)) {
            foreach ($order_list as $k => $v) {
                $start = $k + 2;
                for ($i = 0; $i < $count; $i++) {

                    $phpExcel->getActiveSheet()->setCellValue($header_arr[ $i ] . $start, $v[ $input_field[ $i ] ]);
                }
            }
        }

        // 重命名工作sheet
        $phpExcel->getActiveSheet()->setTitle('退款维权订单');
        // 设置第一个sheet为工作的sheet
        $phpExcel->setActiveSheetIndex(0);
        // 保存Excel 2007格式文件，保存路径为当前路径，名字为export.xlsx
        $objWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel2007');
        $file = date('Y年m月d日-退款维权订单', time()) . '.xlsx';
        $objWriter->save($file);

        header("Content-type:application/octet-stream");

        $filename = basename($file);
        header("Content-Disposition:attachment;filename = " . $filename);
        header("Accept-ranges:bytes");
        header("Accept-length:" . filesize($file));
        readfile($file);
        unlink($file);
        exit;
    }

    /**
     * 导出字段
     * @return array
     */
    public function getPrintingField()
    {
        $order_export_model = new OrderExport();
        $data = [
            'order_field' => $order_export_model->order_field,
            'order_goods_field' => $order_export_model->order_goods_field
        ];

        return success('1', '', $data);
    }

    /**
     * 交易记录
     */
    public function tradelist()
    {
        $order_common_model = new OrderCommonModel();
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $member_id = input('member_id', 0);//会员id
            $search_text = input('search_text', 0);//h关键字查询
            $condition = array ();
            if ($member_id > 0) {
                $condition[] = [ "member_id", "=", $member_id ];
            }
            if (!empty($search_text)) {
                $condition[] = [ 'order_no|order_name', 'like', '%' . $search_text . '%' ];
            }

            return $order_common_model->getTradePageList($condition, $page, $page_size, "create_time desc");

        }
    }
}