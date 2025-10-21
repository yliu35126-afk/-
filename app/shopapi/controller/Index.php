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

use app\model\shop\Shop as ShopModel;
use app\model\shop\ShopReopen as ShopReopenModel;
use app\model\system\Stat;
use app\model\web\Notice as NoticeModel;
use Carbon\Carbon;
use app\model\web\WebSite as WebsiteModel;
use app\model\goods\Goods as GoodsModel;
use app\model\system\User as ShopUser;
use app\model\order\OrderCommon;
use app\model\order\OrderRefund as OrderRefundModel;

class Index extends BaseApi
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
     * 首页
     * @return mixed
     */
    public function index()
    {
        //店铺基础信息
        $shop_model = new ShopModel();
        $shop_info = $shop_model->getShopInfo([ [ 'site_id', '=', $this->site_id ] ], 'site_id, expire_time, site_name,cert_id, is_own, group_name,shop_status, logo,category_name');
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
        $data[ 'shop_info' ] = $shop_info;

        //判断是否有续签
        $reopen_model = new ShopReopenModel();
        $reopen_info = $reopen_model->getReopenInfo([ [ 'sr.site_id', '=', $this->site_id ], [ 'sr.apply_state', 'in', [ 1, -1 ] ] ]);
        if (empty($reopen_info[ 'data' ])) {
            $is_reopen = 1;
        } else {
            $is_reopen = 2;
        }
        $data[ 'is_reopen' ] = $is_reopen;

        //基础统计信息
        $stat_shop_model = new Stat();
        $today = Carbon::now();
        $yesterday = Carbon::yesterday();
        $stat_today = $stat_shop_model->getStatShop($this->site_id, $today->year, $today->month, $today->day);
        $stat_yesterday = $stat_shop_model->getStatShop($this->site_id, $yesterday->year, $yesterday->month, $yesterday->day);

        $data[ 'stat_day' ] = $stat_today[ 'data' ];
        $data[ 'stat_yesterday' ] = $stat_yesterday[ 'data' ];
//        $data[ 'today' ] = $today;

        //日同比
        $day_rate[ 'order_pay_count' ] = diff_rate($stat_today[ 'data' ][ 'order_pay_count' ], $stat_yesterday[ 'data' ][ 'order_pay_count' ]);
        $day_rate[ 'order_total' ] = diff_rate($stat_today[ 'data' ][ 'order_total' ], $stat_yesterday[ 'data' ][ 'order_total' ]);
        $day_rate[ 'collect_goods' ] = diff_rate($stat_today[ 'data' ][ 'collect_goods' ], $stat_yesterday[ 'data' ][ 'collect_goods' ]);
        $day_rate[ 'visit_count' ] = diff_rate($stat_today[ 'data' ][ 'visit_count' ], $stat_yesterday[ 'data' ][ 'visit_count' ]);
        $day_rate[ 'member_count' ] = diff_rate($stat_today[ 'data' ][ 'member_count' ], $stat_yesterday[ 'data' ][ 'member_count' ]);
        $data[ 'day_rate' ] = $day_rate;

        //获取总数
        $shop_stat_sum = $stat_shop_model->getShopStatSum($this->site_id);
        $goods_model = new GoodsModel();
        $goods_sum = $goods_model->getGoodsTotalCount([ 'site_id' => $this->site_id ]);
        $shop_stat_sum[ 'data' ][ 'goods_count' ] = $goods_sum[ 'data' ];
        $data[ 'shop_stat_sum' ] = $shop_stat_sum[ 'data' ];

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
//        $goods_stock_alarm = $goods_model->getGoodsStockAlarm($this->site_id);

        //商品总数
//        $goods_total = $goods_model->getGoodsTotalCount([ [ 'goods_state', '=', 1 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);

        //待审核数量
        $wait_audit_count = $goods_model->getGoodsTotalCount([ [ 'verify_state', '=', 0 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);

        //违规下架商品数
        $audit_refuse_count = $goods_model->getGoodsTotalCount([ [ 'verify_state', '=', 10 ], [ 'site_id', '=', $this->site_id ], [ 'is_delete', '=', 0 ] ]);

        $num_data = [
            'waitpay' => $waitpay[ 'data' ],
            'waitsend' => $waitsend[ 'data' ],
            'refund' => $refund_num[ 'data' ],
//            'goods_stock_alarm' => $goods_stock_alarm[ 'data' ],
//            'goods_total' => $goods_total[ 'data' ],
            'audit_refuse_count' => $audit_refuse_count[ 'data' ],
            'wait_audit_count' => $wait_audit_count[ 'data' ]
        ];
        $data[ 'num_data' ] = $num_data;

        $notice = new NoticeModel();
        $notice_list = $notice->getNoticePageList([ [ 'receiving_type', 'like', '%shop%' ] ], 1, 3, 'is_top desc,create_time desc', 'id, title');
        $notice_list = $notice_list[ 'data' ][ 'list' ];
        $data[ 'notice_list' ] = $notice_list;

        //平台配置信息
//        $website_model = new WebsiteModel();
//        $website_info = $website_model->getWebSite([ [ 'site_id', '=', 0 ] ], 'web_qrcode,web_phone');
//        $data[ 'website_info' ] = $website_info[ 'data' ];

        //会员基础信息
//        $user_model = new ShopUser();
//        $user_info = $user_model->getUserInfo([ [ 'uid', '=', $this->uid ] ], 'username,group_name,login_time');
//        $data[ 'shop_user_info' ] = $user_info[ 'data' ];
        return $this->response($this->success($data));
    }

}