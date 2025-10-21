<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace app\shop\controller;

use app\model\member\MemberLevel as MemberLevelModel;
use addon\coupon\model\CouponType;
use app\model\member\Member as MemberModel;

/**
 * 会员等级管理 控制器
 */
class Memberlevel extends BaseShop
{
    /**
     * 会员等级列表
     */
    public function levelList()
    {
        if (request()->isAjax()) {
            $page        = input('page', 1);
            $page_size   = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');
            $level_type = input('level_type', 0);

            $condition   = [
                ['site_id', '=', $this->site_id],
                ['level_type', '=', $level_type]
            ];
            if (!empty($search_text)) $condition[] = ['level_name', 'like', "%" . $search_text . "%"];
            $order       = 'growth asc,level_id desc';
            $field       = '*';

            $member_level_model = new MemberLevelModel();
            $list               = $member_level_model->getMemberLevelPageList($condition, $page, $page_size, $order, $field);
            if (!empty($list['data']['list'])) {
                $member_model = new MemberModel();
                foreach ($list['data']['list'] as $k => $item) {
                    $count = $member_model->getMemberCount([ ['member_level', '=', $item['level_id'] ], ['is_delete', '=', 0] ]);
                    $list['data']['list'][$k]['member_num'] = $count['data'];
                }
            }
            return $list;
        } else {
            return $this->fetch('memberlevel/level_list');
        }
    }

    /**
     * 会员等级添加
     */
    public function addLevel()
    {
        $member_level_model = new MemberLevelModel();
        if (request()->isAjax()) {
            $data = [
                'site_id'          => $this->site_id,
                'level_name'       => input('level_name', ''),
                'growth'           => input('growth', 0),
                'remark'           => input('remark', ''),
                'consume_discount' => input('consume_discount', 100),
                'point_feedback'   => input('point_feedback', 0),
                'send_point'       => input('send_point', 0),
                'send_balance'     => input('send_balance', 0),
                'send_coupon'      => input('send_coupon', ''),
                'level_type'      => 0,
                'charge_rule' => '',
                'charge_type' => 0
            ];
            $this->addLog("会员等级添加:" . $data['level_name']);
            $res = $member_level_model->addMemberLevel($data);
            return $res;

        } else {

            //获取优惠券列表
            $coupon_model = new CouponType();
            $condition    = [
                ['status', '=', 1],
                ['site_id', '=', $this->site_id],
            ];
            //优惠券字段
            $coupon_field = 'coupon_type_id,type,coupon_name,image,money,discount,validity_type,fixed_term,status,is_limit,at_least,count,lead_count,end_time';
            $coupon_list  = $coupon_model->getCouponTypeList($condition, $coupon_field);
            $this->assign('coupon_list', $coupon_list);

            $this->assign('level_time', $member_level_model->level_time);

            return $this->fetch('memberlevel/add_level');
        }
    }

    /**
     * 会员等级修改
     */
    public function editLevel()
    {
        $member_level_model = new MemberLevelModel();
        if (request()->isAjax()) {
            $data = [
                'site_id'          => $this->site_id,
                'level_name'       => input('level_name', ''),
                'growth'           => input('growth', 0.00),
                'remark'           => input('remark', ''),
                'is_free_shipping' => input('is_free_shipping', 0),
                'consume_discount' => input('consume_discount', 100),
                'point_feedback'   => input('point_feedback', 0),
                'send_point'       => input('send_point', 0),
                'send_balance'     => input('send_balance', 0),
                'send_coupon'      => input('send_coupon', ''),
                'charge_rule' => ''
            ];

            $level_id = input('level_id', 0);


            $this->addLog("会员等级修改:" . $data['level_name']);
            return $member_level_model->editMemberLevel($data, [['level_id', '=', $level_id]]);
        } else {

            $level_id   = input('get.level_id', 0);
            $level_info = $member_level_model->getMemberLevelInfo([['level_id', '=', $level_id]]);
            $this->assign('level_info', $level_info['data']);

            $this->assign('level_time', $member_level_model->level_time);

            //获取优惠券列表
            $coupon_model = new CouponType();
            $condition    = [
                ['status', '=', 1],
                ['site_id', '=', $this->site_id],
            ];
            //优惠券字段
            $coupon_field = 'coupon_type_id,type,coupon_name,image,money,discount,validity_type,fixed_term,status,is_limit,at_least,count,lead_count,end_time';
            $coupon_list  = $coupon_model->getCouponTypeList($condition, $coupon_field);
            $this->assign('coupon_list', $coupon_list);
            return $this->fetch('memberlevel/edit_level');
        }
    }

    /**
     * 会员等级删除
     */
    public function deleteLevel()
    {
        $level_id          = input('level_id', '');
        $member_level_model = new MemberLevelModel();
        $this->addLog("会员等级删除id:" . $level_id);
        return $member_level_model->deleteMemberLevel($level_id, $this->site_id);
    }
}