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
 * 商品删除之后
 */
class DeleteGoods
{

    public function handle($data)
    {
        $goods = new Goods();
        foreach (explode(',', $data['goods_id']) as $goods_id) {
            $goods->deleteGoods($goods_id, $data['site_id']);
        }
    }
}