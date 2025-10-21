<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_PRESALE',
        'title' => '商品预售',
        'url' => 'presale://admin/presale/lists',
        'parent' => 'PROMOTION_ROOT',
        'picture' => 'addon/presale/admin/view/public/img/distribution.png',
        'is_show' => 1,
        'sort' => 1,
        'child_list' => [

            [
                'name' => 'PROMOTION_PRESALE_LISTS',
                'title' => '预售列表',
                'url' => 'presale://admin/presale/lists',
                'is_show' => 1,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_PRESALE_DETAIL',
                        'title' => '预售详情',
                        'url' => 'presale://admin/presale/detail',
                        'is_show' => 0,
                    ]

                ]
            ],
            [
                'name' => 'PROMOTION_PRESALE_ORDER',
                'title' => '订单列表',
                'url' => 'presale://admin/order/lists',
                'is_show' => 1,
                'is_control' => 1,
                'sort' => 2,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_PRESALE_ORDER_DETAIL',
                        'title' => '订单详情',
                        'url' => 'presale://admin/order/detail',
                        'is_show' => 0,
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_PRESALE_ORDER_REFUND',
                'title' => '退款订单',
                'url' => 'presale://admin/refund/lists',
                'is_show' => 1,
                'sort' => 3,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_PRESALE_ORDER_REFUND_DETAIL',
                        'title' => '退款详情',
                        'url' => 'presale://admin/refund/detail',
                        'is_show' => 0,
                        'sort' => 1,
                    ],


                ]
            ]
        ]
    ],
];

