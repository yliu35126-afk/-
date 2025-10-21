<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'MOBILE_SHOP_CONFIG',
        'title' => '商家端设置',
        'url' => 'mobileshop://admin/config/deploy',
        'parent' => 'APPLET_ROOT',
        'is_show' => 1,
        'picture' => 'app/admin/view/public/img/menu_icon/website_set.png',
        'picture_selected' => '',
        'sort' => 4,
        'child_list' => [
            [
                'name' => 'MOBILE_SHOP_DEPLOY',
                'title' => '网站部署',
                'url' => 'mobileshop://admin/config/deploy',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 1,
                'child_list' => [],
            ],
            [
                'name' => 'MOBILE_SHOP_WEAPP_CONFIG',
                'title' => '小程序设置',
                'url' => 'mobileshop://admin/config/weapp',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 1,
                'child_list' => [],
            ],
        ]
    ]
];
