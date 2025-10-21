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

namespace addon\shopwithdraw\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\shopwithdraw\model\Config as ConfigModel;
use addon\shopwithdraw\model\Withdraw as WithdrawModel;

/**
 * 会员提现
 */
class Config extends BaseAdmin
{
    /**
     * 会员提现配置
     */
    public function index()
    {
        $config_model = new ConfigModel();
        if (request()->isAjax()) {

            if (empty(input("transfer_type"))) {
                $transfer_type = "";
            } else {
                $transfer_type = implode(",", input("transfer_type"));
            }
            //订单提现
            $data = [
                'transfer_type' => $transfer_type,//转账方式,
            ];
            $this->addLog("设置店铺提现配置");
            $is_use = input("is_use", 0);//是否启用
            $res = $config_model->setConfig($data, $is_use);
            return $res;
        } else {

            //会员提现
            $config_result = $config_model->getConfig();
            $this->assign('config', $config_result[ 'data' ]);
            $withdraw_model = new WithdrawModel();
            $transfer_type_list = $withdraw_model->getTransferType();
            $this->assign("transfer_type_list", $transfer_type_list);
            return $this->fetch('config/index');
        }
    }

}