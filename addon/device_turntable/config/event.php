<?php
// 设备抽奖插件事件注册
return [
    'bind' => [
    ],
    'listen' => [
        // 商家中心展示“设备线”推广卡片
        'ShowPromotion' => [
            'addon\\device_turntable\\event\\ShowPromotion',
        ],
    ],
    'subscribe' => [
    ],
];