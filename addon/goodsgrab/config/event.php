<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion' => [
            'addon\goodsgrab\event\ShowPromotion',
        ],

        'PromotionType'      => [
            'addon\goodsgrab\event\PromotionType',
        ],

    ],

    'subscribe' => [
    ],
];
