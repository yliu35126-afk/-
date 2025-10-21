<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shopapi\controller;

use app\model\system\Stat as StatModel;

class Statistics extends BaseApi
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
     * 店铺统计
     * @return mixed
     */
    public function shop()
    {
        $date_type = isset($this->params[ 'date_type' ]) ? $this->params[ 'date_type' ] : 0;
        if ($date_type == 0) {
            $start_time = strtotime("today");
            $time_range = date('Y-m-d', $start_time);
        } else if ($date_type == 1) {
            $start_time = strtotime(date('Y-m-d', strtotime("-6 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
        } else if ($date_type == 2) {
            $start_time = strtotime(date('Y-m-d', strtotime("-29 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
        }

        $stat_model = new StatModel();

        $shop_stat_sum = $stat_model->getShopStatSum($this->site_id, $start_time);

        $shop_stat_sum[ 'data' ][ 'time_range' ] = $time_range;

        return $this->response($shop_stat_sum);
    }

    /**
     * 店铺统计报表
     * */
    public function getShopStatList()
    {
        $date_type = isset($this->params[ 'date_type' ]) ? $this->params[ 'date_type' ] : 1;
        if ($date_type == 1) {
            $start_time = strtotime(date('Y-m-d', strtotime("-6 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
            $day = 6;
        } else if ($date_type == 2) {
            $start_time = strtotime(date('Y-m-d', strtotime("-29 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
            $day = 29;
        }

        $stat_model = new StatModel();

        $stat_list = $stat_model->getShopStatList($this->site_id, $start_time);

        //将时间戳作为列表的主键
        $shop_stat_list = array_column($stat_list[ 'data' ], null, 'day_time');

        $data = array ();

        for ($i = 0; $i <= $day; $i++) {
            $time = strtotime(date('Y-m-d', strtotime("-" . ( $day - $i ) . " day")));
            $data[ 'time' ][ $i ] = date('Y-m-d', $time);
            if (array_key_exists($time, $shop_stat_list)) {
                $data[ 'order_total' ][ $i ] = $shop_stat_list[ $time ][ 'order_total' ];
                $data[ 'shipping_total' ][ $i ] = $shop_stat_list[ $time ][ 'shipping_total' ];
                $data[ 'refund_total' ][ $i ] = $shop_stat_list[ $time ][ 'refund_total' ];
                $data[ 'order_pay_count' ][ $i ] = $shop_stat_list[ $time ][ 'order_pay_count' ];
                $data[ 'goods_pay_count' ][ $i ] = $shop_stat_list[ $time ][ 'goods_pay_count' ];
                $data[ 'shop_money' ][ $i ] = $shop_stat_list[ $time ][ 'shop_money' ];
                $data[ 'platform_money' ][ $i ] = $shop_stat_list[ $time ][ 'platform_money' ];
                $data[ 'collect_shop' ][ $i ] = $shop_stat_list[ $time ][ 'collect_shop' ];
                $data[ 'collect_goods' ][ $i ] = $shop_stat_list[ $time ][ 'collect_goods' ];
                $data[ 'visit_count' ][ $i ] = $shop_stat_list[ $time ][ 'visit_count' ];
                $data[ 'order_count' ][ $i ] = $shop_stat_list[ $time ][ 'order_count' ];
                $data[ 'goods_count' ][ $i ] = $shop_stat_list[ $time ][ 'goods_count' ];
                $data[ 'add_goods_count' ][ $i ] = $shop_stat_list[ $time ][ 'add_goods_count' ];
                $data[ 'member_count' ][ $i ] = $shop_stat_list[ $time ][ 'member_count' ];
            } else {
                $data[ 'order_total' ][ $i ] = 0.00;
                $data[ 'shipping_total' ][ $i ] = 0.00;
                $data[ 'refund_total' ][ $i ] = 0.00;
                $data[ 'order_pay_count' ][ $i ] = 0;
                $data[ 'goods_pay_count' ][ $i ] = 0;
                $data[ 'shop_money' ][ $i ] = 0.00;
                $data[ 'platform_money' ][ $i ] = 0.00;
                $data[ 'collect_shop' ][ $i ] = 0;
                $data[ 'collect_goods' ][ $i ] = 0;
                $data[ 'visit_count' ][ $i ] = 0;
                $data[ 'order_count' ][ $i ] = 0;
                $data[ 'goods_count' ][ $i ] = 0;
                $data[ 'add_goods_count' ][ $i ] = 0;
                $data[ 'member_count' ][ $i ] = 0;
            }
        }

        $data[ 'time_range' ] = $time_range;

        return $this->response($this->success($data));
    }

    /**
     * 商品统计
     * @return mixed
     */
    public function goods()
    {
        $date_type = isset($this->params[ 'date_type' ]) ? $this->params[ 'date_type' ] : 0;
        if ($date_type == 0) {
            $start_time = strtotime("today");
            $time_range = date('Y-m-d', $start_time);
        } else if ($date_type == 1) {
            $start_time = strtotime("-6 day");
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
        } else if ($date_type == 2) {
            $start_time = strtotime("-29 day");
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
        }

        $stat_model = new StatModel();

        $shop_stat_sum = $stat_model->getShopStatSum($this->site_id, $start_time);

        $shop_stat_sum[ 'data' ][ 'time_range' ] = $time_range;

        return $this->response($shop_stat_sum);

    }

    /**
     * 商品统计报表
     * */
    public function getGoodsStatList()
    {
        $date_type = isset($this->params[ 'date_type' ]) ? $this->params[ 'date_type' ] : 1;
        if ($date_type == 1) {
            $start_time = strtotime("-6 day");
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
            $day = 6;
        } else if ($date_type == 2) {
            $start_time = strtotime("-29 day");
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
            $day = 29;
        }

        $stat_model = new StatModel();
        $stat_list = $stat_model->getShopStatList($this->site_id, $start_time);
        //将时间戳作为列表的主键
        $shop_stat_list = array_column($stat_list[ 'data' ], null, 'day_time');

        $data = array ();
        for ($i = 0; $i <= $day; $i++) {
            $time = strtotime(date('Y-m-d', strtotime("-" . ( $day - $i ) . " day")));
            $data[ 'time' ][ $i ] = date('Y-m-d', $time);
            if (array_key_exists($time, $shop_stat_list)) {
                $data[ 'order_total' ][ $i ] = $shop_stat_list[ $time ][ 'order_total' ];
                $data[ 'shipping_total' ][ $i ] = $shop_stat_list[ $time ][ 'shipping_total' ];
                $data[ 'refund_total' ][ $i ] = $shop_stat_list[ $time ][ 'refund_total' ];
                $data[ 'order_pay_count' ][ $i ] = $shop_stat_list[ $time ][ 'order_pay_count' ];
                $data[ 'goods_pay_count' ][ $i ] = $shop_stat_list[ $time ][ 'goods_pay_count' ];
                $data[ 'shop_money' ][ $i ] = $shop_stat_list[ $time ][ 'shop_money' ];
                $data[ 'platform_money' ][ $i ] = $shop_stat_list[ $time ][ 'platform_money' ];
                $data[ 'collect_shop' ][ $i ] = $shop_stat_list[ $time ][ 'collect_shop' ];
                $data[ 'collect_goods' ][ $i ] = $shop_stat_list[ $time ][ 'collect_goods' ];
                $data[ 'visit_count' ][ $i ] = $shop_stat_list[ $time ][ 'visit_count' ];
                $data[ 'order_count' ][ $i ] = $shop_stat_list[ $time ][ 'order_count' ];
                $data[ 'goods_count' ][ $i ] = $shop_stat_list[ $time ][ 'goods_count' ];
                $data[ 'add_goods_count' ][ $i ] = $shop_stat_list[ $time ][ 'add_goods_count' ];
                $data[ 'member_count' ][ $i ] = $shop_stat_list[ $time ][ 'member_count' ];
            } else {
                $data[ 'order_total' ][ $i ] = 0.00;
                $data[ 'shipping_total' ][ $i ] = 0.00;
                $data[ 'refund_total' ][ $i ] = 0.00;
                $data[ 'order_pay_count' ][ $i ] = 0;
                $data[ 'goods_pay_count' ][ $i ] = 0;
                $data[ 'shop_money' ][ $i ] = 0.00;
                $data[ 'platform_money' ][ $i ] = 0.00;
                $data[ 'collect_shop' ][ $i ] = 0;
                $data[ 'collect_goods' ][ $i ] = 0;
                $data[ 'visit_count' ][ $i ] = 0;
                $data[ 'order_count' ][ $i ] = 0;
                $data[ 'goods_count' ][ $i ] = 0;
                $data[ 'add_goods_count' ][ $i ] = 0;
                $data[ 'member_count' ][ $i ] = 0;
            }
        }
        $data[ 'time_range' ] = $time_range;
        return $this->response($this->success($data));
    }


    /**
     * 订单金额及订单数量统计（7、15、30天）
     * @return mixed
     */
    public function orderStatistics()
    {
        $day = isset($this->params[ 'day' ]) ? $this->params[ 'day' ] : 7;

        $stat_shop_model = new StatModel();
        //近十天的订单数以及销售金额
        $date_day = getweeks($day);
        $order_total = '';
        $order_pay_count = '';
        foreach ($date_day as $k => $day) {
            $dayarr = explode('-', $day);
            $stat_day[ $k ] = $stat_shop_model->getStatShop($this->site_id, $dayarr[ 0 ], $dayarr[ 1 ], $dayarr[ 2 ]);
            $order_total .= $stat_day[ $k ][ 'data' ][ 'order_total' ] . ',';
            $order_pay_count .= $stat_day[ $k ][ 'data' ][ 'order_pay_count' ] . ',';
        }
        $ten_day[ 'order_total' ] = explode(',', substr($order_total, 0, strlen($order_total) - 1));
        $ten_day[ 'order_pay_count' ] = explode(',', substr($order_pay_count, 0, strlen($order_pay_count) - 1));

        return $this->response($this->success($ten_day));
    }

    /**
     * 交易统计
     * @return mixed
     */
    public function order()
    {
        $date_type = isset($this->params[ 'date_type' ]) ? $this->params[ 'date_type' ] : 0;
        if ($date_type == 0) {
            $start_time = strtotime("today");
            $time_range = date('Y-m-d', $start_time);
        } else if ($date_type == 1) {
            $start_time = strtotime(date('Y-m-d', strtotime("-6 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
        } else if ($date_type == 2) {
            $start_time = strtotime(date('Y-m-d', strtotime("-29 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
        }

        $stat_model = new StatModel();

        $shop_stat_sum = $stat_model->getShopStatSum($this->site_id, $start_time);

        $shop_stat_sum[ 'data' ][ 'time_range' ] = $time_range;

        return $this->response($shop_stat_sum);
    }

    /**
     * 交易统计报表
     * */
    public function getOrderStatList()
    {
        $date_type = isset($this->params[ 'date_type' ]) ? $this->params[ 'date_type' ] : 1;
        if ($date_type == 1) {
            $start_time = strtotime(date('Y-m-d', strtotime("-6 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
            $day = 6;
        } else if ($date_type == 2) {
            $start_time = strtotime(date('Y-m-d', strtotime("-29 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
            $day = 29;
        }

        $stat_model = new StatModel();

        $stat_list = $stat_model->getShopStatList($this->site_id, $start_time);

        //将时间戳作为列表的主键
        $shop_stat_list = array_column($stat_list[ 'data' ], null, 'day_time');

        $data = array ();

        for ($i = 0; $i <= $day; $i++) {
            $time = strtotime(date('Y-m-d', strtotime("-" . ( $day - $i ) . " day")));
            $data[ 'time' ][ $i ] = date('Y-m-d', $time);
            if (array_key_exists($time, $shop_stat_list)) {
                $data[ 'order_total' ][ $i ] = $shop_stat_list[ $time ][ 'order_total' ];
                $data[ 'shipping_total' ][ $i ] = $shop_stat_list[ $time ][ 'shipping_total' ];
                $data[ 'refund_total' ][ $i ] = $shop_stat_list[ $time ][ 'refund_total' ];
                $data[ 'order_pay_count' ][ $i ] = $shop_stat_list[ $time ][ 'order_pay_count' ];
                $data[ 'goods_pay_count' ][ $i ] = $shop_stat_list[ $time ][ 'goods_pay_count' ];
                $data[ 'shop_money' ][ $i ] = $shop_stat_list[ $time ][ 'shop_money' ];
                $data[ 'platform_money' ][ $i ] = $shop_stat_list[ $time ][ 'platform_money' ];
                $data[ 'collect_shop' ][ $i ] = $shop_stat_list[ $time ][ 'collect_shop' ];
                $data[ 'collect_goods' ][ $i ] = $shop_stat_list[ $time ][ 'collect_goods' ];
                $data[ 'visit_count' ][ $i ] = $shop_stat_list[ $time ][ 'visit_count' ];
                $data[ 'order_count' ][ $i ] = $shop_stat_list[ $time ][ 'order_count' ];
                $data[ 'goods_count' ][ $i ] = $shop_stat_list[ $time ][ 'goods_count' ];
                $data[ 'add_goods_count' ][ $i ] = $shop_stat_list[ $time ][ 'add_goods_count' ];
            } else {
                $data[ 'order_total' ][ $i ] = 0.00;
                $data[ 'shipping_total' ][ $i ] = 0.00;
                $data[ 'refund_total' ][ $i ] = 0.00;
                $data[ 'order_pay_count' ][ $i ] = 0;
                $data[ 'goods_pay_count' ][ $i ] = 0;
                $data[ 'shop_money' ][ $i ] = 0.00;
                $data[ 'platform_money' ][ $i ] = 0.00;
                $data[ 'collect_shop' ][ $i ] = 0;
                $data[ 'collect_goods' ][ $i ] = 0;
                $data[ 'visit_count' ][ $i ] = 0;
                $data[ 'order_count' ][ $i ] = 0;
                $data[ 'goods_count' ][ $i ] = 0;
                $data[ 'add_goods_count' ][ $i ] = 0;
            }
        }

        $data[ 'time_range' ] = $time_range;
        return $this->response($this->success($data));
    }


    /**
     * 访问统计
     * @return mixed
     */
    public function visit()
    {
        $date_type = isset($this->params[ 'date_type' ]) ? $this->params[ 'date_type' ] : 0;

        if ($date_type == 0) {
            $start_time = strtotime("today");
            $time_range = date('Y-m-d', $start_time);
        } else if ($date_type == 1) {
            $start_time = strtotime(date('Y-m-d', strtotime("-6 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
        } else if ($date_type == 2) {
            $start_time = strtotime(date('Y-m-d', strtotime("-29 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
        }

        $stat_model = new StatModel();

        $shop_stat_sum = $stat_model->getShopStatSum($this->site_id, $start_time);

        $shop_stat_sum[ 'data' ][ 'time_range' ] = $time_range;

        return $this->response($shop_stat_sum);

    }

    /**
     * 访问统计报表
     * */
    public function getVisitStatList()
    {
        $date_type = isset($this->params[ 'date_type' ]) ? $this->params[ 'date_type' ] : 1;

        if ($date_type == 1) {
            $start_time = strtotime(date('Y-m-d', strtotime("-6 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
            $day = 6;
        } else if ($date_type == 2) {
            $start_time = strtotime(date('Y-m-d', strtotime("-29 day")));
            $time_range = date('Y-m-d', $start_time) . ' 至 ' . date('Y-m-d', strtotime("today"));
            $day = 29;
        }

        $stat_model = new StatModel();

        $stat_list = $stat_model->getShopStatList($this->site_id, $start_time);

        //将时间戳作为列表的主键
        $shop_stat_list = array_column($stat_list[ 'data' ], null, 'day_time');

        $data = array ();

        for ($i = 0; $i <= $day; $i++) {
            $time = strtotime(date('Y-m-d', strtotime("-" . ( $day - $i ) . " day")));
            $data[ 'time' ][ $i ] = date('Y-m-d', $time);
            if (array_key_exists($time, $shop_stat_list)) {
                $data[ 'order_total' ][ $i ] = $shop_stat_list[ $time ][ 'order_total' ];
                $data[ 'shipping_total' ][ $i ] = $shop_stat_list[ $time ][ 'shipping_total' ];
                $data[ 'refund_total' ][ $i ] = $shop_stat_list[ $time ][ 'refund_total' ];
                $data[ 'order_pay_count' ][ $i ] = $shop_stat_list[ $time ][ 'order_pay_count' ];
                $data[ 'goods_pay_count' ][ $i ] = $shop_stat_list[ $time ][ 'goods_pay_count' ];
                $data[ 'shop_money' ][ $i ] = $shop_stat_list[ $time ][ 'shop_money' ];
                $data[ 'platform_money' ][ $i ] = $shop_stat_list[ $time ][ 'platform_money' ];
                $data[ 'collect_shop' ][ $i ] = $shop_stat_list[ $time ][ 'collect_shop' ];
                $data[ 'collect_goods' ][ $i ] = $shop_stat_list[ $time ][ 'collect_goods' ];
                $data[ 'visit_count' ][ $i ] = $shop_stat_list[ $time ][ 'visit_count' ];
                $data[ 'order_count' ][ $i ] = $shop_stat_list[ $time ][ 'order_count' ];
                $data[ 'goods_count' ][ $i ] = $shop_stat_list[ $time ][ 'goods_count' ];
                $data[ 'add_goods_count' ][ $i ] = $shop_stat_list[ $time ][ 'add_goods_count' ];
            } else {
                $data[ 'order_total' ][ $i ] = 0.00;
                $data[ 'shipping_total' ][ $i ] = 0.00;
                $data[ 'refund_total' ][ $i ] = 0.00;
                $data[ 'order_pay_count' ][ $i ] = 0;
                $data[ 'goods_pay_count' ][ $i ] = 0;
                $data[ 'shop_money' ][ $i ] = 0.00;
                $data[ 'platform_money' ][ $i ] = 0.00;
                $data[ 'collect_shop' ][ $i ] = 0;
                $data[ 'collect_goods' ][ $i ] = 0;
                $data[ 'visit_count' ][ $i ] = 0;
                $data[ 'order_count' ][ $i ] = 0;
                $data[ 'goods_count' ][ $i ] = 0;
                $data[ 'add_goods_count' ][ $i ] = 0;
            }
        }
        $data[ 'time_range' ] = $time_range;
        return $this->response($this->success($data));
    }
}