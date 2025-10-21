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

namespace addon\supply\admin\controller;

use addon\supply\model\Config as ConfigModel;
use app\admin\controller\BaseAdmin;

/**
 * 供应商管理
 */
class Withdraw extends BaseAdmin
{

    /**
     * 供应商配置
     */
    public function config()
    {
        $config_model = new ConfigModel();
        if (request()->isAjax()) {
            $data = [
                'min_withdraw' => input('min_withdraw', 0),
                'max_withdraw' => input('max_withdraw', 0),
            ];
            $this->addLog("修改供应商提现配置");
            $res = $config_model->setSupplyWithdrawConfig($data);
            return $res;
        } else {
            $copyright = $config_model->getSupplyWithdrawConfig();
            $this->assign('config', $copyright['data']['value']);
            return $this->fetch('withdraw/config');
        }
    }
}
