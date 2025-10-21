<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\platformcoupon\admin\controller;

use addon\platformcoupon\model\MemberPlatformcoupon;
use addon\platformcoupon\model\Platformcoupon as PlatformcouponModel;
use addon\platformcoupon\model\PlatformcouponType as PlatformcouponTypeModel;
use app\admin\controller\BaseAdmin;
use app\model\shop\ShopGroup as ShopGroupModel;

/**
 * 优惠券
 * @author Administrator
 *
 */
class Platformcoupon extends BaseAdmin
{
    /**
     * 活动列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $platformcoupon_name = input('platformcoupon_name', '');
            $status = input('status', '');

            $condition = [];
            if ($status !== "") {
                $condition[] = [ 'status', '=', $status ];
            }
            $type = input('type');
            if ($type) {
                $condition[] = [ 'type', '=', $type ];
            }
            //类型
            $validity_type = input('validity_type', '');
            if ($validity_type) {
                $start_time = input('start_time', '');
                $end_time = input('end_time', '');
                switch ( $validity_type ) {

                    case 1: //固定

                        $condition[] = [ 'end_time', 'between', [ $start_time, $end_time ] ];
                        break;
                    case 2:

                        $condition[] = [ 'fixed_term', 'between', [ $start_time, $end_time ] ];
                        break;
                }
            }

            $condition[] = [ 'platformcoupon_name', 'like', '%' . $platformcoupon_name . '%' ];
            $order = 'create_time desc';
            $field = '*';

            $platformcoupon_type_model = new PlatformcouponTypeModel();
            $res = $platformcoupon_type_model->getPlatformcouponTypePageList($condition, $page, $page_size, $order, $field);

            //获取优惠券状态
            $platformcoupon_type_status_arr = $platformcoupon_type_model->getPlatformcouponTypeStatus();
            foreach ($res[ 'data' ][ 'list' ] as $key => $val) {
                $res[ 'data' ][ 'list' ][ $key ][ 'status_name' ] = $platformcoupon_type_status_arr[ $val[ 'status' ] ];
            }
            return $res;

        } else {
            //优惠券状态
            $platformcoupon_type_model = new PlatformcouponTypeModel();
            $platformcoupon_type_status_arr = $platformcoupon_type_model->getPlatformcouponTypeStatus();
            $this->assign('platformcoupon_type_status_arr', $platformcoupon_type_status_arr);

            //店铺等级
            $shop_group_model = new ShopGroupModel();
            $shop_group = $shop_group_model->getGroupList([], '*');
            $this->assign('group_list', $shop_group[ 'data' ]);

            return $this->fetch("platformcoupon/lists");
        }
    }

    /**
     * 添加活动
     */
    public function add()
    {

        if (request()->isAjax()) {
            $group_ids = input('group_ids', []);
            $group_ids = implode(',', $group_ids);
            $shop_split_rare = input('shop_split_rare', 0);
            $data = [
                'platformcoupon_name' => input('platformcoupon_name', ''),//优惠券名称
                'money' => input('money', ''),//优惠券面额
                'count' => input('count', ''),//发放数量
                'max_fetch' => input('max_fetch', ''),//最大领取数量
                'at_least' => input('at_least', ''),//满多少元可以使用
                'end_time' => strtotime(input('end_time', '')),//活动结束时间
                'image' => input('image', ''),//优惠券图片
                'validity_type' => input('validity_type', ''),//有效期类型 0固定时间 1领取之日起
                'fixed_term' => input('fixed_term', ''),//领取之日起N天内有效
                'is_show' => input('is_show', 0),//是否允许直接领取 1:是 0：否 允许直接领取，用户才可以在手机端和PC端进行领取，否则只能以活动的形式发放。

                'use_scenario' => input('use_scenario', 1),//使用场景
                'group_ids' => $group_ids,
                'platform_split_rare' => 100 - $shop_split_rare,//平台分担比率
                'shop_split_rare' => input('shop_split_rare', 0),//店铺分担比率
                'create_time' => time()
            ];

            $platformcoupon_type_model = new PlatformcouponTypeModel();
            return $platformcoupon_type_model->addPlatformcouponType($data);
        } else {

            //店铺等级
            $shop_group_model = new ShopGroupModel();
            $shop_group = $shop_group_model->getGroupList([], '*');
            $this->assign('group_list', $shop_group[ 'data' ]);
            return $this->fetch("platformcoupon/add");
        }
    }

