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

namespace addon\store\model;


use app\model\system\Config as ConfigModel;
use app\model\BaseModel;
use Carbon\Carbon;
use app\model\system\Cron;

/**
 * 店铺设置信息
 */
class Config extends BaseModel
{

    /**
     * 获取商家转账设置
     */
    public function getStoreWithdrawConfig($site_id)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'SHOP_STORE_WITHDRAW' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            //默认数据管理
            $res[ 'data' ][ 'value' ] = [
                'period_type' => 3           //转账周期类型1.天  2. 周  3. 月
            ];
        }
        return $res;
    }

    /**
     * 设置商家转账设置
     */
    public function setStoreWithdrawConfig($site_id, $data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '门店结算设置', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'SHOP_STORE_WITHDRAW' ] ]);

        $cron = new Cron();
        switch ( $data[ 'period_type' ] ) {
            case 1://天

                $date = strtotime(date('Y-m-d 00:00:00'));
                $execute_time = strtotime('+1day', $date);
                break;
            case 2://周

                $execute_time = Carbon::parse('next monday')->timestamp;
                break;
            case 3://月

                $execute_time = Carbon::now()->addMonth()->firstOfMonth()->timestamp;
                break;
        }
        $cron->deleteCron([ [ 'event', '=', 'StoreWithdrawPeriodCalc' ], [ 'relate_id', '=', $site_id ] ]);
        $cron->addCron('2', '1', '门店周期结算', 'StoreWithdrawPeriodCalc', $execute_time, $site_id, $data[ 'period_type' ]);
        return $res;
    }

    /**
     * addSettlementCron 添加门店结算计划任务 默认为3 - 月
     */
    public function addSettlementCron($site_id)
    {
        $config = new ConfigModel();
        $config->setConfig([ 'period_type' => 3 ], '门店结算设置', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'SHOP_STORE_WITHDRAW' ] ]);
        $cron = new Cron();
        $execute_time = Carbon::now()->addMonth()->firstOfMonth()->timestamp;
        $cron->deleteCron([ [ 'event', '=', 'StoreWithdrawPeriodCalc' ], [ 'relate_id', '=', $site_id ] ]);
        $res = $cron->addCron('2', '1', '门店周期结算', 'StoreWithdrawPeriodCalc', $execute_time, $site_id, 3);
        return $this->success($res);
    }

}