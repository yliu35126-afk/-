<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'NIU_SMS_CONFIG',
        'title' => '牛云短信配置',
        'url' => 'niusms://admin/sms/index',
        'parent' => 'SMS_MANAGE',
        'is_show' => 0,
        'is_control' => 1,
        'is_icon' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 1,
        'child_list' => [
            [
                'name' => 'NIU_SMS_LOGIN',
                'title' => '账户登录',
                'url' => 'niusms://admin/sms/login',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 1,
            ],
            [
                'name' => 'NIU_SMS_REGISTER',
                'title' => '账户注册',
                'url' => 'niusms://admin/sms/register',
                'parent' => '',
                'is_show' => 1,
                'picture' => '',
                'picture_selected' => '',
                'sort' => 2,
            ],
        ],
    ],
    [
        'name' => 'NIU_MESSAGE_SMS_EDIT',
        'title' => '编辑牛云短信模板',
        'url' => 'niusms://admin/message/edit',
        'parent' => 'MESSAGE_LISTS',
        'is_show' => 0,
        'picture' => '',
        'picture_select' => '',
        'sort' => 1,
        'child_list' => [],
    ],
];
