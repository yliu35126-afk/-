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

        // 商品编辑后保存转盘价档绑定
        'GoodsEdit' => [
            'addon\turntable\event\GoodsEdit',
        ],

        // 抽奖结算：按供货价与价档价计算毛利及分润，生成供应商结算
        'turntable_settlement' => [
            'addon\turntable\event\TurntableSettlement',
        ],

        // 通用核销中心：扩展核销类型
        'VerifyType' => [
            'addon\turntable\event\VerifyType',
        ],

        // 通用核销中心：核销完成事件回写抽奖记录
        'Verify' => [
            'addon\turntable\event\Verify',
        ],

	],

	'subscribe' => [
	],
];
