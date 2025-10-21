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

namespace app\shopapi\controller;


use app\model\shop\ShopMember;
use app\model\order\OrderCommon;

/**
 * 店铺会员
 * @package app\shop\controller
 */
class Member extends BaseApi
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
     * 店铺会员列表
     */
    public function lists()
    {
        $member = new ShopMember();

        $page_index = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        $search_text = isset($this->params[ 'search_text' ]) ? $this->params[ 'search_text' ] : '';
        $start_date = isset($this->params[ 'start_date' ]) ? $this->params[ 'start_date' ] : '';
        $end_date = isset($this->params[ 'end_date' ]) ? $this->params[ 'end_date' ] : '';

        $condition = [
            [ 'nsm.site_id', '=', $this->site_id ]
        ];
        if (!empty($search_text)) {
            $condition[] = [ 'nickname|mobile|email', 'like', "%" . $search_text . "%" ];
        }
        // 关注时间
        if ($start_date != '' && $end_date != '') {
            $condition[] = [ 'nsm.subscribe_time', 'between', [ strtotime($start_date), strtotime($end_date) ] ];
        } else if ($start_date != '' && $end_date == '') {
            $condition[] = [ 'nsm.subscribe_time', '>=', strtotime($start_date) ];
        } else if ($start_date == '' && $end_date != '') {
            $condition[] = [ 'nsm.subscribe_time', '<=', strtotime($end_date) ];
        }
        $list = $member->getShopMemberPageList($condition, $page_index, $page_size, 'nsm.subscribe_time desc');
        return $this->response($list);
    }

    /**
     * 会员详情
     */
    public function detail()
    {
        $member_id = isset($this->params[ 'member_id' ]) ? $this->params[ 'member_id' ] : 0;
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
        $field = 'nm.member_id, nm.source_member, nm.username, nm.nickname, nm.mobile, nm.email, nm.headimg, nm.status, nsm.subscribe_time, nsm.site_id, nsm.is_subscribe';
        $info = $member->getMemberInfo($condition, $field, 'nsm', $join);
        return $this->response($info);
    }

    /**
     * 获取会员订单列表
     */
    public function orderList()
    {
        $member_id = isset($this->params[ 'member_id' ]) ? $this->params[ 'member_id' ] : 0;

        $page_index = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        $condition = [
            [ 'member_id', '=', $member_id ],
            [ 'site_id', '=', $this->site_id ]
        ];

        $field = 'order_id,order_no,order_name,order_money,pay_money,balance_money,order_type_name,order_status_name,create_time';
        $order = new OrderCommon();
        $list = $order->getMemberOrderPageList($condition, $page_index, $page_size, 'order_id desc', $field);
        return $this->response($list);
    }
}