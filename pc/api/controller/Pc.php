<?php


namespace addon\pc\api\controller;


use app\api\controller\BaseApi;
use app\model\web\Config as ConfigModel;

class Pc extends BaseApi
{
    /**
     * 获取分类配置
     * @return false|string
     */
    public function categoryconfig(){
        $config_model = new ConfigModel();
        $config_info = $config_model->getCategoryConfig();
        return $this->response($this->success($config_info[ 'data' ][ 'value' ]));
    }
}