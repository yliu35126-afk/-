<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */
return [

    'template' => [],
    'util' => [],
    'link' => [
        [
            'name' => 'THEMATIC_ACTIVITIES',
            'title' => '专题活动',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'THEMATIC_ACTIVITIES_LIST',
                    'title' => '专题活动列表',
                    'parent' => '',
                    'wap_url' => '/promotionpages/topics/list/list',
                    'web_url' => '',
                    'sort' => 0
                ]
            ]
        ]
    ],

];