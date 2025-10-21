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

namespace addon\wholesale\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\wholesale\model\Wholesale as WholesaleModel;

/**
 * 批发控制器
 */
class Wholesale extends BaseAdmin
{
    /*
     *  批发活动列表
     */
    public function lists()
    {
        $model = new WholesaleModel();

        $condition = [
            [ 'wg.wholesale_goods_id', '>', 0 ],
            [ 'g.goods_state','=',1 ],
            [ 'g.is_delete','=',0 ]
        ];
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $goods_name = input('goods_name', '');
            if(!empty($goods_name)){
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }
            $site_name = input('site_name', '');
            if(!empty($goods_name)){
                $condition[] = [ 'wg.site_name', 'like', '%' . $site_name . '%' ];
            }

            $alias = 'g';
            $join = [
                [ 'wholesale_goods wg', 'wg.goods_id = g.goods_id', 'left' ],
                [ 'goods_sku sku', 'g.sku_id = sku.sku_id', 'left' ]
            ];
            $field = 'g.*,wg.wholesale_goods_id, wg.max_price, wg.min_price, wg.min_num, wg.status,sku.sku_id,sku.price,sku.sku_name,sku.sku_image';

            $list = $model->getWholesaleGoodsViewPageList($condition, $page, $page_size, 'g.sort desc,g.create_time desc', $field, $alias, $join);
            return $list;
        } else {
            return $this->fetch("wholesale/lists");
        }

    }

    /**
     * 批发商品规格列表
     */
    public function detail(){
        $goods_id = input('goods_id', 0);
        $wholesale_model = new WholesaleModel();

        $info_result = $wholesale_model->getWholesaleJoinGoodsDetail($goods_id);
        $this->assign('info', $info_result['data'] ?? []);
        return $this->fetch("wholesale/detail");
    }

    /**
     * 商品加入批发
     */
    public function delete(){
        if (request()->isAjax()) {
            $goods_id = input('goods_id', 0);
            $wholesale_model = new WholesaleModel();
            $condition = array(
                ['goods_id', '=', $goods_id]
            );
            $result = $wholesale_model->delete($condition);
            return $result;
        }
    }

    /**
     * 获取商品列表
     * @return array
     */
    public function getSkuList()
    {
        if(request()->isAjax()){
            $wholesale_model = new WholesaleModel();

            $goods_id = input('goods_id', '');

            $sku_list = $wholesale_model->getWholesaleGoodsSkuList($goods_id);
            return $sku_list;
        }
    }
	
}