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
			'name' => 'WEAPP_LIVE',
			'title' => '小程序直播',
			'type' => 'ADDON',
			'controller' => 'LiveInfo',
			'value' => '{"marginTop":"0","isShowAnchorInfo":"1","isShowLiveGood":"1"}',
			'sort' => '12007',
			'support_diy_view' => '',
			'max_count' => 1,
			'icon' => 'iconzhibojian'
		]
	],
	'link' => [
	    [
	        'name' => 'LIVE',
	        'title' => '直播',
	        'parent' => 'MARKETING_LINK',
	        'wap_url' => '',
	        'web_url' => '',
	        'sort' => 0,
	        'child_list' => [
	            [
	                'name' => 'LIVE_LIST',
	                'title' => '直播',
	                'parent' => '',
	                'wap_url' => '/otherpages/live/list/list',
	                'web_url' => '',
	                'sort' => 0
	            ]
	        ]
	    ]
	]
];