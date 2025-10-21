<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'ADDON_SITEMESSAGE_LIST',
        'title' => '会员群发',
        'url' => 'sitemessage://admin/sitemessage/lists',
        'parent' => 'MEMBER_ROOT',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 15,
        'child_list' => [
            [
                'name' => 'ADDON_SITEMESSAGE_DETAIL',
                'title' => '群发详情',
                'url' => 'sitemessage://admin/sitemessage/detail',
                'is_show' => 0,
                'sort' => 1,
            ],
            [
                'name' => 'ADDON_SITEMESSAGE_ADD',
                'title' => '新建群发',
                'url' => 'sitemessage://admin/sitemessage/add',
                'is_show' => 0,
                'sort' => 1,
            ],
            [
                'name' => 'ADDON_SITEMESSAGE_MESSAGE_EDIT',
                'title' => '站内信修改',
                'url' => 'sitemessage://admin/message/edit',
                'is_show' => 0
            ],
        ]
    ]
];
