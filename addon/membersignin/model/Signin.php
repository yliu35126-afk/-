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

namespace addon\membersignin\model;

use addon\platformcoupon\model\PlatformcouponType;
use app\model\system\Config as ConfigModel;
use app\model\BaseModel;

/**
 * 会员签到
 */
class Signin extends BaseModel
{
    /**
     * 会员签到奖励设置
     * @param $data
     * @param $is_use
     * @return array
     */
    public function setConfig($data, $is_use)
    {
        if (!empty($data)) {
            $day = 0;
            foreach ($data['reward'] as $k => $v) {
                if ($v['day'] == 0) {
                    return $this->error('', '连续签到天数不能设置为0');
                }

                if ($k != 0) {

                    if ($v['day'] <= $day) {
                        return $this->error('', '连续签到天数不能相同或者小于上一个签到天数');
                    }
                }
                $day = $v['day'];
            }
        }
        $config = new ConfigModel();
        $res = $config->setConfig($data, '会员签到奖励设置', $is_use, [['site_id', '=', 0], ['app_module', '=', 'admin'], ['config_key', '=', 'MEMBER_SIGNIN_REWARD_CONFIG']]);
        return $res;

    }

    /**
     * 会员签到奖励设置
     */
    public function getConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', 0], ['app_module', '=', 'admin'], ['config_key', '=', 'MEMBER_SIGNIN_REWARD_CONFIG']]);
        if (empty($res['data']['value'])) {
            $res['data']['value'] = [
                "cycle" => 0,
                'reward' => [
                    [
                        'point' => 0,
                        'growth' => 0,
                        'day' => 1,
                    ]
                ],
                "return_coupon" => 0,
            ];
        }
        if (!isset($res['data']['value']['return_coupon']))  $res['data']['value']['return_coupon'] = 0;
        $coupon_list = [];
        if($res['data']['value']['return_coupon'] != 0 && $res['data']['value']['return_coupon'] != '') {
            $coupon = new PlatformcouponType();
            $coupon_list = $coupon->getPlatformcouponTypeList([ ['status','=',1],['platformcoupon_type_id','in',$res['data']['value']['return_coupon']] ]);
            $coupon_list = $coupon_list['data'];
        }
        $res['data']['value']['coupon_list'] = $coupon_list;
        return $res;
    }

    public function memberSignin()
    {

    }

}