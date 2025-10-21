<?php
// 事件定义文件
return [
	'bind' => [

	],

	'listen' => [
		//展示活动
		'ShowPromotion' => [
			'addon\turntable\event\ShowPromotion',
		],

        'MemberAccountFromType' => [
            'addon\turntable\event\MemberAccountFromType',
        ],

	],

	'subscribe' => [
	],
];
