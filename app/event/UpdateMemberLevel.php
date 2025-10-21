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


namespace app\event;

use addon\platformcoupon\model\MemberPlatformcoupon;
use app\model\member\MemberAccount as MemberAccountModel;
use app\model\member\MemberLevel;
/**
 * 会员等级变化（执行会员成长值变化）
 */
class UpdateMemberLevel
{
	// 行为扩展的执行入口必须是run
	public function handle($data)
	{
        $member_account_model = new MemberAccountModel();
        if($data['account_type'] == 'growth')
        {
            //成长值变化等级检测变化
            $growth_info = model("member")->getInfo([['member_id', '=', $data['member_id']]], 'growth, member_level');
            //查询会员等级
            $member_level = new MemberLevel();
            //$level_list = $member_level->getMemberLevelList([['growth', '<=', $growth_info['growth']]], 'level_id, level_name, sort, growth', 'sort desc');
            $level_list   = $member_level->getMemberLevelList([['growth', '<=', $growth_info['growth']]],  'level_id, level_name, sort, growth, send_point, send_balance, send_coupon', 'growth desc');
            $level_detail = [];
            if(!empty($level_list['data']))
            {
                //检测升级
                if($growth_info['member_level'] == 0)
                {
                    //将用户设置为最大等级
                    $data_level = [
                        'member_level' => $level_list['data'][0]['level_id'],
                        'member_level_name' => $level_list['data'][0]['level_name']
                    ];
                    $level_detail = $level_list['data'][0];
                    model("member")->update($data_level, [['member_id', '=', $data['member_id']]]);
                }else{
                    $level_info = $member_level->getMemberLevelInfo([['level_id', '=', $growth_info['member_level']]]);
                    if(empty($level_info['data']))
                    {
                        //将用户设置为最大等级
                        $data_level = [
                            'member_level' => $level_list['data'][0]['level_id'],
                            'member_level_name' => $level_list['data'][0]['level_name']
                        ];
                        $level_detail = $level_list['data'][0];
                        model("member")->update($data_level, [['member_id', '=', $data['member_id']]]);
                    }else{
                        if($level_info['data']['sort'] <  $level_list['data'][0]['sort'])
                        {
                            //将用户设置为最大等级
                            $data_level = [
                                'member_level' => $level_list['data'][0]['level_id'],
                                'member_level_name' => $level_list['data'][0]['level_name']
                            ];
                            $level_detail = $level_list['data'][0];
                            model("member")->update($data_level, [['member_id', '=', $data['member_id']]]);
                        }
                    }
                }

            }

            //  如果存在已升级等级   发放升级奖励
            if (!empty($level_detail)) {
                // 添加会员卡变更记录
               // $member_level->addMemberLevelChangeRecord($data['member_id'], $data['site_id'], $level_detail['level_id'],0, 'upgrade', $data['member_id'], 'member', $member_info['nickname']);
                if ($level_detail['send_balance'] > 0) {
                    //赠送红包
                    $balance = $level_detail['send_balance'];
                    $member_account_model->addMemberAccount($data['member_id'], 'balance', $balance, 'upgrade', '会员升级得红包' . $balance, '会员升级得红包' . $balance);
                }
                if ($level_detail['send_point'] > 0) {
                    //赠送积分
                    $send_point = $level_detail['send_point'];
                    $member_account_model->addMemberAccount($data['member_id'], 'point', $send_point, 'upgrade', '会员升级得积分' . $send_point, '会员升级得积分' . $send_point);
                }
                //给用户发放优惠券
                if (!empty($level_detail['send_coupon'])) {
                    $platformcoupon_type_ids = $level_detail['send_coupon'];
                    $get_type = 6;
                    $platformcoupon_type_ids = explode(',', $platformcoupon_type_ids);
                    $memberplatformcoupon_model = new MemberPlatformcoupon();
                    $memberplatformcoupon_model->sendPlatformcoupon($platformcoupon_type_ids, $data['member_id'], $get_type);
                }
            }
        }
	}
	
}