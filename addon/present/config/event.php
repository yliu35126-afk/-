<?php
// 事件定义文件
return [
    'bind'      => [
        
    ],

    'listen'    => [
        //展示活动
        'ShowPromotion' => [
            'addon\present\event\ShowPromotion',
        ],
        
        'PromotionType' => [
            'addon\present\event\PromotionType',
        ],

        //关闭赠品
        'ClosePresent' => [
            'addon\present\event\ClosePresent',
        ],

        //开启赠品
        'OpenPresent' => [
            'addon\present\event\OpenPresent',
        ],

        // 商品列表
        'GoodsListPromotion' => [
            'addon\present\event\GoodsListPromotion',
        ],
        // 商品营销活动信息
        'GoodsPromotion'     => [
            'addon\present\event\GoodsPromotion',
        ],
        // 订单营销活动类型
        'OrderPromotionType' => [
            'addon\present\event\OrderPromotionType',
        ],
        // 商品营销活动类型
        'GoodsPromotionType' => [
            'addon\present\event\GoodsPromotionType',
        ],
    ],

    'subscribe' => [
    ],
];
