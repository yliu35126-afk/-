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

namespace addon\store\shop\controller;

use addon\store\model\StoreAccount as StoreAccountModel;
use app\shop\controller\BaseShop;

/**
 * 门店设置控制器
 */
class Config extends BaseShop
{

    /**
     * 门店结算周期配置
     */
    public function index()
    {
        $store_account_model = new StoreAccountModel();
        if (request()->isAjax()) {
            $period_type = input('period_type', 3);
            if (!in_array($period_type, $store_account_model->period_types)) {
                return error(-1, '参数错误');
            }
            $data = [ 'period_type' => $period_type ];
            $res = $store_account_model->setStoreWithdrawConfig($this->site_id, $data);
            return $res;
        }

        $config_info = $store_account_model->getStoreWithdrawConfig($this->site_id);
        $this->assign('config_info', $config_info[ 'data' ]);
        return $this->fetch("config/index");
    }
}