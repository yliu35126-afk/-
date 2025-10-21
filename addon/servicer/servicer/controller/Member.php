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

namespace addon\servicer\servicer\controller;

use app\model\member\Member as MemberModel;
use think\facade\Config;

/**
 * 会员相关
 * Class Member
 * @package addon\servicer\servicer\controller
 */
class Member extends BaseServicer
{
    /**
     * 会员信息
     */
    public function info()
    {
        if (request()->isAjax()) {
            $field = 'nickname,mobile,headimg,member_level_name,balance,balance_money,login_type,login_type_name';
            $member_model = new MemberModel();
            $res = $member_model->getMemberInfo([['member_id', '=', input('member_id')]], $field);
            if (!empty($res['data'])) {
                $res['data']['total_balance'] = $res['data']['balance'] + $res['data']['balance_money'];
            }
            return $res;
        }
    }
}