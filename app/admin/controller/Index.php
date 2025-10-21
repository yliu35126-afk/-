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

namespace app\admin\controller;

use addon\fenxiao\model\FenxiaoApply;
use addon\fenxiao\model\FenxiaoWithdraw;
use app\model\goods\Goods as GoodsModel;
use app\model\order\Complain as ComplainModel;
use app\model\order\Order as OrderModel;
use app\model\order\OrderCommon;
use app\model\order\OrderRefund as OrderRefundModel;
use app\model\shop\Shop as ShopModel;
use app\model\shop\ShopApply;
use app\model\system\Config;
use app\model\system\Stat as StatModel;
use app\model\system\Upgrade as UpgradeModel;
use app\model\system\User as UserModel;
use app\model\system\Web;
use Carbon\Carbon;

/**
 * 首页 控制器
 */
class Index extends BaseAdmin
{

    /**
     * 首页
     */
    public function index()
    {
        $config_model = new Config();
        $stat_model = new StatModel();
        $goods_model = new GoodsModel();
        $carbon = Carbon::now();

        $stat_info = $stat_model->getStatShop(0, $carbon->year, $carbon->month, $carbon->day);
        $this->assign('stat_info', $stat_info[ 'data' ]);


        //基础统计信息
        $today = Carbon::now();
        $yesterday = Carbon::yesterday();
        $stat_today = $stat_model->getStatShop(0, $today->year, $today->month, $today->day);
        $stat_yesterday = $stat_model->getStatShop(0, $yesterday->year, $yesterday->month, $yesterday->day);
        $this->assign("stat_day", $stat_today[ 'data' ]);
        $this->assign("stat_yesterday", $stat_yesterday[ 'data' ]);
        $this->assign("today", $today);
        //日同比
        $day_rate[ 'order_pay_count' ] = diff_rate($stat_today[ 'data' ][ 'order_pay_count' ], $stat_yesterday[ 'data' ][ 'order_pay_count' ]);
        $day_rate[ 'order_total' ] = diff_rate($stat_today[ 'data' ][ 'order_total' ], $stat_yesterday[ 'data' ][ 'order_total' ]);
        $day_rate[ 'member_count' ] = diff_rate($stat_today[ 'data' ][ 'member_count' ], $stat_yesterday[ 'data' ][ 'member_count' ]);

        //近十天的订单数以及销售金额
        $date_day = getweeks();
        $order_total = '';
        $order_pay_count = '';
        foreach ($date_day as $k => $day) {
            $dayarr = explode('-', $day);
            $stat_day[ $k ] = $stat_model->getStatShop(0, $dayarr[ 0 ], $dayarr[ 1 ], $dayarr[ 2 ]);
            $order_total .= $stat_day[ $k ][ 'data' ][ 'order_total' ] . ',';
            $order_pay_count .= $stat_day[ $k ][ 'data' ][ 'order_pay_count' ] . ',';
        }
        $ten_day[ 'order_total' ] = explode(',', substr($order_total, 0, strlen($order_total) - 1));
        $ten_day[ 'order_pay_count' ] = explode(',', substr($order_pay_count, 0, strlen($order_pay_count) - 1));
        $this->assign('ten_day', $ten_day);

        //数据信息统计
        $order = new OrderCommon();
        $waitpay = $order->getOrderCount([ [ 'order_status', '=', 0 ], [ 'is_delete', '=', 0 ] ]);
        $waitsend = $order->getOrderCount([ [ 'order_status', '=', 1 ], [ 'is_delete', '=', 0 ] ]);
        $order_refund_model = new OrderRefundModel();
        $refund_num = $order_refund_model->getRefundOrderGoodsCount([
            [ "refund_status", "not in", [ 0, 3 ] ]
        ]);

        $goods_total = $goods_model->getGoodsTotalCount([ [ 'goods_state', '=', 1 ], [ 'is_delete', '=', 0 ] ]);
        $warehouse_goods = $goods_model->getGoodsTotalCount([ [ 'goods_state', '=', 0 ], [ 'is_delete', '=', 0 ] ]);

        $num_data = [
            'waitpay' => $waitpay[ 'data' ],
            'waitsend' => $waitsend[ 'data' ],
            'refund' => $refund_num[ 'data' ],
            'goods_total' => $goods_total[ 'data' ],
            'warehouse_goods' => $warehouse_goods[ 'data' ]
        ];

        //分销插件是否存在
        $is_fenxiao = addon_is_exit('fenxiao');
        $this->assign('is_fenxiao', $is_fenxiao);
        if ($is_fenxiao) {
            //提现待审核
            $fenxiao_model = new FenxiaoWithdraw();
            $withdraw_count = $fenxiao_model->getFenxiaoWithdrawCount([ [ 'status', '=', 1 ] ], 'id');
            $num_data[ 'withdraw_count' ] = $withdraw_count[ 'data' ];

            //分销商申请
            $fenxiao_apply_model = new FenxiaoApply();
            $fenxiao_count = $fenxiao_apply_model->getFenxiaoApplyCount([ [ 'status', '=', 1 ] ], 'apply_id');
            $num_data[ 'apply_count' ] = $fenxiao_count[ 'data' ];
        } else {
            $waitconfirm = $order->getOrderCount([ [ 'order_status', "=", 3 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);
            $complete = $order->getOrderCount([ [ 'order_status', "=", 10 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);
            $num_data[ 'waitconfirm' ] = $waitconfirm[ 'data' ];
            $num_data[ 'complete' ] = $complete[ 'data' ];
        }

        //维权订单
        $complain_model = new ComplainModel();
        $complain_count = $complain_model->getComplainCount([ [ 'complain_status', '=', 1 ] ]);
        $num_data[ 'complain_count' ] = $complain_count[ 'data' ];

        $this->assign('num_data', $num_data);

        //订单总额
        $order_model = new OrderModel();
        $order_money = $order_model->getOrderMoneySum();
        $this->assign('order_money', $order_money[ 'data' ]);

        //会员总数
        $user_model = new UserModel();
        $member_count = $user_model->getMemberTotalCount();
        $this->assign('member_count', $member_count[ 'data' ]);

        //新增店铺数
        $shop_apply_model = new ShopApply();
        $today_apply_count = $shop_apply_model->getShopApplyCount([ [ 'create_time', 'between', [ date_to_time(date('Y-m-d 00:00:00')), date_to_time(date('Y-m-d 23:59:59')) ] ] ]);
        $yesterday_apply_count = $shop_apply_model->getShopApplyCount([ [ 'create_time', 'between', [ date_to_time(date('Y-m-' . $yesterday->day . ' 00:00:00')), date_to_time(date('Y-m-' . $yesterday->day . ' 23:59:59')) ] ] ]);
        $this->assign('today_apply_count', $today_apply_count[ 'data' ]);
        $this->assign('yesterday_apply_count', $yesterday_apply_count[ 'data' ]);

        $day_rate[ 'shop_count' ] = diff_rate($today_apply_count[ 'data' ], $yesterday_apply_count[ 'data' ]);
        $this->assign('day_rate', $day_rate);

        //今日申请店铺数
        $shop_model = new ShopModel();
        $shop_count = $shop_model->getShopCount([ [ 'create_time', 'between', [ date_to_time(date('Y-m-d 00:00:00')), date_to_time(date('Y-m-d 23:59:59')) ] ] ]);
        $this->assign('shop_count', $shop_count[ 'data' ]);

        //店铺总数
        $shop_total_count = $shop_model->getShopCount();
        $this->assign('shop_total_count', $shop_total_count[ 'data' ]);

        //商品总数
        $goods_count = $goods_model->getGoodsTotalCount();
        $this->assign('goods_count', $goods_count[ 'data' ]);

        //获取最新版本
        $upgrade_model = new UpgradeModel();
        $last_version = $upgrade_model->getLatestVersion();
        $last_release = $last_version[ 'data' ][ 'version_no' ] ?? 0;
        $need_upgrade = 0;
        $app_info = config('info');
        if ($app_info[ 'version_no' ] < $last_release) {
            $need_upgrade = 1;
        }

        $system_config = $config_model->getSystemConfig();
        $this->assign('need_upgrade', $need_upgrade);
        $this->assign('sys_product_name', $app_info[ 'name' ]);
        $this->assign('sys_version_no', $app_info[ 'version_no' ]);
        $this->assign('sys_version_name', $app_info[ 'version' ]);
        $this->assign('system_config', $system_config[ 'data' ]);

        $this->assign('system_config', $system_config[ 'data' ]);

        //待处理的事物
        //$this->todo();
        return $this->fetch('index/index');

    }

    /**
     * 待处理项
     */
    public function todo()
    {
        //待审核商品
        $goods_model = new \app\model\goods\Goods();
        $goods_count_result = $goods_model->getGoodsTotalCount([ [ "verify_state", "=", 0 ], [ "is_delete", "=", 0 ] ]);
        $this->assign("verify_goods_count", $goods_count_result[ "data" ]);
        //举报商品
        $inform_model = new \app\model\goods\Inform();
        $inform_count_result = $inform_model->getInformCount([ [ "state", "=", 0 ] ]);
        $this->assign("inform_count", $inform_count_result[ "data" ]);

        //查询平台维权数量
        $complain_model = new \app\model\order\Complain();
        $complain_count_result = $complain_model->getComplainCount([ [ "complain_status", "=", 1 ] ]);
        $this->assign("complain_count_result", $complain_count_result[ "data" ]);

        //店铺入驻申请
        $shop_apply_model = new \app\model\shop\ShopApply();
        $shop_apply_count_result = $shop_apply_model->getShopApplyCount([ [ "apply_state", "in", [ 1, 2 ] ] ]);
        $this->assign("shop_apply_count", $shop_apply_count_result[ "data" ]);
        //店铺续签申请

        $shop_reopen_model = new \app\model\shop\ShopReopen();
        $shop_reopen_count_result = $shop_reopen_model->getApplyReopenCount([ [ "apply_state", "=", 1 ] ]);
        $this->assign("shop_reopen_count", $shop_reopen_count_result[ "data" ]);

    }

    /**
     * 官网资讯
     */
    public function news()
    {
        $web_model = new Web();
        $result = $web_model->news();
        return $result;
    }


}
