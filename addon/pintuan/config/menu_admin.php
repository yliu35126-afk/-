<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [

    [
        'name' => 'PROMOTION_PINTUAN',
        'title' => '拼团',
        'url' => 'pintuan://admin/pintuan/lists',
        'parent' => 'PROMOTION_SHOP',
        'is_show' => 0,
        'sort' => 100,
        'child_list' => [
            [
                'name' => 'PROMOTION_PINTUAN_LIST',
                'title' => '拼团商品',
                'url' => 'pintuan://admin/pintuan/lists',
                'is_show' => 1,
            ],
            [
                'name' => 'PROMOTION_PINTUAN_DETAIL',
                'title' => '商品详情',
                'url' => 'pintuan://admin/pintuan/detail',
                'sort' => 1,
                'is_show' => 0
            ],
            [
                'name' => 'PROMOTION_PINTUAN_DELETE',
                'title' => '删除商品',
                'url' => 'pintuan://admin/pintuan/delete',
                'sort' => 1,
                'is_show' => 0
            ],
            [
                'name' => 'PROMOTION_PINTUAN_INVALID',
                'title' => '结束活动',
                'url' => 'pintuan://admin/pintuan/invalid',
                'sort' => 1,
                'is_show' => 0
            ],
            [
                'name' => 'PROMOTION_PINTUAN_GROUP_ORDER',
                'title' => '拼团组订单列表',
                'url' => 'pintuan://admin/pintuan/grouporder',
                'sort' => 1,
                'is_show' => 0
            ],
            [
                'name' => 'PROMOTION_PINTUAN_GROUP',
                'title' => '拼团列表',
                'url' => 'pintuan://admin/pintuan/group',
                'parent' => 'PROMOTION_PINTUAN',
                'is_show' => 1,
                'child_list' => []
            ],

        ]
    ],

];