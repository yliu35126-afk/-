<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name'       => 'SERVICER',
        'title'      => '客服管理',
        'url'        => 'servicer://shop/servicer/index',
        'parent'     => 'PROMOTION_ROOT',
        'picture'    => 'addon/servicer/shop/view/public/img/servicer.png',
        'is_show'    => 1,
        'is_control' => 1,
        'is_icon'    => 0,
        'sort'       => 100,
        'child_list' => [
            [
                'name'       => 'SERVICER_MANAGER',
                'title'      => '客服列表',
                'url'        => 'servicer://shop/servicer/index',
                'is_show'    => 1,
                'is_control' => 1,
                'is_icon'    => 0,
                'sort'       => 1,
                'child_list' => [
                    [
                        'name'       => 'SERVICE_MANAGER_ADD',
                        'title'      => '添加客服',
                        'url'        => 'servicer://shop/servicer/add',
                        'is_show'    => 1,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 100,
                    ],
                    [
                        'name'       => 'SERVICE_MANAGER_EDIT',
                        'title'      => '编辑客服',
                        'url'        => 'servicer://shop/servicer/edit',
                        'is_show'    => 1,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 100,
                    ],
                    [
                        'name'       => 'SERVICE_MANAGER_DELETE',
                        'title'      => '删除客服',
                        'url'        => 'servicer://shop/servicer/delete',
                        'is_show'    => 0,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 100,
                    ],

                ]
            ],
            [
                'name'       => 'SERVICER_GROUP',
                'title'      => '客服分组',
                'url'        => 'servicer://shop/group/index',
                'is_show'    => 1,
                'is_control' => 1,
                'is_icon'    => 0,
                'sort'       => 2,
                'child_list' => [
                    [
                        'name'       => 'SERVICER_GROUP_ADD',
                        'title'      => '添加分组',
                        'url'        => 'servicer://shop/group/add',
                        'is_show'    => 1,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 100,
                    ],
                    [
                        'name'       => 'SERVICER_GROUP_EDIT',
                        'title'      => '编辑分组',
                        'url'        => 'servicer://shop/group/edit',
                        'is_show'    => 1,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 100,
                    ],
                    [
                        'name'       => 'SERVICER_GROUP_DELETE',
                        'title'      => '删除分组',
                        'url'        => 'servicer://shop/group/delete',
                        'is_show'    => 0,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 100,
                    ],

                ]
            ],
            [
                'name'       => 'SERVICER_KEYWORD_REPLY',
                'title'      => '关键词回复',
                'url'        => 'servicer://shop/keyword/index',
                'is_show'    => 1,
                'is_control' => 1,
                'is_icon'    => 0,
                'sort'       => 4,
                'child_list' => [
                    [
                        'name'       => 'SERVICER_KEYWORD_REPLY_ADD',
                        'title'      => '添加关键词',
                        'url'        => 'servicer://shop/keyword/add',
                        'is_show'    => 1,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 1,
                    ],
                    [
                        'name'       => 'SERVICER_KEYWORD_REPLY_EDIT',
                        'title'      => '编辑关键词',
                        'url'        => 'servicer://shop/keyword/edit',
                        'is_show'    => 1,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 2,
                    ],
                    [
                        'name'       => 'SERVICER_KEYWORD_REPLY_DELETE',
                        'title'      => '删除关键词',
                        'url'        => 'servicer://shop/keyword/delete',
                        'is_show'    => 0,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 3,
                    ],

                ]
            ],
            [
                'name'       => 'SERVICER_FAST_REPLY',
                'title'      => '快捷回复',
                'url'        => 'servicer://shop/fast/index',
                'is_show'    => 1,
                'is_control' => 1,
                'is_icon'    => 0,
                'sort'       => 5,
                'child_list' => [
                    [
                        'name'       => 'SERVICER_KEYWORD_FAST_ADD',
                        'title'      => '添加快捷回复',
                        'url'        => 'servicer://shop/fast/add',
                        'is_show'    => 1,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 1,
                    ],
                    [
                        'name'       => 'SERVICER_KEYWORD_FAST_EDIT',
                        'title'      => '编辑快捷回复',
                        'url'        => 'servicer://shop/fast/edit',
                        'is_show'    => 1,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 2,
                    ],
                    [
                        'name'       => 'SERVICER_KEYWORD_FAST_DELETE',
                        'title'      => '删除快捷回复',
                        'url'        => 'servicer://shop/fast/delete',
                        'is_show'    => 0,
                        'is_control' => 1,
                        'is_icon'    => 0,
                        'sort'       => 3,
                    ],

                ]
            ],
        ]
    ]

];
