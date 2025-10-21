<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_PRESALE',
        'title' => '商品预售',
        'url' => 'presale://shop/presale/lists',
        'parent' => 'PROMOTION_ROOT',
        'picture' => 'addon/presale/shop/view/public/img/distribution.png',
        'is_show' => 1,
        'sort' => 1,
        'child_list' => [

            [
                'name' => 'PROMOTION_PRESALE_LISTS',
                'title' => '预售列表',
                'url' => 'presale://shop/presale/lists',
                'is_show' => 1,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_PRESALE_DETAIL',
                        'title' => '预售详情',
                        'url' => 'presale://shop/presale/detail',
                        'is_show' => 0,
                    ],
                    [
                        'name' => 'PROMOTION_PRESALE_ADD',
                        'title' => '添加预售',
                        'url' => 'presale://shop/presale/add',
                        'is_show' => 0,
                    ],
                    [
                        'name' => 'PROMOTION_PRESALE_EDIT',
                        'title' => '编辑预售',
                        'url' => 'presale://shop/presale/edit',
                        'sort' => 2,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_PRESALE_DELETE',
                        'title' => '删除预售',
                        'url' => 'presale://shop/presale/delete',
                        'sort' => 3,
                        'is_show' => 0
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_PRESALE_ORDER',
                'title' => '订单列表',
                'url' => 'presale://shop/order/lists',
                'is_show' => 1,
                'is_control' => 1,
                'sort' => 2,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_PRESALE_ORDER_DETAIL',
                        'title' => '订单详情',
                        'url' => 'presale://shop/order/detail',
                        'is_show' => 0,
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_PRESALE_ORDER_REFUND',
                'title' => '退款订单',
                'url' => 'presale://shop/refund/lists',
                'is_show' => 1,
                'sort' => 3,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_PRESALE_ORDER_REFUND_DETAIL',
                        'title' => '退款详情',
                        'url' => 'presale://shop/refund/detail',
                        'is_show' => 0,
                        'sort' => 1,
                    ],
                    [
                        'name' => 'PROMOTION_PRESALE_ORDER_REFUND_AGREE',
                        'title' => '同意退款',
                        'url' => 'presale://shop/refund/agree',
                        'is_show' => 0,
                        'sort' => 2,
                    ],
                    [
                        'name' => 'PROMOTION_PRESALE_ORDER_REFUND_REFUSE',
                        'title' => '拒绝退款',
                        'url' => 'presale://shop/refund/refuse',
                        'is_show' => 0,
                        'sort' => 3,
                    ],

                ]
            ]
        ]
    ],

];