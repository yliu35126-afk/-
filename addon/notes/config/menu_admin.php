<?php
// +----------------------------------------------------------------------
// | 店铺端菜单设置
// +----------------------------------------------------------------------
return [
    [
        'name' => 'PROMOTION_NOTES',
        'title' => '店铺笔记',
        'url' => 'notes://admin/notes/lists',
        'parent' => 'PROMOTION_ROOT',
        'picture' => 'addon/notes/shop/view/public/img/distribution.png',
        'is_show' => 1,
        'sort' => 1,
        'child_list' => [

            [
                'name' => 'PROMOTION_NOTES_LISTS',
                'title' => '笔记列表',
                'url' => 'notes://admin/notes/lists',
                'is_show' => 1,
                'sort' => 1,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_NOTES_EDIT',
                        'title' => '编辑笔记',
                        'url' => 'notes://admin/notes/edit',
                        'sort' => 2,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_DELETE',
                        'title' => '删除笔记',
                        'url' => 'notes://admin/notes/delete',
                        'sort' => 3,
                        'is_show' => 0
                    ]
                ]
            ],
            [
                'name' => 'PROMOTION_NOTES_GROUP',
                'title' => '笔记分组',
                'url' => 'notes://admin/group/lists',
                'is_show' => 1,
                'sort' => 3,
                'child_list' => [
                    [
                        'name' => 'PROMOTION_NOTES_GROUP_ADD',
                        'title' => '添加分组',
                        'url' => 'notes://admin/group/add',
                        'sort' => 1,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_GROUP_EDIT',
                        'title' => '编辑分组',
                        'url' => 'notes://admin/group/edit',
                        'sort' => 2,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_GROUP_DELETE',
                        'title' => '删除分组',
                        'url' => 'notes://admin/group/delete',
                        'sort' => 3,
                        'is_show' => 0
                    ],
                    [
                        'name' => 'PROMOTION_NOTES_GROUP_MODIFYSORT',
                        'title' => '修改排序',
                        'url' => 'notes://admin/group/modifysort',
                        'sort' => 4,
                        'is_show' => 0
                    ],
                ]
            ]
        ]
    ],

];