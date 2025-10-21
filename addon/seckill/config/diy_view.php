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
	        'name' => 'SECKILL_LIST',
	        'title' => '秒杀',
	        'type' => 'ADDON',
	        'controller' => 'Seckill',
	        'value' => '{style: 1, "backgroundColor": "", "marginTop": 0, "styleName": "风格一", "changeType": 1, "bgSelect": "red", "paddingLeftRight": 0, "isShowGoodsName": 1, "isShowGoodsDesc": 0, "isShowGoodsPrice": 1, "isShowGoodsPrimary": 1, "isShowGoodsStock": 0, "list": {"imageUrl": "public/static/img/diy_view/seckill_style1_title.png","title": "秒杀专区"}, "listMore": {"imageUrl": "","title": "更多秒杀"}, "titleTextColor": "#000", "defaultTitleTextColor": "#000", "moreTextColor": "#858585", "defaultMoreTextColor": "#858585"}',
	        'sort' => '12004',
	        'support_diy_view' => '',
	        'max_count' => 1,
	        'icon' => 'iconmiaosha1'
	    ]
	],
	'link' => [
	    [
	        'name' => 'SECKILL',
	        'title' => '秒杀',
	        'parent' => 'MARKETING_LINK',
	        'wap_url' => '',
	        'web_url' => '',
	        'sort' => 0,
	        'child_list' => [
	            [
	                'name' => 'SECKILL_PREFECTURE',
	                'title' => '秒杀专区',
	                'parent' => '',
	                'wap_url' => '/promotionpages/seckill/list/list',
	                'web_url' => '',
	                'sort' => 0
	            ]
	        ]
	    ]
	],
];