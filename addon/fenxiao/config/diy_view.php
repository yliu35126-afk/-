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
            'name' => 'FENXIAO_GOODS_LIST',
            'title' => '分销商品',
            'type' => 'ADDON',
            'controller' => 'FenxiaoGoodsList',
            'value' => '{"sources" : "default", "categoryId" : 0, "goodsCount" : "6", "goodsId": [], "style": 1, "backgroundColor": "", "padding": 10, "list": {"imageUrl": "","title": "分销商品"}, "listMore": {"imageUrl": "","title": "查看更多"}, "titleTextColor": "#000", "defaultTitleTextColor": "#000", "moreTextColor": "#858585", "defaultMoreTextColor": "#858585"}',
            'sort' => '12008',
            'support_diy_view' => 'DIY_FENXIAO_MARKET',
            'max_count' => 0,
            'icon' => 'addon/fenxiao/component/view/goods_list/img/icon/fx_goods_list.png',
            'icon_selected' => 'addon/fenxiao/component/view/goods_list/img/icon/fx_goods_list_selected.png'
        ]
    ],
    'link' => [
        [
            'name' => 'DISTRIBUTION',
            'title' => '分销',
            'parent' => 'MALL_LINK',
            'wap_url' => '/pages/index/index/index',
            'web_url' => '',
            'sort' => 2,
            'child_list' => [
                //多商户原有
                [
                    'name' => 'FENXIAO_GOODS_LIST',
                    'title' => '分销商品列表',
                    'wap_url' => '/otherpages/fenxiao/goods_list/goods_list',
                    'web_url' => '',
                    'support_diy_view' => 'DIY_VIEW_INDEX',
                ],
                //单商户添加过来的
                [
                    'name' => 'DISTRIBUTION_CENTRE',
                    'title' => '分销中心',
                    'parent' => '',
                    'wap_url' => '/otherpages/fenxiao/index/index',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'WITHDRAWAL_SUBSIDIARY',
                    'title' => '提现明细',
                    'parent' => '',
                    'wap_url' => '/otherpages/fenxiao/withdraw_list/withdraw_list',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'DISTRIBUTION_ORDER',
                    'title' => '分销订单',
                    'parent' => '',
                    'wap_url' => '/otherpages/fenxiao/order/order',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'DISTRIBUTION_MARKET',
                    'title' => '分销市场',
                    'parent' => '',
                    'wap_url' => '/otherpages/diy/diy/diy?name=DIY_FENXIAO_MARKET',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'DISTRIBUTION_GOODS',
                    'title' => '分销商品',
                    'parent' => '',
                    'wap_url' => '/otherpages/fenxiao/follow/follow',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'DISTRIBUTION_TEAM',
                    'title' => '分销团队',
                    'parent' => '',
                    'wap_url' => '/otherpages/fenxiao/team/team',
                    'web_url' => '',
                    'sort' => 0
                ],
                [
                    'name' => 'PROMOTION_POSTER',
                    'title' => '推广海报',
                    'parent' => '',
                    'wap_url' => '/otherpages/fenxiao/promote_code/promote_code',
                    'web_url' => '',
                    'sort' => 0
                ],
            ]
        ],
        [
            'name' => 'DISTRIBUTION_GOODS',
            'title' => '分销商品',
            'parent' => 'COMMODITY',
            'wap_url' => '',
            'web_url' => '',
            'child_list' => []
        ],
    ]
];