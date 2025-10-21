<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\store\event;

use app\model\store\Store;

/**
 * 删除门店
 */
class DeleteStore
{

    public function handle($data)
    {
        $store_model = new Store();
        $res = $store_model->cronDeleteStore($data['store_id']);
        return $res;
    }
}