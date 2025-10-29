<?php
// +----------------------------------------------------------------------
// | 平台端菜单设置（插件）
// +---------------------------------------------------------------------
return [
    [
        'name' => 'ADDON_AGENT_ROOT',
        'title' => '代理管理',
        'url' => 'agent://admin/agent/lists',
        // 归类到会员管理，避免顶栏难找
        'parent' => 'MEMBER_ROOT',
        'is_show' => 1,
        'is_control' => 0,
        'is_icon' => 0,
        'sort' => 21,
        'child_list' => [
            [
                'name' => 'ADDON_AGENT_LISTS',
                'title' => '代理列表',
                'url' => 'agent://admin/agent/lists',
                'is_show' => 1,
                'sort' => 1,
            ],
            [
                'name' => 'ADDON_AGENT_MAP',
                'title' => '地图分布',
                'url' => 'agent://admin/agent/map',
                'is_show' => 1,
                'sort' => 2,
            ],
            [
                'name' => 'ADDON_AGENT_LEDGER',
                'title' => '代理流水',
                'url' => 'agent://admin/ledger/lists',
                'is_show' => 1,
                'sort' => 3,
            ],
            [
                'name' => 'ADDON_AGENT_ADD',
                'title' => '新增代理',
                'url' => 'agent://admin/agent/add',
                'is_show' => 0,
            ],
            [
                'name' => 'ADDON_AGENT_ENABLE',
                'title' => '启用代理',
                'url' => 'agent://admin/agent/enable',
                'is_show' => 0,
            ],
            [
                'name' => 'ADDON_AGENT_DISABLE',
                'title' => '停用代理',
                'url' => 'agent://admin/agent/disable',
                'is_show' => 0,
            ],
        ]
    ]
];