<?php
// 事件定义文件
return [
	'bind' => [

	],

	'listen' => [
		//展示活动
		'ShowPromotion' => [
			'addon\presale\event\ShowPromotion',
		],

		'PromotionType' => [
			'addon\presale\event\PromotionType',
		],

        //关闭预售
        'ClosePresale' => [
            'addon\presale\event\ClosePresale',
        ],

        //开启预售
        'OpenPresale' => [
            'addon\presale\event\OpenPresale',
        ],

        // 商品营销活动类型
        'GoodsPromotionType' => [
            'addon\presale\event\GoodsPromotionType',
        ],
        //定金订单关闭
        'CronDepositOrderClose' => [
            'addon\presale\event\DepositOrderClose'
        ],
        //定金支付回调地址
        'DepositOrderPayNotify' => [
            'addon\presale\event\DepositOrderPayNotify'
        ],
        //尾款支付回调地址
        'FinalOrderPayNotify' => [
            'addon\presale\event\FinalOrderPayNotify'
        ],
        //预售订单退款
        'AddonOrderRefund' => [
            'addon\presale\event\AddonOrderRefund'
        ],
        // 商品列表
        'GoodsListPromotion' => [
            'addon\presale\event\GoodsListPromotion',
        ],

        'MemberAccountFromType' => [
            'addon\presale\event\MemberAccountFromType',
        ],

        //订单营销类型
        'OrderPromotionType' => [
            'addon\presale\event\OrderPromotionType',
        ],

        //判断sku是否参与预售
        'IsJoinPresale' => [
            'addon\presale\event\IsJoinPresale',
        ],
	],

	'subscribe' => [
	],
];
