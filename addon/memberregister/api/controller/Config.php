<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\memberregister\api\controller;

use app\api\controller\BaseApi;
use addon\memberregister\model\Register;

/**
 * 会员注册奖励
 */
class Config extends BaseApi
{

    /**
     * 计算信息
     */
    public function config()
    {
        //注册后奖励
        $register_model = new Register();
        $info = $register_model->getConfig();
        if(!empty($info['data'])){
            if(!empty($info['data']['value']['coupon_list']) ){
                $coupon_list = [];
                foreach ($info['data']['value']['coupon_list'] as $k=>$v){
                    if(($v['count'] - $v['lead_count']) > 0){
                        $coupon_list[] = $v;
                    }
                }
                $info['data']['value']['coupon_list'] = $coupon_list;
            }
            return $this->response($info);
        }else{
            return $this->response($this->error());
        }
    }

}