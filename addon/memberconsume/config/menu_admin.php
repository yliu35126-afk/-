<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'MEMBER_CONSUME',
        'title' => '会员消费',
        'url' => 'memberconsume://admin/config/index',
        'parent' => 'PROMOTION_MEMBER',
        'is_show' => 0,
        'is_control' => 0,
        'is_icon' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'MEMBER_CONSUME_CONFIG',
                'title' => '会员消费',
                'url' => 'memberconsume://admin/config/index',
                'is_show' => 0,
                'is_control' => 1,
                'is_icon' => 0,
                'sort' => 100,
            ]
        ]
    ],
];
