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
return [
    'template' => [],
    'util' => [
        [
            'name' => 'ADMIN_PLATFORM_COUPON',
            'title' => '平台优惠券',
            'type' => 'ADDON',
            'controller' => 'AdminPlatformCoupon',
            'value' => '{ "sources": "default" ,"style": 1, "couponCount" : "6", "styleName": "风格一", "backgroundColor": "", "marginTop": 0, "status": 1, "couponIds": [] }',
            'sort' => '12009',
            'support_diy_view' => 'DIY_VIEW_INDEX',
            'max_count' => 0,
            'icon' => 'iconyouhuiquan'
        ],
    ],
    'link' => [
        [
            'name' => 'COUPON_LIST',
            'title' => '优惠券',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'COUPON_PREFECTURE',
                    'title' => '优惠券专区',
                    'parent' => '',
                    'wap_url' => '/otherpages/goods/coupon/coupon',
                    'web_url' => '',
                    'sort' => 0
                ]
            ]
        ]
    ],

];
