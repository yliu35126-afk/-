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

namespace addon\supply\supply\controller;

use addon\supply\model\goods\Goods as GoodsModel;
use addon\supply\model\SupplyReopen as SupplyReopenModel;
use app\model\system\Stat;
use app\model\system\User;
use app\model\web\Notice as NoticeModel;
use Carbon\Carbon;

/**
 * 门店首页
 * @author Administrator
 *
 */
class Index extends BaseSupply
{
    public function index()
    {
        $supply_info = $this->supply_info;

        if ($supply_info[ 'expire_time' ] == 0) {
            $supply_info[ 'is_reopen' ] = 1;//永久有效
        } elseif ($supply_info[ 'expire_time' ] > time()) {
            $cha = $supply_info[ 'expire_time' ] - time();
            $date = ceil(( $cha / 86400 ));
            if ($date < 30) {
                $supply_info[ 'is_reopen' ] = 2;//离到期一月内才可以申请续签
                $supply_info[ 'expires_date' ] = (int) $date;
            }
        } else {
            $supply_info[ 'is_reopen' ] = 3;
            $supply_info[ 'expires_date' ] = 0;
        }
        $this->assign("supply_info", $supply_info);

        //判断是否有续签
        $reopen_model = new SupplyReopenModel();
        $reopen_info = $reopen_model->getReopenInfo([ [ 'sr.site_id', '=', $this->supply_id ], [ 'sr.apply_state', 'in', [ 1, -1 ] ] ]);
        if (empty($reopen_info[ 'data' ])) {
            $is_reopen = 1;
        } else {
            $is_reopen = 2;
        }
        $this->assign('is_reopen', $is_reopen);
        //会员基础信息
        $user_model = new User();
        $user_info = $user_model->getUserInfo([ [ 'uid', '=', $this->uid ] ], 'username,group_name,login_time');
        $this->assign("supply_user_info", $user_info[ 'data' ]);
        //基础统计信息
        $today = Carbon::now();
        $this->assign("today", $today);

        //网站公告
        $notice_model = new NoticeModel();
        $notice_list = $notice_model->getNoticePageList(
            [ [ 'receiving_type', 'like', '%shop%' ] ],
            1,
            5,
            'is_top desc,create_time desc',
            'id,title'
        );
        $this->assign('notice_list', $notice_list[ 'data' ][ 'list' ]);

        //获取总数
        $stat_shop_model = new Stat();

        $today = Carbon::now();
        $yesterday = Carbon::yesterday();
        $stat_today = $stat_shop_model->getStatShop($this->supply_id, $today->year, $today->month, $today->day);
        $stat_yesterday = $stat_shop_model->getStatShop($this->supply_id, $yesterday->year, $yesterday->month, $yesterday->day);
        $this->assign("stat_day", $stat_today[ 'data' ]);
        $this->assign("stat_yesterday", $stat_yesterday[ 'data' ]);
        $this->assign("today", $today);

        $supply_stat_sum = $stat_shop_model->getShopStatSum($this->supply_id);
        $goods_model = new GoodsModel();
        $goods_sum_data = $goods_model->getGoodsTotalCount([ 'site_id' => $this->supply_id ]);
        $supply_stat_sum[ 'data' ][ 'goods_count' ] = $goods_sum_data[ 'data' ];
        $this->assign('supply_stat_sum', $supply_stat_sum[ 'data' ]);

        return $this->fetch("index/index", [], $this->replace);
    }
}
