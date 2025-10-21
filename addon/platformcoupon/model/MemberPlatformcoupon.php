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

namespace addon\platformcoupon\model;

use app\model\BaseModel;
/**
 * 优惠券
 */
class MemberPlatformcoupon extends BaseModel
{

    /**
     * 获取会员已领取优惠券优惠券
     * @param array $member_id
     */
    public function getMemberPlatformcouponList($member_id, $state, $order = "fetch_time desc"){
        $condition = array(
            ["member_id", "=", $member_id],
            ["state", "=", $state],
        );

        $list = model("promotion_platformcoupon")->getList($condition, "*", $order, '', '', '', 0);
        return $this->success($list);
    }

    /**
     * 使用优惠券
     * @param $platformcoupon_id
     */
    public function useMemberPlatformcoupon($platformcoupon_id, $member_id, $order_id = 0){
        //优惠券处理方案
        $result = model('promotion_platformcoupon')->update(['use_order_id' => $order_id, 'state' => 2, 'use_time' => time()], [['platformcoupon_id', '=', $platformcoupon_id], ["member_id", "=", $member_id], ['state', '=', 1]]);
        if($result === false){
            return $this->error();
        }
        return $this->success();
    }

    
    /**
     * 获取会员已领取优惠券优惠券数量
     * @param unknown $member_id
     * @param unknown $state
     * @return multitype:number unknown
     */
    public function getMemberPlatformcouponNum($member_id, $state)
    {
        $condition = array(
            [ "member_id", "=", $member_id ],
            [ "state", "=", $state ],
        );

        $num = model("promotion_platformcoupon")->getCount($condition);
        return $this->success($num);
    }
    
    /**
     * 会员是否可领取该优惠券
     */
    public function receivedNum($platformcoupon_type_id, $member_id){
        $received_num = model('promotion_platformcoupon')->getCount([ ['platformcoupon_type_id', '=', $platformcoupon_type_id], ['member_id', '=', $member_id] ]);
        return $this->success($received_num);
    }

    /**
     * 获取编码
     */
    public function getCode()
    {
        return random_keys(8);
    }

    /**
     * 会员批量发送优惠券
     * @param $platformcoupon_type_ids
     * @param $member_id
     * @param int $get_type
     * @param int $is_stock   是否有库存 0 正常发放已有数量优惠卷，1不限库存发放优惠卷 等于1时不限制库存 不减库存
     * @param int $is_limit   是否限制个人领取数量  0 不限制个人最大领取数量  1限制个人最大领取数量
     * @return array
     */
    public function sendPlatformcoupon($platformcoupon_type_ids, $member_id, $get_type = 4, $is_stock = 0, $is_limit = 1)
    {

        //已选优惠券提交数组
        if(!empty($platformcoupon_type_ids)){
            model('promotion_platformcoupon')->startTrans();
            try{
                foreach ($platformcoupon_type_ids as $platformcoupon_type_id) {
                    $platformcoupon_type_info = model('promotion_platformcoupon_type')->getInfo(['platformcoupon_type_id' => $platformcoupon_type_id]);
                    if (!empty($platformcoupon_type_info)) {
                        if ($platformcoupon_type_info['count'] == $platformcoupon_type_info['lead_count'] && $is_stock == 0) {
                            continue;
                        }
                        if ($platformcoupon_type_info['max_fetch'] != 0) {
                            //限制领取
                            $member_receive_num = model('promotion_platformcoupon')->getCount([
                                'platformcoupon_type_id' => $platformcoupon_type_id,
                                'member_id' => $member_id
                            ]);
                            if ($member_receive_num >= $platformcoupon_type_info['max_fetch'] && $is_limit == 1) {
                                continue;
                            }
                        }

                        //优惠券已过期
                        if ($platformcoupon_type_info['status'] !=1 ) {
                            continue;
                        }

                        $data = [
                            'platformcoupon_type_id' => $platformcoupon_type_id,
                            'platformcoupon_code' => $this->getCode(),
                            'member_id' => $member_id,
                            'money' => $platformcoupon_type_info['money'],
                            'state' => 1,
                            'get_type' => $get_type,
                            'fetch_time' => time(),
                            'platformcoupon_name' => $platformcoupon_type_info['platformcoupon_name'],
                            'at_least' => $platformcoupon_type_info['at_least'],
                            'use_scenario' => $platformcoupon_type_info['use_scenario'],
                            'group_ids' => $platformcoupon_type_info['group_ids'],
                            'platform_split_rare' => $platformcoupon_type_info['platform_split_rare'],
                            'shop_split_rare' => $platformcoupon_type_info['shop_split_rare']
                        ];
                        if ($platformcoupon_type_info['validity_type'] == 0) {
                            $data['end_time'] = $platformcoupon_type_info['end_time'];
                        } else {
                            $data['end_time'] = (time() + $platformcoupon_type_info['fixed_term'] * 86400);
                        }
                        model('promotion_platformcoupon')->add($data);
                        if ($is_stock == 0) {
                            model('promotion_platformcoupon_type')->setInc(['platformcoupon_type_id' => $platformcoupon_type_id], 'lead_count');
                        }
                    }
                }
                model('promotion_platformcoupon')->commit();
                return $this->success();
            }catch(\Exception $e){

                model('promotion_platformcoupon')->rollback();
                return $this->error('',$e->getMessage());
            }
        }else{
            return $this->error();
        }
    }
}