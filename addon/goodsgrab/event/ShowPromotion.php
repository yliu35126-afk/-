<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\goodsgrab\event;

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
        $data = [
            'shop' => [
                [
                    //插件名称
                    'name'        => 'goodsgrab',
                    //店铺端展示分类  shop:营销活动   member:互动营销
                    'show_type'   => 'tool',
                    //展示主题
                    'title'       => '商品采集',
                    //展示介绍
                    'description' => '商品采集',
                    //展示图标
                    'icon'        => 'addon/goodsgrab/icon.png',
                    //跳转链接
                    'url'         => 'goodsgrab://shop/goodsgrab/lists',
                ]
            ]

        ];
        return $data;
    }
}