    /**
     * 编辑活动
     */
    public function edit()
    {
        $platformcoupon_type_model = new PlatformcouponTypeModel();
        if (request()->isAjax()) {
            $group_ids = input('group_ids', []);
            $group_ids = implode(',', $group_ids);
            $shop_split_rare = input('shop_split_rare', 0);
            $data = [
                'platformcoupon_name' => input('platformcoupon_name', ''),//优惠券名称
                'money' => input('money', ''),//优惠券面额
                'count' => input('count', ''),//发放数量
                'max_fetch' => input('max_fetch', ''),//最大领取数量
                'at_least' => input('at_least', ''),//满多少元可以使用
                'end_time' => strtotime(input('end_time', '')),//活动结束时间
                'image' => input('image', ''),//优惠券图片
                'validity_type' => input('validity_type', ''),//有效期类型 0固定时间 1领取之日起
                'fixed_term' => input('fixed_term', ''),//领取之日起N天内有效
                'is_show' => input('is_show', 0),//是否允许直接领取 1:是 0：否 允许直接领取，用户才可以在手机端和PC端进行领取，否则只能以活动的形式发放。

                'use_scenario' => input('use_scenario', 1),//使用场景
                'group_ids' => $group_ids,
                'platform_split_rare' => 100 - $shop_split_rare,//平台分担比率
                'shop_split_rare' => input('shop_split_rare', 0),//店铺分担比率
            ];

            $platformcoupon_type_id = input('platformcoupon_type_id', 0);

            return $platformcoupon_type_model->editPlatformcouponType($data, $platformcoupon_type_id);
        } else {

            $platformcoupon_type_id = input('platformcoupon_type_id', 0);
            $this->assign('platformcoupon_type_id', $platformcoupon_type_id);

            $platformcoupon_type_info = $platformcoupon_type_model->getPlatformcouponTypeInfo($platformcoupon_type_id);
            $this->assign('platformcoupon_type_info', $platformcoupon_type_info[ 'data' ]);

            //店铺等级
            $shop_group_model = new ShopGroupModel();
            $shop_group = $shop_group_model->getGroupList([], '*');
            $this->assign('group_list', $shop_group[ 'data' ]);

            return $this->fetch("platformcoupon/edit");
        }
    }

    /**
     * 活动详情
     */
    public function detail()
    {
        $platformcoupon_type_id = input('platformcoupon_type_id', 0);
        $platformcoupon_type_model = new PlatformcouponTypeModel();
        $platformcoupon_type_info = $platformcoupon_type_model->getPlatformcouponTypeInfo($platformcoupon_type_id);

        $this->assign('platformcoupon_type_info', $platformcoupon_type_info[ 'data' ]);

        //店铺等级
        $shop_group_model = new ShopGroupModel();
        $shop_group = $shop_group_model->getGroupList([], '*');
        $this->assign('group_list', $shop_group[ 'data' ]);

        return $this->fetch("platformcoupon/detail");
    }

    /**
     * 发送优惠券
     */
    public function send()
    {
        if (request()->isAjax()) {
            $member_id = input('member_id');
            $platformcoupon_type_ids = input('parent', 0);
            $get_type = input('get_type', 4);
            $parent = $platformcoupon_type_ids;
            $site_id = $this->site_id;
            if (empty($parent)) {
                return $this->error('', 'REQUEST_PLATFORMCOUPON_TYPE_ID');
            }
            if (count($parent, COUNT_NORMAL) == 1) {
                $coupon_model = new PlatformcouponModel();
                $res = $coupon_model->receivePlatformcoupon($parent[ 0 ], $member_id, $get_type);
            } else {
                $memberplatformcoupon_model = new MemberPlatformcoupon();
                $res = $memberplatformcoupon_model->sendPlatformcoupon($parent, $member_id, $get_type);
            }
            return $res;
        }
    }


    /**
     * 关闭活动
     */
    public function close()
    {
        if (request()->isAjax()) {
            $platformcoupon_type_id = input('platformcoupon_type_id', 0);
            $platformcoupon_type_model = new PlatformcouponTypeModel();
            return $platformcoupon_type_model->closePlatformcouponType($platformcoupon_type_id);
        }
    }

    /**
     * 删除活动
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $platformcoupon_type_id = input('platformcoupon_type_id', 0);
            $platformcoupon_type_model = new PlatformcouponTypeModel();
            return $platformcoupon_type_model->deletePlatformcouponType($platformcoupon_type_id);
        }
    }

    /**
     * 优惠券领取记录
     * */
    public function receive()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $platformcoupon_type_id = input('platformcoupon_type_id', 0);
            $state = input('state', '');
            $condition = [];
            $condition[] = [ 'npc.platformcoupon_type_id', '=', $platformcoupon_type_id ];
            if ($state !== '') {
                $condition[] = [ 'state', '=', $state ];
            }
            $platformcoupon_model = new PlatformcouponModel();
            $res = $platformcoupon_model->getMemberPlatformcouponPageList($condition, $page, $page_size);
            return $res;
        } else {
            $platformcoupon_type_id = input('platformcoupon_type_id', 0);
            $this->assign('platformcoupon_type_id', $platformcoupon_type_id);
            return $this->fetch("platformcoupon/receive");
        }
    }

    /**
     * 活动列表
     */
    public function couponSelect()
    {
        $platformcoupon_type_model = new PlatformcouponTypeModel();
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $platformcoupon_name = input('platformcoupon_name', '');

            $condition[] = [ 'status', '=', 1 ];
            $condition[] = [ 'platformcoupon_name', 'like', '%' . $platformcoupon_name . '%' ];
            $order = 'create_time desc';
            $field = '*';

            $res = $platformcoupon_type_model->getPlatformCouponTypePageList($condition, $page, $page_size, $order, $field);

            //获取优惠券状态
            $coupon_type_status_arr = $platformcoupon_type_model->getPlatformCouponTypeStatus();
            foreach ($res[ 'data' ][ 'list' ] as $key => $val) {
                $res[ 'data' ][ 'list' ][ $key ][ 'status_name' ] = $coupon_type_status_arr[ $val[ 'status' ] ];
            }
            return $res;

        } else {
            //优惠券状态
            $coupon_type_status_arr = $platformcoupon_type_model->getPlatformCouponTypeStatus();
            $this->assign('coupon_type_status_arr', $coupon_type_status_arr);
            $select_id = input('select_id', '');
            $this->assign('select_id', $select_id);

            return $this->fetch("platformcoupon/platformcoupon_select");
        }
    }
}