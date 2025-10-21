<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\api\controller;

use app\model\shop\ShopGroup as ShopGroupModel;

/**
 * 店铺等级
 * Class Shopcategory
 * @package app\api\controller
 */
class Shopgroup extends BaseApi
{

    public function page()
    {
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $condition = [
            [ 'is_own', '=', 1 ]
        ];
        $order = 'is_own asc,fee asc';
        $field = 'group_id,group_name,fee,remark';
        $shop_group_model = new ShopGroupModel();
        $list = $shop_group_model->getGroupPageList($condition, $page, $page_size, $order, $field);
        return $this->response($list);
    }

}