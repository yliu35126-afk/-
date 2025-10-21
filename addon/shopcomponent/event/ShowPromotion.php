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
 * 活动展示
 */
class ShowPromotion
{

    /**
     * 活动展示
     *
     * @return multitype:number unknown
     */
    public function handle()
    {
        return;
        $data = [
            'admin' => [
                [
                    //插件名称
                    'name'        => 'shopcomponent',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type'   => 'tool',
                    //展示主题
                    'title'       => '微信视频号',
                    //展示介绍
                    'description' => '实现小程序与视频号的连接',
                    //展示图标
                    'icon'        => 'addon/shopcomponent/icon.png',
                    //跳转链接
                    'url'         => 'shopcomponent://admin/goods/lists',
                ]
            ],
            'shop' => [
                [
                    //插件名称
                    'name'        => 'shopcomponent',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type'   => 'tool',
                    //展示主题
                    'title'       => '微信视频号',
                    //展示介绍
                    'description' => '实现小程序与视频号的连接',
                    //展示图标
                    'icon'        => 'addon/shopcomponent/icon.png',
                    //跳转链接
                    'url'         => 'shopcomponent://shop/goods/lists',
                ]
            ]
        ];
        return $data;
    }
}