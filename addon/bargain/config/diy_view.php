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
    'util' => [
       [
           'name' => 'BARGAIN_LIST',
           'title' => '砍价',
           'type' => 'ADDON',
           'controller' => 'Bargain',
          'value' => '{"sources" : "default", "categoryId" : 0, "goodsCount" : "6", "styleName": "风格一", "goodsId": [], "style": 1, "changeType": 1, "backgroundColor": "", "bgSelect": "violet", "marginTop": 0, "list": {"imageUrl": "public/static/img/diy_view/bargain_style1_title.png","title": "砍价专区"}, "listMore": {"imageUrl": "","title": "更多"}, "titleTextColor": "#000", "defaultTitleTextColor": "#000", "moreTextColor": "#858585", "defaultMoreTextColor": "#858585"}',
           'sort' => '12000',
           'support_diy_view' => '',
           'max_count' => 1,
           'icon' => 'iconkanjia'
       ]
    ],
    'link' => [
        [
            'name' => 'BARGAIN',
            'title' => '砍价',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'BARGAIN_PREFECTURE',
                    'title' => '砍价专区',
                    'parent' => '',
                    'wap_url' => '/promotionpages/bargain/list/list',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'MY_BARGAIN',
                    'title' => '我的砍价',
                    'parent' => '',
                    'wap_url' => '/promotionpages/bargain/my_bargain/my_bargain',
                    'web_url' => '',
                    'sort' => 0
                ],
            ]
        ],
        [
            'name' => 'BARGAIN_GOODS',
            'title' => '砍价商品',
            'parent' => 'COMMODITY',
            'wap_url' => '',
            'web_url' => '',
            'child_list' => []
        ]
    ],
];