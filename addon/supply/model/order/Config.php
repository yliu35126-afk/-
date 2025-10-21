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

namespace addon\supply\model\order;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;

/**
 * 订单交易设置
 */
class Config extends BaseModel
{
    /**
     * 获取供应商订单交易设置
     */
    public function getOrderTradeConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'SUPPLY_ORDER_TRADE_CONFIG' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'auto_close' => 30,//订单未付款自动关闭时间 数字 单位(天)
                'auto_take_delivery' => 14,//订单发货后自动收货时间 数字 单位(天)
                'auto_complete' => 7,//订单收货后自动完成时间 数字 单位(天)
            ];
        }
        return $res;
    }

    /**
     * 设置供应商订单交易设置
     */
    public function setOrderTradeConfig($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '供应商订单交易设置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'SUPPLY_ORDER_TRADE_CONFIG' ] ]);
        return $res;
    }

}