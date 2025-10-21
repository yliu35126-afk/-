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

namespace addon\electronicsheet\shop\controller;

use app\shop\controller\BaseShop;
use addon\electronicsheet\model\ExpressElectronicsheet as ExpressElectronicsheetModel;


class Config extends BaseShop
{

    /*
     *  电子面单设置
     */
    public function config()
    {
        $config = new ExpressElectronicsheetModel();
        if (request()->isAjax()) {

            $data = [
                'site_id' => $this->site_id,
                'type' => input('type', 'kdniao'),

                'kdniao_user_id' => input('kdniao_user_id', ''),
                'kdniao_api_key' => input('kdniao_api_key', ''),
                'kdniao_port' => input('kdniao_port', ''),

                'cainiao_token' => input('cainiao_token', ''),
                'cainiao_ip' => input('cainiao_ip', ''),
            ];

            return $config->setElectronicsheetConfig($data);
        } else {

            $res = $config->getElectronicsheetConfig($this->site_id);
            $this->assign('config_info', $res[ 'data' ][ 'value' ]);
            return $this->fetch('config/config');
        }
    }

}