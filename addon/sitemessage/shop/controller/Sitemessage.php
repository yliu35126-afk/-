<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\sitemessage\shop\controller;

use app\shop\controller\BaseShop;

/**
 * 站内信
 * @author Administrator
 *
 */
class Sitemessage extends BaseShop
{
    /**
     * 添加活动
     */
    public function lists()
    {

        if (request()->isAjax()) {

        } else {

            return $this->fetch("sitemessage/lists");
        }
    }


}