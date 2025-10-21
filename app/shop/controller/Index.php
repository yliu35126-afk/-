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

namespace app\shop\controller;


use app\model\goods\Goods as GoodsModel;
use app\model\order\OrderCommon;
use app\model\order\OrderRefund as OrderRefundModel;
use app\model\shop\Config as ConfigModel;
use app\model\shop\Shop;
use app\model\shop\ShopGroup as ShopGroupModel;
use app\model\shop\ShopReopen as ShopReopenModel;
use app\model\system\Promotion as PromotionModel;
use app\model\system\Stat;
use app\model\system\User as ShopUser;
use app\model\web\Help as HelpModel;
use app\model\web\Notice as NoticeModel;
use Carbon\Carbon;

class Index extends BaseShop
{

    /**
     * 首页
     * @return mixed
     */
    public function index()
    {
        //店铺基础信息
        $shop_model = new Shop();
        $shop_info = $shop_model->getShopInfo([ [ 'site_id', '=', $this->site_id ] ], 'site_id, expire_time, site_name, username, website_id, 
	        cert_id, is_own, level_id, level_name, category_id, category_name, group_id, group_name, member_id, member_name,
	        shop_status, close_info, sort, start_time, end_time, logo, avatar, banner, seo_keywords, seo_description, qq, ww, 
	        telephone, is_recommend, shop_desccredit, shop_servicecredit, shop_deliverycredit, workingtime, shop_baozh,
	        shop_baozhopen, shop_baozhrmb, shop_qtian, shop_zhping, shop_erxiaoshi, shop_tuihuo, shop_shiyong, shop_shiti, 
	        shop_xiaoxie, shop_free_time, shop_sales, shop_adv, account, account_withdraw, work_week, province, province_name, 
	        city, city_name, district, district_name, community, community_name, address, full_address, longitude, latitude, 
	        sub_num');

        $shop_info = $shop_info[ 'data' ];
        if ($shop_info[ 'expire_time' ] == 0) {
            $shop_info[ 'is_reopen' ] = 1;//永久有效
        } elseif ($shop_info[ 'expire_time' ] > time()) {
            $cha = $shop_info[ 'expire_time' ] - time();
            $date = ceil(( $cha / 86400 ));
            if ($date < 30) {
                $shop_info[ 'is_reopen' ] = 2;//离到期一月内才可以申请续签
                $shop_info[ 'expires_date' ] = (int) $date;
            }
        } else {
            $shop_info[ 'is_reopen' ] = 3;
            $shop_info[ 'expires_date' ] = 0;
        }
        $this->assign("shop", $shop_info);

        //判断是否有续签
        $reopen_model = new ShopReopenModel();
        $reopen_info = $reopen_model->getReopenInfo([ [ 'sr.site_id', '=', $this->site_id ], [ 'sr.apply_state', 'in', [ 1, -1 ] ] ]);
        if (empty($reopen_info[ 'data' ])) {
            $is_reopen = 1;
        } else {
            $is_reopen = 2;
        }
        $this->assign('is_reopen', $is_reopen);


        //会员基础信息
        $user_model = new ShopUser();
        $user_info = $user_model->getUserInfo([ [ 'uid', '=', $this->uid ] ], 'username,group_name,login_time');
        $this->assign("shop_user_info", $user_info[ 'data' ]);

        //基础统计信息
        $stat_shop_model = new Stat();
        $today = Carbon::now();
        $yesterday = Carbon::yesterday();
        $stat_today = $stat_shop_model->getStatShop($this->site_id, $today->year, $today->month, $today->day);
        $stat_yesterday = $stat_shop_model->getStatShop($this->site_id, $yesterday->year, $yesterday->month, $yesterday->day);
        $this->assign("stat_day", $stat_today[ 'data' ]);
        $this->assign("stat_yesterday", $stat_yesterday[ 'data' ]);
        $this->assign("today", $today);

        //日同比
        $day_rate[ 'order_pay_count' ] = diff_rate($stat_today[ 'data' ][ 'order_pay_count' ], $stat_yesterday[ 'data' ][ 'order_pay_count' ]);
        $day_rate[ 'order_total' ] = diff_rate($stat_today[ 'data' ][ 'order_total' ], $stat_yesterday[ 'data' ][ 'order_total' ]);
        $day_rate[ 'collect_goods' ] = diff_rate($stat_today[ 'data' ][ 'collect_goods' ], $stat_yesterday[ 'data' ][ 'collect_goods' ]);
        $day_rate[ 'visit_count' ] = diff_rate($stat_today[ 'data' ][ 'visit_count' ], $stat_yesterday[ 'data' ][ 'visit_count' ]);
        $day_rate[ 'collect_shop' ] = diff_rate($stat_today[ 'data' ][ 'collect_shop' ], $stat_yesterday[ 'data' ][ 'collect_shop' ]);
        $this->assign('day_rate', $day_rate);

        //近十天的订单数以及销售金额
        $date_day = getweeks();
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
        $this->assign('ten_day', $ten_day);

        //获取总数
        $shop_stat_sum = $stat_shop_model->getShopStatSum($this->site_id);
        $goods_model = new GoodsModel();
        $goods_sum = $goods_model->getGoodsTotalCount([ 'site_id' => $this->site_id ]);
        $shop_stat_sum[ 'data' ][ 'goods_count' ] = $goods_sum[ 'data' ];
        $this->assign('shop_stat_sum', $shop_stat_sum[ 'data' ]);

        //营销活动
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getPromotions();
        $shop_group_model = new ShopGroupModel();
        $addon_array = $shop_group_model->getGroupInfo([ 'group_id' => $this->shop_info[ 'group_id' ] ], 'addon_array');
        $addon_array = explode(',', $addon_array[ 'data' ][ 'addon_array' ]);

        $toolcount = 0;
        $shopcount = 0;
        foreach ($promotions[ 'shop' ] as $key => $promotion) {
            if (!empty($promotion[ 'is_developing' ])) {
                unset($promotions[ 'shop' ][ $key ]);
                continue;
            }
            if (!in_array($promotion[ 'name' ], $addon_array)) {
                unset($promotions[ 'shop' ][ $key ]);
            } else {
                if ($promotion[ "show_type" ] == 'tool') {
                    $toolcount += 1;
                }
                if ($promotion[ "show_type" ] == 'member' || $promotion[ "show_type" ] == 'shop') {
                    $shopcount += 1;
                }
            }
        }
        $this->assign("promotion", $promotions[ 'shop' ]);
        $count = [
            'toolcount' => $toolcount,
            'shopcount' => $shopcount
        ];
        $this->assign("count", $count);

        //数据信息统计
        $order = new OrderCommon();
        $waitpay = $order->getOrderCount([ [ 'order_status', '=', 0 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);
        $waitsend = $order->getOrderCount([ [ 'order_status', '=', 1 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);
        $order_refund_model = new OrderRefundModel();
        $refund_num = $order_refund_model->getRefundOrderGoodsCount([
            [ "site_id", "=", $this->site_id ],
            [ "refund_status", "not in", [ 0, 3 ] ]
        ]);

        //商品预警数
        $goods_stock_alarm = $goods_model->getGoodsStockAlarm($this->site_id);
        //商品总数
        $goods_total = $goods_model->getGoodsTotalCount([ [ 'goods_state', '=', 1 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);
        //待审核数量
        $wait_audit_count = $goods_model->getGoodsTotalCount([ [ 'verify_state', '=', 0 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);
        //违规下架商品数
        $audit_refuse_count = $goods_model->getGoodsTotalCount([ [ 'verify_state', '=', 10 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);

        $num_data = [
            'waitpay' => $waitpay[ 'data' ],
            'waitsend' => $waitsend[ 'data' ],
            'refund' => $refund_num[ 'data' ],
            'goods_stock_alarm' => $goods_stock_alarm[ 'data' ],
            'goods_total' => $goods_total[ 'data' ],
            'audit_refuse_count' => $audit_refuse_count[ 'data' ],
            'wait_audit_count' => $wait_audit_count[ 'data' ]
        ];
        $this->assign('num_data', $num_data);

        //入驻指南
        $config_model = new ConfigModel();
        $shop_join_guide_list = $config_model->getShopJoinGuide();
        $this->assign("shop_join_guide_list", $shop_join_guide_list[ 'data' ]);

        //入驻帮助
        $help_model = new HelpModel();
        $help_list = $help_model->getHelpPageList([ [ 'app_module', '=', 'shop' ] ], 1, 5, 'create_time desc');
        $this->assign("help_list", $help_list[ 'data' ][ 'list' ]);

        //店铺等级
        $shop_group_model = new ShopGroupModel();
        $shop_group_list = $shop_group_model->getGroupList([ [ 'is_own', '=', 0 ] ], 'group_id,is_own,group_name,fee,remark', 'is_own asc,fee asc');
        $this->assign('shop_group_list', $shop_group_list[ 'data' ]);

        //网站公告
        $notice_model = new NoticeModel();
        $notice_list = $notice_model->getNoticePageList([ [ 'receiving_type', 'like', '%shop%' ] ], 1, 5, 'is_top desc,create_time desc', 'id,title');
        $this->assign('notice_list', $notice_list[ 'data' ][ 'list' ]);

        return $this->fetch("index/index");
    }

}