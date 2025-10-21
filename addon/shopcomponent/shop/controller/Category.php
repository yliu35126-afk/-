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

use addon\shopcomponent\model\Weapp;
use app\shop\controller\BaseShop;
use addon\shopcomponent\model\Category as CategoryModel;

/**
 * 类目
 */
class Category extends BaseShop
{
    public function lists()
    {
        $third_cat_id = input('third_cat_id', '');

        if (request()->isAjax()) {
            $category = new CategoryModel();
            $page = input('page', 1);
            $keywords = empty(input('keywords')) ? '' : input('keywords');
            $page_size = input('page_size', PAGE_LIST_ROWS);
            if (!empty($keywords)){
                $condition [] = ['first_cat_name|second_cat_name|third_cat_name','like','%'.$keywords.'%'];
            }
            if (!empty($third_cat_id)){
                $condition [] = ['third_cat_id','=',$third_cat_id];
            }

            $condition [] = ['site_id','=',$this->site_id];

            $data = $category->getcategoryPageList($condition, '*', 'first_cat_id asc,second_cat_id asc,third_cat_id asc', $page, $page_size);
            return $data;
        } else {
            $this->assign('third_cat_id', $third_cat_id);
            return $this->fetch("category/index");
        }
    }

    /**
     * 同步商品类目
     * @return array
     */
    public function sync()
    {
        if (request()->isAjax()) {
            $category = new CategoryModel();
            $res = $category->syncCategory($this->site_id);
            return $res;
        }
    }
    /**
     * 上传资质
     * @return array
     */
    public function qualifications()
    {
        if (request()->isAjax()) {
            $category = new CategoryModel();
            $param = input();
            $res = $category->uploadQualifications($param, $this->site_id);
            return $res;
        }
    }

    /**
     * 获取分类通过上级
     * @return array
     */
    public function getCategoryByParent(){
        if (request()->isAjax()) {
            $category = new CategoryModel();
            $level = input('level', 1);
            $pid = input('pid', 0);
            $res = $category->getCategoryByParent($level, $pid);
            return $res;
        }
    }

}