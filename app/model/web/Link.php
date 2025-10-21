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

namespace app\model\web;

use app\model\BaseModel;


/**
 * 网站系统性设置
 */
class Link extends BaseModel
{

    public function getLinkDict($site_id)
    {
        $dict_array =
            [
                'shop' => [
                    'ALL_GOODS' => [
                        'wap_url' => '/pages/goods/detail/detail?sku_id=',
                        'promotion' => 'all',
                        'select_id' => 'sku_id'
                    ],
                    'PINTUAN_GOODS' => [
                        'wap_url' => '/promotionpages/pintuan/detail/detail?pintuan_id=',
                        'goodsColsParameter' => [
                            'price' => 'pintuan_price'
                        ],
                        'promotion' => 'pintuan',
                        'select_id' => 'pintuan_id'
                    ],
                    'GROUPBUY_GOODS' => [
                        'wap_url' => '/promotionpages/groupbuy/detail/detail?groupbuy_id=',
                        'goodsColsParameter' => [
                            'price' => 'groupbuy_price'
                        ],
                        'promotion' => 'groupbuy',
                        'select_id' => 'groupbuy_id'
                    ],
                    'DISTRIBUTION_GOODS' => [
                        'wap_url' => '/pages/goods/detail/detail?sku_id=',
                        'promotion' => 'distribution',
                        'select_id' => 'sku_id'
                    ],
                    'BARGAIN_GOODS' => [
                        'wap_url' => '/promotionpages/bargain/detail/detail?bargain_id=',
                        'promotion' => 'bargain',
                        'select_id' => 'bargain_id'
                    ],
                    'PRESALE_GOODS' => [
                        'wap_url' => '/promotionpages/presale/detail/detail?id=',
                        'promotion' => 'presale',
                        'select_id' => 'presale_id'
                    ],
                    'CARDS_GAME' => [
                        'wap_url' => '/promotionpages/game/cards/cards?site_id='.$site_id.'&id=',
                        'url' => addon_url("cards://shop/cards/lists"),
                        'goodsCols' => [
                            [
                                [
                                    'unresize' => 'false',
                                    'width' => '8%',
                                    'templet' => '#checkbox'
                                ],
                                [
                                    'field' => 'game_name',
                                    'title' => '标题',
                                    'unresize' => 'false',
                                    'width' => '62%',
                                ],
                                [
                                    'field' => 'price',
                                    'title' => '状态',
                                    'unresize' => 'false',
                                    'width' => '30%',
                                    'templet' => '#game_status'
                                ]
                            ]
                        ],
                        'select_id' => 'game_id'
                    ],
                    'TURNTABLE_GAME' => [
                        'wap_url' => '/promotionpages/game/turntable/turntable?site_id='.$site_id.'&id=',
                        'url' => addon_url("turntable://shop/turntable/lists"),
                        'goodsCols' => [
                            [
                                [
                                    'unresize' => 'false',
                                    'width' => '8%',
                                    'templet' => '#checkbox'
                                ],
                                [
                                    'field' => 'game_name',
                                    'title' => '标题',
                                    'unresize' => 'false',
                                    'width' => '62%',
                                ],
                                [
                                    'field' => 'price',
                                    'title' => '状态',
                                    'unresize' => 'false',
                                    'width' => '30%',
                                    'templet' => '#game_status'
                                ]
                            ]
                        ],
                        'select_id' => 'game_id'
                    ],
                    'EGG_GAME' => [
                        'wap_url' => '/promotionpages/game/smash_eggs/smash_eggs?site_id='.$site_id.'&id=',
                        'url' => addon_url("egg://shop/egg/lists"),
                        'goodsCols' => [
                            [
                                [
                                    'unresize' => 'false',
                                    'width' => '8%',
                                    'templet' => '#checkbox'
                                ],
                                [
                                    'field' => 'game_name',
                                    'title' => '标题',
                                    'unresize' => 'false',
                                    'width' => '62%',
                                ],
                                [
                                    'field' => 'price',
                                    'title' => '状态',
                                    'unresize' => 'false',
                                    'width' => '30%',
                                    'templet' => '#game_status'
                                ]
                            ]
                        ],
                        'select_id' => 'game_id'
                    ]
                ],
                'admin' => [
                    'ALL_GOODS' => [
                        'wap_url' => '/pages/goods/detail/detail?sku_id=',
                        'promotion' => 'all',
                        'select_id' => 'sku_id'
                    ],
                    'PINTUAN_GOODS' => [
                        'wap_url' => '/promotionpages/pintuan/detail/detail?pintuan_id=',
                        'goodsColsParameter' => [
                            'price' => 'pintuan_price'
                        ],
                        'promotion' => 'pintuan',
                        'select_id' => 'pintuan_id'
                    ],
                    'GROUPBUY_GOODS' => [
                        'wap_url' => '/promotionpages/groupbuy/detail/detail?groupbuy_id=',
                        'goodsColsParameter' => [
                            'price' => 'groupbuy_price'
                        ],
                        'promotion' => 'groupbuy',
                        'select_id' => 'groupbuy_id'
                    ],
                    'DISTRIBUTION_GOODS' => [
                        'wap_url' => '/pages/goods/detail/detail?sku_id=',
                        'promotion' => 'distribution',
                        'select_id' => 'sku_id'
                    ],
                    'BARGAIN_GOODS' => [
                        'wap_url' => '/ppromotionpages/bargain/detail/detail?bargain_id=',
                        'promotion' => 'bargain',
                        'select_id' => 'bargain_id'
                    ],
                    'PRESALE_GOODS' => [
                        'wap_url' => '/promotionpages/presale/detail/detail?id=',
                        'promotion' => 'presale',
                        'select_id' => 'presale_id'
                    ],
                    'CARDS_GAME' => [
                        'wap_url' => '/promotionpages/game/cards/cards?site_id='.$site_id.'&id=',
                        'url' => addon_url("cards://admin/cards/lists"),
                        'goodsCols' => [
                            [
                                [
                                    'unresize' => 'false',
                                    'width' => '8%',
                                    'templet' => '#checkbox'
                                ],
                                [
                                    'field' => 'game_name',
                                    'title' => '标题',
                                    'unresize' => 'false',
                                    'width' => '62%',
                                ],
                                [
                                    'field' => 'price',
                                    'title' => '状态',
                                    'unresize' => 'false',
                                    'width' => '30%',
                                    'templet' => '#game_status'
                                ]
                            ]
                        ],
                        'select_id' => 'game_id'
                    ],
                    'TURNTABLE_GAME' => [
                        'wap_url' => '/promotionpages/game/turntable/turntable?site_id='.$site_id.'&id=',
                        'url' => addon_url("turntable://admin/turntable/lists"),
                        'goodsCols' => [
                            [
                                [
                                    'unresize' => 'false',
                                    'width' => '8%',
                                    'templet' => '#checkbox'
                                ],
                                [
                                    'field' => 'game_name',
                                    'title' => '标题',
                                    'unresize' => 'false',
                                    'width' => '62%',
                                ],
                                [
                                    'field' => 'price',
                                    'title' => '状态',
                                    'unresize' => 'false',
                                    'width' => '30%',
                                    'templet' => '#game_status'
                                ]
                            ]
                        ],
                        'select_id' => 'game_id'
                    ],
                    'EGG_GAME' => [
                        'wap_url' => '/promotionpages/game/smash_eggs/smash_eggs?site_id='.$site_id.'&id=',
                        'url' => addon_url("egg://admin/egg/lists"),
                        'goodsCols' => [
                            [
                                [
                                    'unresize' => 'false',
                                    'width' => '8%',
                                    'templet' => '#checkbox'
                                ],
                                [
                                    'field' => 'game_name',
                                    'title' => '标题',
                                    'unresize' => 'false',
                                    'width' => '62%',
                                ],
                                [
                                    'field' => 'price',
                                    'title' => '状态',
                                    'unresize' => 'false',
                                    'width' => '30%',
                                    'templet' => '#game_status'
                                ]
                            ]
                        ],
                        'select_id' => 'game_id'
                    ]
                ]
            ];
        $temp_link = event('LinkDict', []);
        if (!empty($temp_link)) {
            foreach ($temp_link as $k => $v) {
                $dict_array = array_merge($dict_array, $v);
            }
        }
        return $dict_array;
    }
}