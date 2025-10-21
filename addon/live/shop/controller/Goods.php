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

namespace addon\live\shop\controller;

use addon\live\model\Goods as GoodsModel;
use app\shop\controller\BaseShop;

/**
 * 直播间
 */
class Goods extends BaseShop
{
    public function index()
    {
        if (request()->isAjax()) {
            $goods = new GoodsModel();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [
                'site_id' => $this->site_id
            ];
            $data = $goods->getGoodsPageList($condition, '*', 'id desc', $page, $page_size);
            return $data;
        } else {
            return $this->fetch("goods/index");
        }
    }

    /**
     * 同步直播商品库
     */
    public function sync()
    {
        if (request()->isAjax()) {
            $goods = new GoodsModel();
            $start = input('start', 0);
            $res = $goods->syncGoods($start, 20);
            return $res;
        }
    }

    /**
     * 添加商品
     * @return mixed
     */
    public function add()
    {
        if (request()->isAjax()) {
            $goods = new GoodsModel();
            $data = [
                'site_id' => $this->site_id,
                'name' => input('name', ''),
                'goods_pic' => input('goods_pic', ''),
                'price_type' => input('price_type', ''),
                'price' => input('price', ''),
                'price2' => input('price2', ''),
                'url' => input('url', ''),
            ];
            $res = $goods->addGoods($data);
            return $res;
        }
        return $this->fetch("goods/add");
    }

    /**
     * 删除商品
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $id = input('id', '');
            $goods = new GoodsModel();
            $res = $goods->deleteGoods($id, $this->site_id);
            return $res;
        }
    }

}