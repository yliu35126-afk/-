<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\shopcomponent\event;

/**
 * 小程序菜单
 */
class WeappMenu
{

    /**
     * 小程序菜单
     *
     * @return multitype:number unknown
     */
    public function handle()
    {
        return;
        $data = [
            'title'       => '微信视频号',
            'description' => '实现小程序与视频号的连接',
            'url'         => 'shopcomponent://admin/goods/lists',
            'icon'        => 'addon/shopcomponent/icon.png'
        ];
        return $data;
    }
}