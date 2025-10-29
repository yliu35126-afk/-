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
use think\facade\Cache;

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
        $ttl = 60; // 后台首页短缓存窗口（秒）

        // 基础统计：统一空值兼容
        $stat_defaults = ['order_pay_count' => 0, 'order_total' => 0, 'member_count' => 0];

        $stat_info = Cache::remember(
            "admin:stat_shop:{$carbon->year}-{$carbon->month}-{$carbon->day}",
            function () use ($stat_model, $carbon) {
                return $stat_model->getStatShop(0, $carbon->year, $carbon->month, $carbon->day);
            },
            $ttl
        );
        $stat_info_data = is_array($stat_info) ? ($stat_info['data'] ?? []) : [];
        $stat_info_data = array_merge($stat_defaults, (array)$stat_info_data);
        $this->assign('stat_info', $stat_info_data);

        //基础统计信息
        $today = Carbon::now();
        $yesterday = Carbon::yesterday();
        $stat_today = Cache::remember(
            "admin:stat_shop:{$today->year}-{$today->month}-{$today->day}",
            function () use ($stat_model, $today) {
                return $stat_model->getStatShop(0, $today->year, $today->month, $today->day);
            },
            $ttl
        );
        $stat_yesterday = Cache::remember(
            "admin:stat_shop:{$yesterday->year}-{$yesterday->month}-{$yesterday->day}",
            function () use ($stat_model, $yesterday) {
                return $stat_model->getStatShop(0, $yesterday->year, $yesterday->month, $yesterday->day);
            },
            $ttl
        );

        $stat_today_data = is_array($stat_today) ? ($stat_today['data'] ?? []) : [];
        $stat_yesterday_data = is_array($stat_yesterday) ? ($stat_yesterday['data'] ?? []) : [];
        $stat_today_data = array_merge($stat_defaults, (array)$stat_today_data);
        $stat_yesterday_data = array_merge($stat_defaults, (array)$stat_yesterday_data);

        $this->assign("stat_day", $stat_today_data);
        $this->assign("stat_yesterday", $stat_yesterday_data);
        $this->assign("today", $today);
        //日同比
        $day_rate['order_pay_count'] = diff_rate($stat_today_data['order_pay_count'], $stat_yesterday_data['order_pay_count']);
        $day_rate['order_total'] = diff_rate($stat_today_data['order_total'], $stat_yesterday_data['order_total']);
        $day_rate['member_count'] = diff_rate($stat_today_data['member_count'], $stat_yesterday_data['member_count']);

        //近十天的订单数以及销售金额
        $date_day = getweeks();
        $order_total = '';
        $order_pay_count = '';
        $stat_day = [];
        foreach ($date_day as $k => $day) {
            $dayarr = explode('-', $day);
            $res = Cache::remember(
                "admin:stat_shop:{$dayarr[0]}-{$dayarr[1]}-{$dayarr[2]}",
                function () use ($stat_model, $dayarr) {
                    return $stat_model->getStatShop(0, $dayarr[0], $dayarr[1], $dayarr[2]);
                },
                $ttl
            );
            $data = is_array($res) ? ($res['data'] ?? []) : [];
            $data = array_merge(['order_total' => 0, 'order_pay_count' => 0], (array)$data);
            $stat_day[$k] = $res;
            $order_total .= $data['order_total'] . ',';
            $order_pay_count .= $data['order_pay_count'] . ',';
        }
        $ten_day['order_total'] = explode(',', substr($order_total, 0, strlen($order_total) - 1));
        $ten_day['order_pay_count'] = explode(',', substr($order_pay_count, 0, strlen($order_pay_count) - 1));
        $this->assign('ten_day', $ten_day);

        //数据信息统计（短缓存）
        $order = new OrderCommon();
        $waitpay = Cache::remember(
            'admin:count:waitpay',
            function () use ($order) { return $order->getOrderCount([[ 'order_status', '=', 0 ], [ 'is_delete', '=', 0 ]]); },
            $ttl
        );
        $waitsend = Cache::remember(
            'admin:count:waitsend',
            function () use ($order) { return $order->getOrderCount([[ 'order_status', '=', 1 ], [ 'is_delete', '=', 0 ]]); },
            $ttl
        );
        $order_refund_model = new OrderRefundModel();
        $refund_num = Cache::remember(
            'admin:count:refund_num',
            function () use ($order_refund_model) { return $order_refund_model->getRefundOrderGoodsCount([[ "refund_status", "not in", [ 0, 3 ] ]]); },
            $ttl
        );

        $goods_total = Cache::remember(
            'admin:count:goods_total',
            function () use ($goods_model) { return $goods_model->getGoodsTotalCount([[ 'goods_state', '=', 1 ], [ 'is_delete', '=', 0 ]]); },
            $ttl
        );
        $warehouse_goods = Cache::remember(
            'admin:count:warehouse_goods',
            function () use ($goods_model) { return $goods_model->getGoodsTotalCount([[ 'goods_state', '=', 0 ], [ 'is_delete', '=', 0 ]]); },
            $ttl
        );

        $num_data = [
            'waitpay' => (is_array($waitpay) ? ($waitpay['data'] ?? 0) : 0),
            'waitsend' => (is_array($waitsend) ? ($waitsend['data'] ?? 0) : 0),
            'refund' => (is_array($refund_num) ? ($refund_num['data'] ?? 0) : 0),
            'goods_total' => (is_array($goods_total) ? ($goods_total['data'] ?? 0) : 0),
            'warehouse_goods' => (is_array($warehouse_goods) ? ($warehouse_goods['data'] ?? 0) : 0)
        ];

        //分销插件是否存在（相关统计短缓存）
        $is_fenxiao = addon_is_exit('fenxiao');
        $this->assign('is_fenxiao', $is_fenxiao);
        if ($is_fenxiao) {
            $fenxiao_model = new FenxiaoWithdraw();
            $withdraw_count = Cache::remember(
                'admin:fenxiao:withdraw_count',
                function () use ($fenxiao_model) { return $fenxiao_model->getFenxiaoWithdrawCount([[ 'status', '=', 1 ]], 'id'); },
                $ttl
            );
            $num_data['withdraw_count'] = (is_array($withdraw_count) ? ($withdraw_count['data'] ?? 0) : 0);

            $fenxiao_apply_model = new FenxiaoApply();
            $fenxiao_count = Cache::remember(
                'admin:fenxiao:apply_count',
                function () use ($fenxiao_apply_model) { return $fenxiao_apply_model->getFenxiaoApplyCount([[ 'status', '=', 1 ]], 'apply_id'); },
                $ttl
            );
            $num_data['apply_count'] = (is_array($fenxiao_count) ? ($fenxiao_count['data'] ?? 0) : 0);
        } else {
            $waitconfirm = Cache::remember(
                'admin:order:waitconfirm',
                function () use ($order) { return $order->getOrderCount([[ 'order_status', "=", 3 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ]]); },
                $ttl
            );
            $complete = Cache::remember(
                'admin:order:complete',
                function () use ($order) { return $order->getOrderCount([[ 'order_status', "=", 10 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ]]); },
                $ttl
            );
            $num_data['waitconfirm'] = (is_array($waitconfirm) ? ($waitconfirm['data'] ?? 0) : 0);
            $num_data['complete'] = (is_array($complete) ? ($complete['data'] ?? 0) : 0);
        }

        //维权订单（短缓存）
        $complain_model = new ComplainModel();
        $complain_count = Cache::remember(
            'admin:order:complain_count',
            function () use ($complain_model) { return $complain_model->getComplainCount([[ 'complain_status', '=', 1 ]]); },
            $ttl
        );
        $num_data['complain_count'] = (is_array($complain_count) ? ($complain_count['data'] ?? 0) : 0);

        $this->assign('num_data', $num_data);

        //订单总额（短缓存）
        $order_model = new OrderModel();
        $order_money = Cache::remember(
            'admin:order:money_sum',
            function () use ($order_model) { return $order_model->getOrderMoneySum(); },
            $ttl
        );
        $this->assign('order_money', (is_array($order_money) ? ($order_money['data'] ?? 0) : 0));

        //会员总数（短缓存）
        $user_model = new UserModel();
        $member_count = Cache::remember(
            'admin:user:member_total',
            function () use ($user_model) { return $user_model->getMemberTotalCount(); },
            $ttl
        );
        $this->assign('member_count', (is_array($member_count) ? ($member_count['data'] ?? 0) : 0));

        //新增店铺数（短缓存）
        $shop_apply_model = new ShopApply();
        $today_apply_count = Cache::remember(
            'admin:shop:apply_today',
            function () use ($shop_apply_model, $yesterday) { return $shop_apply_model->getShopApplyCount([[ 'create_time', 'between', [ date_to_time(date('Y-m-d 00:00:00')), date_to_time(date('Y-m-d 23:59:59')) ] ]]); },
            $ttl
        );
        $yesterday_apply_count = Cache::remember(
            'admin:shop:apply_yesterday',
            function () use ($shop_apply_model, $yesterday) { return $shop_apply_model->getShopApplyCount([[ 'create_time', 'between', [ date_to_time(date('Y-m-' . $yesterday->day . ' 00:00:00')), date_to_time(date('Y-m-' . $yesterday->day . ' 23:59:59')) ] ]]); },
            $ttl
        );
        $this->assign('today_apply_count', (is_array($today_apply_count) ? ($today_apply_count['data'] ?? 0) : 0));
        $this->assign('yesterday_apply_count', (is_array($yesterday_apply_count) ? ($yesterday_apply_count['data'] ?? 0) : 0));
        $day_rate['shop_count'] = diff_rate((is_array($today_apply_count) ? ($today_apply_count['data'] ?? 0) : 0), (is_array($yesterday_apply_count) ? ($yesterday_apply_count['data'] ?? 0) : 0));
        $this->assign('day_rate', $day_rate);

        //今日申请店铺数（短缓存）
        $shop_model = new ShopModel();
        $shop_count = Cache::remember(
            'admin:shop:count_today',
            function () use ($shop_model) { return $shop_model->getShopCount([[ 'create_time', 'between', [ date_to_time(date('Y-m-d 00:00:00')), date_to_time(date('Y-m-d 23:59:59')) ] ]]); },
            $ttl
        );
        $this->assign('shop_count', (is_array($shop_count) ? ($shop_count['data'] ?? 0) : 0));

        //店铺总数（短缓存）
        $shop_total_count = Cache::remember(
            'admin:shop:count_total',
            function () use ($shop_model) { return $shop_model->getShopCount(); },
            $ttl
        );
        $this->assign('shop_total_count', (is_array($shop_total_count) ? ($shop_total_count['data'] ?? 0) : 0));

        //商品总数（短缓存）
        $goods_count = Cache::remember(
            'admin:goods:count_total',
            function () use ($goods_model) { return $goods_model->getGoodsTotalCount(); },
            $ttl
        );
        $this->assign('goods_count', (is_array($goods_count) ? ($goods_count['data'] ?? 0) : 0));

        //获取最新版本（空值兼容）
        $upgrade_model = new UpgradeModel();
        $last_version = $upgrade_model->getLatestVersion();
        $last_version_data = is_array($last_version) ? ($last_version['data'] ?? []) : [];
        $last_release = is_array($last_version_data) ? ($last_version_data['version_no'] ?? 0) : 0;

        $need_upgrade = 0;
        $app_info = config('info');
        $current_version_no = is_array($app_info) ? ($app_info['version_no'] ?? 0) : 0;
        if ($current_version_no < $last_release) {
            $need_upgrade = 1;
        }

        $system_config = $config_model->getSystemConfig();
        $this->assign('need_upgrade', $need_upgrade);
        $this->assign('sys_product_name', (is_array($app_info) ? ($app_info['name'] ?? 'B2B2C') : 'B2B2C'));
        $this->assign('sys_version_no', $current_version_no);
        $this->assign('sys_version_name', (is_array($app_info) ? ($app_info['version'] ?? '') : ''));
        $this->assign('system_config', is_array($system_config) ? ($system_config['data'] ?? []) : []);

        //待处理的事物
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
        return json($result);
    }


}
