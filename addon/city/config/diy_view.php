<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */
return [
    'template' => [],
    'util' => [
        [
            'name' => 'CUT_CITY',
            'title' => '城市切换',
            'type' => 'SYSTEM',
            'controller' => 'CutCity',
            'value' => '{}',
            'sort' => '12004',
            'support_diy_view' => 'DIY_VIEW_CITY_INDEX',
            'max_count' => 1,
            'is_delete' => 1,
            'icon' => 'addon/city/component/view/city/img/icon/city.png',
            'icon_selected' => 'addon/city/component/view/city/img/icon/city_selected.png',
        ],
    ],
    'link' => [],
];