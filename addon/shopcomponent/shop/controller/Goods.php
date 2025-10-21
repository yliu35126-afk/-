<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\shopcomponent\shop\controller;

use addon\shopcomponent\model\Category;
use addon\shopcomponent\model\Weapp;
use app\model\system\Cron;
use app\shop\controller\BaseShop;
use addon\shopcomponent\model\Goods as GoodsModel;
use addon\shopcomponent\model\Category as CategoryModel;
/**
 * 商品
 */
class Goods extends BaseShop
{
    public function lists()
    {
        if (request()->isAjax()) {
            $goods = new GoodsModel();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [
                'sg.site_id' => $this->site_id
            ];
            $data = $goods->getGoodsPageList($condition, '*', 'id desc', $page, $page_size);
            return $data;
        } else {
            $category = (new Category())->getCategoryByParent(1);
            $this->assign('first_cat', $category['data']);

            $check_res = (new Weapp())->checkRegister();
            $this->assign('checkres', $check_res);
            return $this->fetch("goods/index");
        }
    }

    public function sync(){
        $start = input('start', 0);
        $site_id = input('site_id', 0);
        $res = (new GoodsModel())->syncGoods($start,100,$site_id);
        return $res;
    }

    /**
     * 小程序添加商品
     */
    public function add()
    {
        if (request()->isAjax()) {
            $site_id = input('site_id',0);

            $param = input();
            if(empty($param))return $this->error('必要参数必填');
            $data = [
                'site_id' => $site_id,
                'goods_ids' =>$param['goods_ids'],
                'third_cat_id' =>$param['third_cat_id'],

            ];
            $goods_model = new GoodsModel();
            return $goods_model->addGoods($data);
        }
    }

    /**
     * 小程序修改商品分类
     */
    public function edit()
    {
        if (request()->isAjax()) {
            $site_id = input('site_id',0);
            $param = input();
            if(empty($param))return $this->error('必要参数必填');
            $data = [
                'site_id' => $site_id,
                'goods_id' =>$param['goods_id'],
                'third_cat_id' =>$param['third_cat_id'],

            ];
            $goods_model = new GoodsModel();
            return $goods_model->updateGoods($data);
        }
    }

    /**
     * 检验分类是否已上传资质
     */
    public function check()
    {
        if (request()->isAjax()) {
            $site_id = input('site_id',0);
            $param = input();
            if(empty($param))return $this->error('必要参数必填');
            $data = [
                'site_id' => $site_id,
                'third_cat_id' =>$param['third_cat_id'],
            ];
            $cat_model = new CategoryModel();
            return $cat_model->isQualifications($data);
        }
    }

    /**
     * 小程序删除商品
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $site_id = input('site_id',0);

            $goods_model = new GoodsModel();
            return $goods_model->deleteGoods(input('out_product_ids',''),$site_id);
        }
    }

    /**
     * 商品上架
     */
    public function listing()
    {
        if (request()->isAjax()) {
            $site_id = input('site_id',0);
            $goods_model = new GoodsModel();
            $out_product_ids = input('out_product_ids', '');
            return $goods_model->goodsListing($out_product_ids, $site_id);
        }
    }

    /**
     * 商品下架
     */
    public function dellisting()
    {
        if (request()->isAjax()) {
            $site_id = input('site_id',0);
            $goods_model = new GoodsModel();
            $out_product_ids = input('out_product_ids', '');
            return $goods_model->goodsDelisting($out_product_ids, $site_id);
        }
    }


}