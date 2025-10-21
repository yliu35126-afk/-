<?php
// 事件定义文件
return [
    'bind'      => [
        
    ],

    'listen'    => [
        //展示活动
        'ShowPromotion' => [
            'addon\wholesale\event\ShowPromotion',
        ],
        
        'PromotionType' => [
            'addon\wholesale\event\PromotionType',
        ],


        // 商品列表
        'GoodsListPromotion' => [
            'addon\wholesale\event\GoodsListPromotion',
        ],
        // 商品营销活动类型
        'GoodsPromotionType' => [
            'addon\wholesale\event\GoodsPromotionType',
        ],
        // 订单营销活动类型
        'OrderPromotionType' => [
            'addon\wholesale\event\OrderPromotionType',
        ],
        // 商品营销活动信息
        'GoodsPromotion'     => [
            'addon\wholesale\event\GoodsPromotion',
        ],
    ],

    'subscribe' => [
    ],
];
