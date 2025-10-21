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
            'name' => 'GROUPBUY_LIST',
            'title' => '团购',
            'type' => 'ADDON',
            'controller' => 'Groupbuy',
            'value' => '{"sources" : "default", "categoryId" : 0, "goodsCount" : "6", "goodsId": [], "style": 1, "styleName": "风格一", "changeType": 1, "backgroundColor": "", "bgSelect": "yellow", "marginTop": 0, "list": {"imageUrl": "public/static/img/diy_view/groupbuy_style1_title.png","title": "团购专区"}, "listMore": {"imageUrl": "","title": "查看更多"}, "titleTextColor": "#000", "defaultTitleTextColor": "#000", "moreTextColor": "#858585", "defaultMoreTextColor": "#858585"}',
            'sort' => '12001',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'icontuangou1'
        ]
    ],
    'link' => [
        [
            'name' => 'GROUPBUY',
            'title' => '团购',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'GROUPBUY_PREFECTURE',
                    'title' => '团购专区',
                    'parent' => '',
                    'wap_url' => '/promotionpages/groupbuy/list/list',
                    'web_url' => '',
                    'sort' => 0
                ]
            ]
        ],
        [
            'name' => 'GROUPBUY_GOODS',
            'title' => '团购商品',
            'parent' => 'COMMODITY',
            'wap_url' => '',
            'web_url' => '',
            'child_list' => []
        ]
    ],
];