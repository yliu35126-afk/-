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

namespace addon\supply\model\goods;


use app\model\BaseModel;

/**
 * 购物车
 */
class Cart extends BaseModel
{

    /**
     * 添加购物车
     * @param $data
     * @return array
     */
    public function addCart($data)
    {
        $cart_info = model("supply_goods_cart")->getInfo([['sku_id', '=', $data['sku_id']], ['shop_id', '=', $data['shop_id']], ['uid', '=', $data['uid']]], 'cart_id, num');
        if (!empty($cart_info)) {
            $res = model("supply_goods_cart")->update(['num' => $cart_info['num'] + $data['num']], [['cart_id', '=', $cart_info['cart_id']]]);
        } else {
            $res = model("supply_goods_cart")->add($data);
        }
        return $this->success($res);
    }

    /**
     * 更新购物车商品数量
     * @param $data
     * @return array
     */
    public function editCart($data)
    {
        $res = model("supply_goods_cart")->update(['num' => $data['num']], [['cart_id', '=', $data['cart_id']], ['shop_id', '=', $data['shop_id']]]);
        return $this->success($res);
    }

    /**
     * 删除购物车商品项(可以多项)
     * @param $data
     * @return array
     */
    public function deleteCart($data)
    {
        $condition = [['cart_id', 'in', $data['cart_id']]];
        $shop_id = $data['shop_id'] ?? 0;
        if ($shop_id > 0) {
            $condition[] = ['shop_id', '=', $shop_id];
        }
        $res = model("supply_goods_cart")->delete($condition);
        return $this->success($res);
    }

    /**
     * 清空购物车
     * @param $data
     * @return array
     */
    public function clearCart($data)
    {
        $res = model("supply_goods_cart")->delete([['shop_id', '=', $data['shop_id']]]);
        return $this->success($res);
    }

    /**
     * 获取会员购物车
     * @param $shop_id
     * @return array
     */
    public function getCart($shop_id)
    {

        $field = 'sgc.cart_id, sgc.shop_id, sgc.uid, sgc.site_id, sgc.sku_id, sgc.num, sgs.sku_name,sgs.price_json,sgs.min_price,sgs.max_price,sgs.min_num,
            sgs.sku_no, sgs.sku_spec_format, sgs.price_json, sgs.market_price, sgs.cost_price, sgs.stock, 
            sgs.sku_image, sgs.sku_images, sgs.site_name, sgs.website_id, sgs.is_own, sgs.goods_state, 
            sgs.verify_state, sgs.verify_state_remark, sgs.goods_stock_alarm, sgs.is_virtual, 
            sgs.virtual_indate, sgs.is_free_shipping, sgs.shipping_template, sgs.unit, sgs.introduction, sgs.keywords';
        $alias = 'sgc';
        $join = [
            [
                'supply_goods_sku sgs',
                'sgc.sku_id = sgs.sku_id',
                'inner'
            ],
        ];
        $list = model("supply_goods_cart")->getList([['sgc.shop_id', '=', $shop_id]], $field, '', $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取购物车数量
     * @param $shop_id
     * @return array
     */
    public function getCartCount($shop_id)
    {
        $alias = 'sgc';
        $join = [
            ['supply_goods_sku gs', 'gs.sku_id = sgc.sku_id', 'inner']
        ];

        $condition = [
            ['gs.goods_state', '=', 1],
            ['gs.verify_state', '=', 1],
            ['sgc.shop_id', '=', $shop_id]
        ];
        $list = model("supply_goods_cart")->getList($condition,'*','',$alias,$join);

        return $this->success(count($list));
    }

    /**
     * 获取购物车数量
     * @param $shop_id
     * @return array
     */
    public function getCartItemsNum($shop_id)
    {
        $num = model("supply_goods_cart")->getSum(['shop_id' => $shop_id], 'num');
        return $this->success($num);
    }
}
