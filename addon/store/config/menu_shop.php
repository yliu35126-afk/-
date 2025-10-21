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

    [
        'name' => 'ADDON_STORE_SHOP_STORE_SETTLEMENT',
        'title' => '门店结算',
        'url' => 'store://shop/settlement/index',
        'parent' => 'STORE_MANAGE',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'sort' => 2,
        'picture' => 'addon/store/shop/view/public/img/shop_icon/settlement.png',
        'child_list' => [
            [
                'name' => 'ADDON_STORE_SHOP_STORE_SETTLEMENT_INFO',
                'title' => '结算详情',
                'url' => 'store://shop/settlement/detail',
                'is_show' => 0,
                'is_control' => 1,
                'sort' => 1,
            ],
            [
                'name' => 'ADDON_STORE_SHOP_STORE_SETTLEMENT_SETTLEMENT',
                'title' => '门店结算',
                'url' => 'store://shop/settlement/settlement',
                'is_show' => 0,
                'is_control' => 1,
                'sort' => 1,
            ]
        ]
    ],
    [
        'name' => 'ADDON_STORE_SHOP_STORE_CONFIG',
        'title' => '门店设置',
        'url' => 'store://shop/config/index',
        'parent' => 'STORE_MANAGE',
        'is_show' => 1,
        'is_control' => 1,
        'is_icon' => 0,
        'sort' => 3,
    ],
];
