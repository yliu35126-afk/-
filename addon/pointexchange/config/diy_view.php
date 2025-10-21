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
    'util' => [],
    'link' => [
        [
            'name' => 'INTEGRAL',
            'title' => '积分商城',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'INTEGRAL_STORE',
                    'title' => '积分商城',
                    'parent' => '',
                    'wap_url' => '/promotionpages/point/list/list',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'STORE_MY_INTEGRAL',
                    'title' => '我的积分',
                    'parent' => '',
                    'wap_url' => '/otherpages/member/point/point',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'INTEGRAL_CONVERSION',
                    'title' => '积分兑换',
                    'parent' => '',
                    'wap_url' => '/promotionpages/point/order_list/order_list',
                    'web_url' => '',
                    'sort' => 0
                ]
            ]
        ]
    ],
];