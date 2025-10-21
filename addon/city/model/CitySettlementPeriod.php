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

namespace addon\city\model;

use app\model\BaseModel;


class CitySettlementPeriod extends BaseModel
{

    /**
     * 获取账单结算周期信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getCitySettlementPeriodInfo($condition = [], $field = '*')
    {
        $res = model('website_settlement_period')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取结算分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getCitySettlementPeriodPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {

        $list = model('website_settlement_period')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 分站结算数据统计
     * @param $condition
     * @return array
     */
    public function getCitySettlementPeriodSum()
    {
        $res = [];
        $res[ 'shop_commission' ] = model('website_settlement_period')->getSum([], 'shop_commission');
        $res[ 'order_commission' ] = model('website_settlement_period')->getSum([], 'order_commission');
        if (empty($res[ 'shop_commission' ]) || $res[ 'shop_commission' ] == null) {
            $res[ 'shop_commission' ] = '0.00';
        } else {
            $res[ 'shop_commission' ] = number_format($res[ 'shop_commission' ], 2, '.', '');
        }
        if (empty($res[ 'order_commission' ]) || $res[ 'order_commission' ] == null) {
            $res[ 'order_commission' ] = '0.00';
        } else {
            $res[ 'order_commission' ] = number_format($res[ 'order_commission' ], 2, '.', '');
        }
        return $this->success($res);
    }

}