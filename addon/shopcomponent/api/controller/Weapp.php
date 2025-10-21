<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\shopcomponent\api\controller;

use app\api\controller\BaseApi;
use addon\weapp\model\Weapp as WeappModel;

class Weapp extends BaseApi
{
    /**
     * 检查场景值是否在支付校验范围内
     * @return mixed
     */
    public function sceneCheck(){
        $scene = $this->params['scene'] ?? '';
        $weapp_model = new WeappModel();
        $result = $weapp_model->sceneCheck($scene);
        return $this->response($result);
    }
}