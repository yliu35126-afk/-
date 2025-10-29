<?php
// 店铺端菜单设置（设备抽奖：设备线）
return [
    [
        'name' => 'PROMOTION_DEVICE_TURNTABLE',
        'title' => '设备抽奖（设备线）',
        'url' => 'device_turntable://shop/bind/lists',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 1,
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_DEVICE_TURNTABLE_BIND',
                'title' => '设备-价档绑定',
                'url' => 'device_turntable://shop/bind/lists',
                'is_show' => 1
            ]
        ]
    ]
];