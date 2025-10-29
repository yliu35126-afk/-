<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_ADMIN_TURNTABLE',
        'title' => '设备抽奖',
        'url' => 'turntable://admin/bind/lists',
        // 归类至店铺营销，便于与活动线区分
        'parent' => 'PROMOTION_SHOP',
        'is_show' => 1,
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_ADMIN_TURNTABLE_LIST',
                'title' => '站内活动（会员版）',
                'url' => 'turntable://admin/turntable/lists',
                'is_show' => 0,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_ADD',
                        'title' => '添加活动',
                        'url' => 'turntable://admin/turntable/add',
                        'sort' => 1,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_EDIT',
                        'title' => '编辑活动',
                        'url' => 'turntable://admin/turntable/edit',
                        'sort' => 1,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_DETAIL',
                        'title' => '活动详情',
                        'url' => 'turntable://admin/turntable/detail',
                        'sort' => 1,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_DELETE',
                        'title' => '删除活动',
                        'url' => 'turntable://admin/turntable/delete',
                        'sort' => 1,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_FINISH',
                        'title' => '关闭活动',
                        'url' => 'turntable://admin/turntable/finish',
                        'sort' => 1,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_RECORD',
                        'title' => '抽奖记录',
                        'url' => 'turntable://admin/record/lists',
                        'sort' => 1,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_REWARD',
                        'title' => '奖励单管理',
                        'url' => 'turntable://admin/reward/lists',
                        'sort' => 2,
                        'is_show' => 1
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_ADMIN_TURNTABLE_BOARD',
                'title' => '抽奖盘管理',
                'url' => 'turntable://admin/board/lists',
                'is_show' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_BOARD_ADD',
                        'title' => '新增抽奖盘',
                        'url' => 'turntable://admin/board/add',
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_BOARD_EDIT',
                        'title' => '编辑抽奖盘',
                        'url' => 'turntable://admin/board/edit',
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_BOARD_DELETE',
                        'title' => '删除抽奖盘',
                        'url' => 'turntable://admin/board/delete',
                        'is_show' => 0
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_ADMIN_TURNTABLE_SLOT',
                'title' => '格子管理',
                'url' => 'turntable://admin/slot/lists',
                'is_show' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_SLOT_ADD',
                        'title' => '新增格子',
                        'url' => 'turntable://admin/slot/add',
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_SLOT_EDIT',
                        'title' => '编辑格子',
                        'url' => 'turntable://admin/slot/edit',
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_SLOT_DELETE',
                        'title' => '删除格子',
                        'url' => 'turntable://admin/slot/delete',
                        'is_show' => 0
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_ADMIN_TURNTABLE_PRICETIER',
                'title' => '价档管理',
                'url' => 'turntable://admin/pricetier/lists',
                'is_show' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_PRICETIER_ADD',
                        'title' => '新增价档',
                        'url' => 'turntable://admin/pricetier/add',
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_PRICETIER_EDIT',
                        'title' => '编辑价档',
                        'url' => 'turntable://admin/pricetier/edit',
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_PRICETIER_DELETE',
                        'title' => '删除价档',
                        'url' => 'turntable://admin/pricetier/delete',
                        'is_show' => 0
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_ADMIN_TURNTABLE_DEVICE',
                'title' => '设备管理',
                'url' => 'turntable://admin/device/lists',
                'is_show' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_DEVICE_ADD',
                        'title' => '新增设备',
                        'url' => 'turntable://admin/device/add',
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_DEVICE_EDIT',
                        'title' => '编辑设备',
                        'url' => 'turntable://admin/device/edit',
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_DEVICE_DELETE',
                        'title' => '删除设备',
                        'url' => 'turntable://admin/device/delete',
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_ADMIN_TURNTABLE_DEVICE_PRICE_BIND',
                        'title' => '设备-价档绑定',
                        'url' => 'turntable://admin/bind/lists',
                        'is_show' => 1,
                        'child_list' => [
                            [
                                'name' => 'PROMOTION_ADMIN_TURNTABLE_DEVICE_PRICE_BIND_ADD',
                                'title' => '新增绑定',
                                'url' => 'turntable://admin/bind/add',
                                'is_show' => 1
                            ],
                            [
                                'name' => 'PROMOTION_ADMIN_TURNTABLE_DEVICE_PRICE_BIND_EDIT',
                                'title' => '编辑绑定',
                                'url' => 'turntable://admin/bind/edit',
                                'is_show' => 0
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_ADMIN_TURNTABLE_PROFIT',
                'title' => '分润账单',
                'url' => 'turntable://admin/profit/lists',
                'is_show' => 1
            ],
            [
                'name' => 'PROMOTION_ADMIN_TURNTABLE_STATS',
                'title' => '统计大盘',
                'url' => 'turntable://admin/dashboard/index',
                'is_show' => 1
            ]
        ]
    ],
];