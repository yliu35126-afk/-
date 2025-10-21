<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\supply\supply\controller;

use app\model\express\Config as ConfigModel;

/**
 * 配送
 * Class Express
 * @package addon\supply\supply\controller
 */
class Delivery extends BaseSupply
{


    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();

    }

    /**
     * 配送方式
     */
    public function express()
    {
        $config_model   = new ConfigModel();
        $config_result  = $config_model->getExpressConfig($this->supply_id);
        $express_config = $config_result["data"];
        $this->assign("express_config", $express_config);
        return $this->fetch("delivery/delivery");
    }

    /**
     * 物流开关配置
     * @return \multitype
     */
    public function modifyExpressStatus()
    {
        $config_model = new ConfigModel();
        if (request()->isAjax()) {
            $is_use = input("is_use", 0);
            $data   = array();
            $result = $config_model->setExpressConfig($data, $is_use, $this->supply_id);
            return $result;
        }
    }

    /**
     * 物流配置
     */
    public function expressConfig()
    {
        $config_model = new ConfigModel();
        if (request()->isAjax()) {
            $is_use = input("is_use", 0);
            $data   = array();
            $result = $config_model->setExpressConfig($data, $is_use, $this->supply_id);
            return $result;
        } else {
            $config_result = $config_model->getExpressConfig($this->supply_id);
            $config        = $config_result["data"];
            $this->assign("config", $config);
            return $this->fetch("delivery/store_config");
        }
    }
}