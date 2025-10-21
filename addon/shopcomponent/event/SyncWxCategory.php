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

use addon\shopcomponent\model\Category;

/**
 * 同步微信类目
 */
class SyncWxCategory
{
    public function handle($data)
    {
        (new Category())->syncCategory($data['relate_id']);
    }
}