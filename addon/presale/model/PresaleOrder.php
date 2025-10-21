<?php
// +---------------------------------------------------------------------+
// | NiuCloud | [ WE CAN DO IT JUST NiuCloud ]                |
// +---------------------------------------------------------------------+
// | Copy right 2019-2029 www.niucloud.com                          |
// +---------------------------------------------------------------------+
// | Author | NiuCloud <niucloud@outlook.com>                       |
// +---------------------------------------------------------------------+
// | Repository | https://github.com/niucloud/framework.git          |
// +---------------------------------------------------------------------+

namespace addon\presale\model;

use app\model\BaseModel;

use app\model\order\Order;
use app\model\system\Cron;
use app\model\order\Config;
use app\model\message\Message;
use app\model\goods\Goods;
use addon\coupon\model\Coupon;
use app\model\member\MemberAccount;
use addon\store\model\StoreGoodsSku;

/**
 * 商品预售
 */
class PresaleOrder extends BaseModel
{

    /**
     * 获取预售订单信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getPresaleOrderInfo($condition = [], $field = '*')
    {
        $info = model('promotion_presale_order')->getInfo($condition,$field);
        return $this->success($info);
    }


    /**
     * 获取预售订单分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getPresaleOrderPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'id desc', $field='*',$alias = 'a', $join = [])
    {
        $list = model('promotion_presale_order')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    public function getBuyNum($condition, $field)
    {
        $num = model('promotion_presale_order')->getSum($condition, $field);
        return $this->success($num);
    }

    public function getPresaleOrderNum($condition){
        $num = model('promotion_presale_order')->getCount($condition);
        return $this->success($num);
    }

}