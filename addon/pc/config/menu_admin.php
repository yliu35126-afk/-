<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PC_CONFIG',
        'title' => '电脑端设置',
        'url' => 'pc://admin/pc/floor',
        'is_show' => 1,
        'parent' => 'APPLET_ROOT',
        'picture' => 'app/admin/view/public/img/menu_icon/website_set.png',
        'picture_selected' => '',
        'sort' => 3,
        'child_list' => [
            [
                'name' => 'PC_INDEX_FLOOR',
                'title' => '首页楼层',
                'url' => 'pc://admin/pc/floor',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 2,
                'child_list' => [
                    [
                        'name' => 'PC_INDEX_FLOOR_EDIT',
                        'title' => '楼层编辑',
                        'url' => 'pc://admin/pc/editfloor',
                        'is_show' => 0,
                    ]
                ],
            ],
            [
                'name' => 'PC_HOT_SEARCH_WORDS',
                'title' => '热门搜索',
                'url' => 'pc://admin/pc/hotsearchwords',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 3,
                'child_list' => [],
            ],
            [
                'name' => 'PC_DEFAULT_SEARCH_WORDS',
                'title' => '默认搜索',
                'url' => 'pc://admin/pc/defaultsearchwords',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 4,
                'child_list' => [],
            ],
            [
                'name' => 'PC_FLOAT_LAYER',
                'title' => '首页浮层',
                'url' => 'pc://admin/pc/floatlayer',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 5,
                'child_list' => [],
            ],
            [
                'name' => 'PC_NAV_LIST',
                'title' => '导航设置',
                'url' => 'pc://admin/pc/navlist',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 6,
                'child_list' => [
                    [
                        'name' => 'PC_NAV_ADD',
                        'title' => '添加导航',
                        'url' => 'pc://admin/pc/addnav',
                        'is_show' => 0,
                        'sort' => 1,
                    ],
                    [
                        'name' => 'PC_NAV_EDIT',
                        'title' => '编辑导航',
                        'url' => 'pc://admin/pc/editnav',
                        'is_show' => 0,
                        'sort' => 2,
                    ],
                    [
                        'name' => 'PC_NAV_DELETE',
                        'title' => '编辑导航',
                        'url' => 'pc://admin/pc/deletenav',
                        'is_show' => 0,
                        'sort' => 3,
                    ],
                ],
            ],
            [
                'name' => 'PC_LINK_LIST',
                'title' => '友情链接',
                'url' => 'pc://admin/pc/linklist',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 7,
                'child_list' => [
                    [
                        'name' => 'PC_LINK_ADD',
                        'title' => '添加友情链接',
                        'url' => 'pc://admin/pc/addlink',
                        'is_show' => 0,
                        'sort' => 1,
                    ],
                    [
                        'name' => 'PC_LINK_EDIT',
                        'title' => '编辑友情链接',
                        'url' => 'pc://admin/pc/editlink',
                        'is_show' => 0,
                        'sort' => 2,
                    ],
                    [
                        'name' => 'PC_LINK_DELETE',
                        'title' => '删除友情链接',
                        'url' => 'pc://admin/pc/deletelink',
                        'is_show' => 0,
                        'sort' => 3,
                    ],
                ],
            ],
            [
                'name' => 'PC_CATEGORY_CONFIG',
                'title' => '首页分类',
                'url' => 'pc://admin/pc/category',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 8,
                'child_list' => []
            ]
        ],
    ],

    [
        'name' => 'PC_INDEX_DEPLOY',
        'title' => '电脑端部署',
        'url' => 'pc://admin/pc/deploy',
        'parent' => 'WEBSITE_DEPLOY',
        'is_show' => 1,
        'picture' => '',
        'picture_selected' => '',
        'sort' => 2,
    ]
];
