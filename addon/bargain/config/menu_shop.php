<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [

    [
        'name' => 'PROMOTION_BARGAIN',
        'title' => '砍价',
        'url' => 'bargain://shop/bargain/lists',
        'parent' => 'PROMOTION_CENTER',
        'is_show' => 0,
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_BARGAIN_LIST',
                'title' => '砍价商品',
                'url' => 'bargain://shop/bargain/lists',
                'parent' => 'PROMOTION_BARGAIN',
                'is_show' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_BARGAIN_ADD',
                        'title' => '添加活动',
                        'url' => 'bargain://shop/bargain/add',
                        'sort' => 1,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_BARGAIN_EDIT',
                        'title' => '编辑活动',
                        'url' => 'bargain://shop/bargain/edit',
                        'sort' => 2,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_BARGAIN_DETAIL',
                        'title' => '活动详情',
                        'url' => 'bargain://shop/bargain/detail',
                        'sort' => 3,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_BARGAIN_DELETE',
                        'title' => '删除活动',
                        'url' => 'bargain://shop/bargain/delete',
                        'sort' => 4,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_BARGAIN_FINISH',
                        'title' => '结束活动',
                        'url' => 'bargain://shop/bargain/finish',
                        'sort' => 5,
                        'is_show' => 0
                    ]

                ]
            ],
            [
                'name' => 'PROMOTION_BARGAIN_LAUNCH',
                'title' => '砍价列表',
                'url' => 'bargain://shop/bargain/launchlist',
                'parent' => 'PROMOTION_BARGAIN',
                'is_show' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_BARGAIN_LAUNCH_DETAIL',
                        'title' => '砍价详情',
                        'url' => 'bargain://shop/bargain/launchdetail',
                        'sort' => 1,
                        'is_show' => 0
                    ]

                ]
            ],
        ]
    ],

];