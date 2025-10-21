<?php

namespace addon\coupon\component\controller;

use app\component\controller\BaseDiyView;

/**
 * 店铺优惠券·组件
 */
class ShopCoupon extends BaseDiyView
{
    /**
     * 后台编辑界面
     */
    public function design()
    {
        return $this->fetch("shop_coupon/design.html");
    }
}