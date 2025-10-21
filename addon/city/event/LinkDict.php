<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */
namespace addon\city\event;


/**
 * 分站结算
 */
class LinkDict
{
	// 行为扩展的执行入口必须是run
	public function handle($data)
	{
        $link_dict = [
            'city' => [
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
            ],
        ];

        return $link_dict;
	}


}