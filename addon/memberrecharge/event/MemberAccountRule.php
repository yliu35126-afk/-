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


namespace addon\memberrecharge\event;

use addon\memberconsume\model\Consume;
/**
 * 会员账户变化规则
 */
class MemberAccountRule
{
    
	public function handle($data)
	{
//	    $config = new Consume();
//	    $info = $config->getConfig();
//        if($data['account'] == 'point')
//        {
//            if($info['data']['value']['is_return_point'] == 1)
//            {
//                if($info['data']['value']['return_point_status'] == 'receive')
//                {
//                    $data = "会员消费订单收货返积分,比率".$info['data']['value']['return_point_rate']."%";
//                }
//                if($info['data']['value']['return_point_status'] == 'pay')
//                {
//                    $data = "会员消费订单支付返积分,比率".$info['data']['value']['return_point_rate']."%";
//                }
//                if($info['data']['value']['return_point_status'] == 'complete')
//                {
//                    $data = "会员消费订单支付返积分,比率".$info['data']['value']['return_point_rate']."%";
//                }
//            }
//        }
//        if($data['account'] == 'growth')
//        {
//            if($info['data']['value']['is_return_point'] == 1)
//            {
//                if($info['data']['value']['return_point_status'] == 'receive')
//                {
//                    $data = "会员消费订单收货返成长值,比率".$info['data']['value']['return_growth_rate']."%";
//                }
//                if($info['data']['value']['return_point_status'] == 'pay')
//                {
//                    $data = "会员消费订单支付返成长值,比率".$info['data']['value']['return_growth_rate']."%";
//                }
//                if($info['data']['value']['return_point_status'] == 'complete')
//                {
//                    $data = "会员消费订单支付返成长值,比率".$info['data']['value']['return_growth_rate']."%";
//                }
//            }
//        }
//        return '';
	    
	}
}