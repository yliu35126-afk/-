<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [

    [
        'name'       => 'PROMOTION_GRAB_ROOT',
        'title'      => '商品采集',
        'url'        => 'goodsgrab://shop/goodsgrab/lists',
        'parent'     => 'GOODS_MANAGE',
        'is_show'    => 0,
        'sort'       => 3,
        'child_list' => [
            [
                'name'    => 'PROMOTION_GRAB_DETAIL',
                'title'   => '采集详情',
                'url'     => 'goodsgrab://shop/goodsgrab/detail',
                'sort'    => 1,
                'is_show' => 0
            ],
            [
                'name'    => 'PROMOTION_GRAB',
                'title'   => '商品采集',
                'url'     => 'goodsgrab://shop/goodsgrab/collect',
                'sort'    => 1,
                'is_show' => 0
            ],
            [
                'name'       => 'PROMOTION_GRAB_CONFIG',
                'title'      => '采集配置',
                'url'        => 'goodsgrab://shop/goodsgrab/config',
                'sort'    => 1,
                'is_show' => 0
            ],
        ]
    ],

];