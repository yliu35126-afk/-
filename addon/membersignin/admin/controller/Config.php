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

namespace addon\membersignin\admin\controller;

use addon\membersignin\model\Signin;
use app\admin\controller\BaseAdmin;

/**
 * 会员签到
 */
class Config extends BaseAdmin
{

    public function index()
    {
        $config_model = new Signin();
        if (request()->isAjax()) {
            $data   = input("json", "{}");
            $cycle   = input("cycle", 30);//签到周期
            $is_use = input("is_use", 0);//是否启用
            $return_coupon = input('coupon', '');//优惠券
            $data   = json_decode($data,true);
            $res    = $config_model->setConfig(['cycle' => $cycle,'return_coupon' => $return_coupon, 'reward' => $data], $is_use);
            $this->addLog("设置会员签到奖励");
            return $res;
        } else {
            $config_result = $config_model->getConfig();
            $this->assign('config', $config_result['data']);
            return $this->fetch('config/index');
        }
    }


	
}