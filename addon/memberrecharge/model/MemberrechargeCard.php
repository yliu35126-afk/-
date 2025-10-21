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

namespace addon\memberrecharge\model;

use addon\coupon\model\Coupon;
use app\model\BaseModel;
use addon\coupon\model\CouponType;
use app\model\member\MemberAccount;
use think\facade\Cache;

/**
 * 开卡
 */
class MemberrechargeCard extends BaseModel
{

    /**
     * 开卡
     * @param $data
     * @return array
     */
    public function addMemberRechargeCard($data)
    {
        $card_account = substr(md5(date('YmdHis') . mt_rand(100, 999)), 8, 16);
        $card_data = [
            'recharge_id' => $data[ 'recharge_id' ],
            'card_account' => $card_account,
            'recharge_name' => $data[ 'recharge_name' ],
            'cover_img' => $data[ 'cover_img' ],
            'face_value' => $data[ 'face_value' ],
            'point' => $data[ 'point' ],
            'growth' => $data[ 'growth' ],
            'coupon_id' => $data[ 'coupon_id' ],
            'buy_price' => $data[ 'buy_price' ],
            'member_id' => $data[ 'member_id' ],
            'member_img' => $data[ 'member_img' ],
            'nickname' => $data[ 'nickname' ],
            'order_id' => $data[ 'order_id' ],
            'order_no' => $data[ 'order_no' ],
            'use_status' => $data[ 'use_status' ],
            'create_time' => time(),
            'use_time' => $data[ 'use_time' ]
        ];
        $res = model('member_recharge_card')->add($card_data);
        Cache::tag("member_recharge_card")->clear();
        return $this->success($res);
    }

    /**
     *  开卡发放礼包
     * @param $order_info
     */
    public function addMemberAccount($order_info)
    {
        $member_account = new MemberAccount();
        //修改用户的余额
        $member_account->addMemberAccount($order_info[ 'member_id' ], 'balance', $order_info[ 'face_value' ], 'memberrecharge', '0', '会员充值增加余额');

        //积分
        if ($order_info[ 'point' ] > 0) {
            $member_account->addMemberAccount($order_info[ 'member_id' ], 'point', $order_info[ 'point' ], 'memberrecharge', '0', '会员充值增加积分');
        }

        //成长值
        if ($order_info[ 'growth' ] > 0) {
            $member_account->addMemberAccount($order_info[ 'member_id' ], 'growth', $order_info[ 'growth' ], 'memberrecharge', '0', '会员充值增加成长值');
        }
        //添加优惠券
        if (!empty($order_info[ 'coupon_id' ])) {
            $coupon_model = new Coupon();
            $coupon_id = explode(',', $order_info[ 'coupon_id' ]);
            foreach ($coupon_id as $v) {
                //获取优惠券站点ID
                $coupon_type_model = new CouponType();
                $coupon_type = $coupon_type_model->getCouponTypeInfo($v, $order_info[ 'site_id' ]);
                $coupon_model->receiveCoupon($v, $order_info[ 'member_id' ], 1);
            }
        }
    }

    /**
     * 套餐详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getMemberRechargeCardInfo($condition = [], $field = '*')
    {
        $card = model('member_recharge_card')->getInfo($condition, $field);
        if ($card) {
            //获取优惠券信息
            if ($card[ 'coupon_id' ]) {
                //优惠券字段
                $coupon_field = 'coupon_type_id,coupon_name,money,count,lead_count,max_fetch,at_least,end_time,image,validity_type,fixed_term';

                $model = new CouponType();
                $coupon = $model->getCouponTypeList([ [ 'coupon_type_id', 'in', $card[ 'coupon_id' ] ] ], $coupon_field);
                $card[ 'coupon_list' ] = $coupon;
            }

        }
        Cache::tag("member_recharge_card")->clear();
        return $this->success($card);
    }

    /**
     * 开卡列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getMemberRechargeCardPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('member_recharge_card')->pageList($condition, $field, $order, $page, $page_size);

        Cache::tag("member_recharge_card")->clear();
        return $this->success($list);
    }

}