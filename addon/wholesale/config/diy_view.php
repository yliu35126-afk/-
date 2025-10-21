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
            'name' => 'WHOLESALE_LIST',
            'title' => '批发',
            'type' => 'ADDON',
            'controller' => 'Wholesale',
            'value' => '{"sources" : "default", "categoryId" : 0, "goodsCount" : "6", "goodsId": [], "style": 1, "styleName": "风格一", "changeType": 1, "backgroundColor": "", "bgSelect": "yellow", "marginTop": 0, "list": {"imageUrl": "public/static/img/diy_view/wholesale_style1_title.png","title": "批发"}, "listMore": {"imageUrl": "","title": "查看更多"}, "titleTextColor": "#000", "defaultTitleTextColor": "#000", "moreTextColor": "#858585", "defaultMoreTextColor": "#858585"}',
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 1,
			'icon' => 'iconpifajia'
        ]
    ],
	'link' => [
        [
            'name' => 'WHOLESALE',
            'title' => '批发',
            'parent' => 'MARKETING_LINK',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 0,
            'child_list' => [
                [
                    'name' => 'WHOLESALE_LIST',
                    'title' => '批发列表',
                    'parent' => '',
                    'wap_url' => '/promotionpages/wholesale/list/list',
                    'web_url' => '',
                    'sort' => 0
                ],
            ]
        ]
	],
];