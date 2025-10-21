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

use think\facade\Cache;
use app\model\BaseModel;

/**
 * 商品分类
 */
class GoodsCategory extends BaseModel
{

    /**
     * 修改商品分类 的供应商分佣比率
     * @param $data
     * @return \multitype
     */
    public function editCategory($condition, $commission_rate)
    {
        $check_condition = array_column($condition, 2, 0);
        $category_id = isset($check_condition[ 'category_id' ]) ? $check_condition[ 'category_id' ] : '';
        $data = array (
             'supply_commission_rate'=> $commission_rate
        );
        $res = model('goods_category')->update($data, $condition);

        //修改受影响的商品分佣比率
        $goods_condition = array (
            [ 'category_json', 'like', '%' . $category_id . '"]' ]
        );
        model('supply_goods')->update([ 'commission_rate' => $commission_rate ],$goods_condition);

        Cache::tag("goods_category")->clear();

        return $this->success($res);
    }


}