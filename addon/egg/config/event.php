<?php
// 事件定义文件
return [
	'bind' => [

	],

	'listen' => [
		//展示活动
		'ShowPromotion' => [
			'addon\egg\event\ShowPromotion',
		],

        'MemberAccountFromType' => [
            'addon\egg\event\MemberAccountFromType',
        ],

	],

	'subscribe' => [
	],
];
