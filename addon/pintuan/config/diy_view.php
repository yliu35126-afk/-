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
            'name' => 'PINTUAN_LIST',
            'title' => '拼团',
            'type' => 'ADDON',
            'controller' => 'Pintuan',
            'value' => '{"sources" : "default", "categoryId" : 0, "goodsCount" : "6", "goodsId": [], "style": 1, "changeType": 1, "styleName": "风格一", "backgroundColor": "", "bgSelect": "blue", "marginTop": 0, "list": {"imageUrl": "public/static/img/diy_view/pintuan_style1_title.png","title": "拼团专区"}, "listMore": {"imageUrl": "","title": "好友都在拼"}, "titleTextColor": "#000", "defaultTitleTextColor": "#000", "moreTextColor": "#858585", "defaultMoreTextColor": "#858585"}',
            'sort' => '12003',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'iconpintuanfanli'
        ]
    ],
    'link' => [
        [
            'name' => 'PINTUAN',
            'title' => '拼团',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'PINTUAN_PREFECTURE',
                    'title' => '拼团专区',
                    'parent' => '',
                    'wap_url' => '/promotionpages/pintuan/list/list',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'MY_PINTUAN',
                    'title' => '我的拼团',
                    'parent' => '',
                    'wap_url' => '/promotionpages/pintuan/my_spell/my_spell',
                    'web_url' => '',
                    'sort' => 0
                ],
            ]
        ],
        [
            'name' => 'PINTUAN_GOODS',
            'title' => '拼团商品',
            'parent' => 'COMMODITY',
            'wap_url' => '',
            'web_url' => '',
            'child_list' => []
        ],
    ],
];