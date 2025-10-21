<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\shopcomponent\event;

use addon\shopcomponent\model\Goods;

/**
 * 商品编辑之后
 */
class GoodsEdit
{

    public function handle($data)
    {
        $goods = new Goods();
        $goods->updateGoods(['goods_id' => $data['goods_id'], 'site_id' => $data['site_id'] ]);
    }
}