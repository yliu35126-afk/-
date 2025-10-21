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


use addon\coupon\model\Coupon as CouponModel;
use app\model\member\MemberAccount as MemberAccountModel;
use app\model\member\MemberAddress as MemberAddressModel;
use app\model\shop\ShopMember;
use app\model\order\OrderCommon;

/**
 * 店铺会员
 * @package app\shop\controller
 */
class Member extends BaseShop
{
    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
    }

    /**
     * 会员概况
     */
    public function index()
    {
        $member = new ShopMember();

        // 累计会员数
        $total_count = $member->getMemberCount([ [ 'nsm.site_id', '=', $this->site_id ] ]);
        // 今日新增数
        $newadd_count = $member->getMemberCount([ [ 'nsm.site_id', '=', $this->site_id ], [ 'nsm.create_time', 'between', [ date_to_time(date('Y-m-d 00:00:00')), time() ] ] ]);
        // 累计关注数
        $subscribe_count = $member->getMemberCount([ [ 'nsm.site_id', '=', $this->site_id ], [ 'nsm.is_subscribe', '=', 1 ] ]);
        // 已购会员数
        $buyed_count = $member->getPurchasedMemberCount($this->site_id);

        $this->assign('data', [
            'total_count' => $total_count[ 'data' ],
            'newadd_count' => $newadd_count[ 'data' ],
            'subscribe_count' => $subscribe_count[ 'data' ],
            'buyed_count' => $buyed_count[ 'data' ]
        ]);

        return $this->fetch("member/index");
    }

    /**
     * 获取区域会员数量
     */
    public function areaCount()
    {
        if (request()->isAjax()) {
            $member = new ShopMember();
            $handle = input('handle', false);
            $res = $member->getMemberCountByArea($this->site_id, $handle);
            return $res;
        }
    }

    /**
     * 店铺会员列表
     */
    public function lists()
    {
        $member = new ShopMember();
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('limit', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $search_text_type = input('search_text_type', 'nickname');
            $start_date = input('start_date', '');
            $end_date = input('end_date', '');

            $condition = [
                [ 'nsm.site_id', '=', $this->site_id ]
            ];
            $condition[] = [ $search_text_type, 'like', "%" . $search_text . "%" ];
            // 关注时间
            if ($start_date != '' && $end_date != '') {
                $condition[] = [ 'nsm.subscribe_time', 'between', [ strtotime($start_date), strtotime($end_date) ] ];
            } else if ($start_date != '' && $end_date == '') {
                $condition[] = [ 'nsm.subscribe_time', '>=', strtotime($start_date) ];
            } else if ($start_date == '' && $end_date != '') {
                $condition[] = [ 'nsm.subscribe_time', '<=', strtotime($end_date) ];
            }
            $list = $member->getShopMemberPageList($condition, $page_index, $page_size, 'nsm.subscribe_time desc');
            return $list;
        } else {
            return $this->fetch("member/lists");
        }
    }

    /**
     * 会员详情
     */
    public function detail()
    {
        $member_id = input('member_id', 0);


        $member = new ShopMember();
        $condition = [
            [ 'nsm.member_id', '=', $member_id ],
            [ 'nsm.site_id', '=', $this->site_id ],
            [ 'nm.is_delete', '=', 0 ]
        ];
        $join = [
            [
                'member nm',
                'nsm.member_id = nm.member_id',
                'inner'
            ],
        ];
        $field = 'nm.member_id, nm.source_member, nm.username, nm.nickname, nm.mobile, nm.email, nm.headimg, nm.status, nm.point, nm.balance, nm.balance_money, nm.growth, nsm.subscribe_time, nsm.site_id, nsm.is_subscribe';
        $info = $member->getMemberInfo($condition, $field, 'nsm', $join);
        if ($info[ 'code' ] < 0) $this->error($info[ 'message' ]);
        //账户类型和来源类型
        $member_account_model = new MemberAccountModel();
        $account_type_arr = $member_account_model->getAccountType();
        $this->assign('account_type_arr', $account_type_arr);
        $this->assign('info', $info[ 'data' ]);
        return $this->fetch("member/detail");
    }

    /**
     * 获取会员订单列表
     */
    public function accountList()
    {
        if (request()->isAjax()) {
            $member_id = input('member_id', 0);
            $page_index = input('page', 1);
            $page_size = input('limit', PAGE_LIST_ROWS);

            $condition = [
                [ 'member_id', '=', $member_id ],
                [ 'site_id', '=', $this->site_id ]
            ];

            $field = 'order_id,order_no,order_name,order_money,pay_money,balance_money,order_type_name,order_status_name,create_time';
            $order = new OrderCommon();
            $list = $order->getMemberOrderPageList($condition, $page_index, $page_size, 'order_id desc', $field);
            return $list;
        }else{
            $member_id = input("member_id", 0);//会员id
            $this->assign('member_id', $member_id);
            //会员详情四级菜单
            $this->forthMenu([ 'member_id' => $member_id ]);
            return $this->fetch('member/account_list');
        }
    }

    /**
     * 订单管理
     */
    public function order()
    {
        $member_id = input("member_id", 0);//会员id
        $this->assign('member_id', $member_id);
        //会员详情四级菜单
        $this->forthMenu([ 'member_id' => $member_id ]);
        return $this->fetch('member/order');

    }

    /**
     * 会员领取优惠卷
     */
    public function memberCoupon()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $member_id = input('member_id', 0);

            $condition[] = [ 'site_id', '=', $this->site_id ];
            $condition[] = [ 'member_id', '=', $member_id ];

            //查询会员领取的优惠券
            $coupon_model   = new CouponModel();
            $res = $coupon_model->getCouponPageList($condition, $page, $page_size);
            return $res;
        } else {
            $member_id = input('member_id', 0);
            $this->assign('member_id', $member_id);

            //会员详情四级菜单
            $this->forthMenu([ 'member_id' => $member_id ]);

            return $this->fetch('member/member_coupon');
        }
    }
    /**
     * 会员地址
     */
    public function addressDetail()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $member_id = input('member_id', 0);
            $condition[] = [ 'member_id', '=', $member_id ];
            //会员地址
            $member_address_model = new MemberAddressModel();
            $res = $member_address_model->getMemberAddressPageList($condition, $page, $page_size);
            return $res;

        } else {
            $member_id = input('member_id', 0);
            $this->assign('member_id', $member_id);

            //会员详情四级菜单
            $this->forthMenu([ 'member_id' => $member_id ]);

            return $this->fetch('member/address_detail');
        }
    }
}