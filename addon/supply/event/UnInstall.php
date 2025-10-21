<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\supply\event;

use app\model\system\Cron;
use addon\supply\model\web\Adv;
use addon\supply\model\web\AdvPosition;
use app\model\system\Menu;

/**
 * 应用卸载
 */
class UnInstall
{
	/**
	 * 执行卸载
	 */
	public function handle()
	{
        $cron_model = new Cron();
        $result = $cron_model->deleteCron([ [ 'event', '=', 'CronSupplyClose' ] ] );
        if($result['code'] < 0)
            return $result;


        $result = $cron_model->deleteCron([ [ 'event', '=', 'SupplyPeriodCalc' ] ] );
        if($result['code'] < 0)
            return $result;

        //删除广告以及广告位
        $adv_position_data = [
            [
                'ap_name' => '供货市场首页广告位',
                'keyword' => 'NS_SUPPLY_SHOP_INDEX',
                'ap_intro' => '',
                'ap_width' => '763',
                'ap_height' => '430',
                'default_content' => '',
                'ap_background_color' => '#FFFFFF',
                'adv' => [
                    [
                        'adv_title' => '广告一',
                        'adv_url' => '',
                        'adv_image' => 'upload/default/supply/adv/adv1.png',
                        'background' => '#FFF'
                    ]
                ]
            ]
        ];

        $adv_position_model = new AdvPosition();
        $adv_model = new Adv();
        foreach ($adv_position_data as $k => $v) {
            $position_info_result = $adv_position_model->getAdvPositionInfo([['keyword', '=', $v['keyword']]], 'ap_id');
            $position_info = $position_info_result['data'];
            if(!empty($position_info)){
                $adv_model->deleteAdv([['ap_id', '=', $position_info['ap_id']]]);
            }
            //删除广告位
            $adv_position_model->deleteAdvPosition([['keyword', '=', $v['keyword']]]);
        }

        //菜单
        $menu = new Menu();
        //删除菜单数据
        $menu->deleteMenu([['app_module', '=', 'supply']]);

        //删除安装的应用
        return success();
	}
}