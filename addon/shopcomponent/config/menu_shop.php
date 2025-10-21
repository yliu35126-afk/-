<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'SHOP_COMPONENT_ROOT',
        'title' => '视频号',
        'url' => 'shopcomponent://shop/goods/lists',
        'picture' => 'addon/shopcomponent/shop/view/public/img/live_new.png',
        'picture_selected' => 'addon/shopcomponent/shop/view/public/img/live_select.png',
        'parent' => 'PROMOTION_ROOT',
        'is_show' => 1,
        'sort' => 1,
        'child_list' => [
            [
                'name' => 'SHOP_COMPONENT_ACCESS',
                'title' => '视频号设置',
                'url' => 'shopcomponent://shop/video/access',
                'is_show' => 1,
                'sort' => 1,
                'child_list' => [

                ]
            ],
            [
                'name' => 'SHOP_COMPONENT_GOODS',
                'title' => '商品管理',
                'url' => 'shopcomponent://shop/goods/lists',
                'is_show' => 1,
                'sort' => 2,
                'child_list' => [

                ]
            ],
//            [
//                'name' => 'SHOP_COMPONENT_CATEGORY',
//                'title' => '类目管理',
//                'url' => 'shopcomponent://shop/category/lists',
//                'is_show' => 1,
//                'sort' => 2,
//                'child_list' => [
//
//                ]
//            ],
        ]
    ]
];
