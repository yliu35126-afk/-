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

namespace app\admin\controller;

use app\Controller;
use app\model\system\User as UserModel;
use app\model\web\Link;
use app\model\goods\Goods as GoodsModel;
use app\model\goods\GoodsCategory as GoodsCategoryModel;

/**
 * 上传 控制器
 */
class Commonutil extends Controller
{

    public $site_id = 0;
    protected $app_module = "admin";

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
        //维护的链接字典
        $this->app_module = input('post', $this->app_module);

        $this->assign('post', $this->app_module);
        $user_model = new UserModel();
        $user_info = $user_model->userInfo($this->app_module);
        if(empty($user_info)){
            $this->error('当前端口未处于登录状态');
        }
        $this->site_id = $user_info["site_id"] ?? -1;
    }

    public function link()
    {
        $link_model = new Link();
        $dict_array = $link_model->getLinkDict($this->site_id);
        $dict = $dict_array[$this->app_module];
        $this->assign('dict', $dict);
        $data = [
            'app_module' => $this->app_module,
            'site_id' => $this->site_id,
        ];
        $diy_link = event('DiyLink', $data, true);
        return $diy_link;
    }

    /**
     * 下级链接
     * @return array|mixed|string
     */
    public function childLink()
    {
        $support_diy_view = input('support_diy_view', '');
        $diy_child_link = event('DiyChildLink', [ 'site_id' => $this->site_id, 'app_module' => $this->app_module, 'support_diy_view' => $support_diy_view ], true);
        return $diy_child_link;
    }



    public function goodsselect()
    {
        if (request()->isAjax()) {
            //已经选择的商品sku数据
            $select_id = input('select_id', '');
            $mode = input('mode', 'spu');
            $max_num = input('max_num', 0);
            $min_num = input('min_num', 0);
            $is_virtual = input('is_virtual', '');
            $disabled = input('disabled', 0);
            $promotion = input('promotion', '');//营销活动标识：pintuan、groupbuy、seckill、fenxiao

            $this->assign('select_id', $select_id);
            $this->assign('mode', $mode);
            $this->assign('max_num', $max_num);
            $this->assign('min_num', $min_num);
            $this->assign('is_virtual', $is_virtual);
            $this->assign('disabled', $disabled);
            $this->assign('promotion', $promotion);

            // 营销活动
            $goods_promotion_type = event('GoodsPromotionType');
            $this->assign('promotion_type', $goods_promotion_type);


            $goods_category_model = new GoodsCategoryModel();

            $field = 'category_id,category_name as title';
            $condition = [
                [ 'pid', '=', 0 ],
                [ 'level', '=', 1 ],
            ];
            $list = $goods_category_list = $goods_category_model->getCategoryByParent($condition, $field);
            $list = $list[ 'data' ];
            if (!empty($list)) {
                foreach ($list as $k => $v) {
                    $two_list = $goods_category_list = $goods_category_model->getCategoryByParent(
                        [
                            [ 'pid', '=', $v[ 'category_id' ] ],
                            [ 'level', '=', 2 ],
                        ],
                        $field
                    );

                    $two_list = $two_list[ 'data' ];
                    if (!empty($two_list)) {

                        foreach ($two_list as $two_k => $two_v) {
                            $three_list = $goods_category_list = $goods_category_model->getCategoryByParent(
                                [
                                    [ 'pid', '=', $two_v[ 'category_id' ] ],
                                    [ 'level', '=', 3 ],
                                ],
                                $field
                            );
                            $two_list[ $two_k ][ 'children' ] = $three_list[ 'data' ];
                        }
                    }

                    $list[ $k ][ 'children' ] = $two_list;
                }
            }

            $this->assign("category_list", $list);
            return $this->fetch("commonutil/goods_select");
        }

    }

    /**
     * 商品列表
     * @return array|mixed|string
     */
    public function goodslist(){
        $page = input('page', 1);
        $page_size = input('page_size', PAGE_LIST_ROWS);
        $goods_name = input('goods_name', '');
        $goods_id = input('goods_id', 0);
        $is_virtual = input('is_virtual', '');// 是否虚拟类商品（0实物1.虚拟）
        $min_price = input('min_price', 0);
        $max_price = input('max_price', 0);
        $goods_class = input('goods_class', "");// 商品类型，实物、虚拟
        $category_id = input('category_id', "");// 商品分类id
        $promotion = input('promotion', '');//营销活动标识：pintuan、groupbuy、fenxiao、bargain
        $promotion_type = input('promotion_type', "");

        if (!empty($promotion) && addon_is_exit($promotion)) {
            $pintuan_name = input('pintuan_name', '');//拼团活动
            $params = [
                'page' => $page,
                'page_size' => $page_size,
                'promotion' => $promotion,
                'pintuan_name' => $pintuan_name,
                'goods_name' => $goods_name
            ];
            //城市分站传website_id
            if($this->app_module == 'city'){
                $params['website_id'] = $this->site_id;
            }else{
                $params['site_id'] = $this->site_id;
            }
            $goods_list = event('GoodsListPromotion', [ 'page' => $page, 'page_size' => $page_size, 'site_id' => $this->site_id, 'promotion' => $promotion, 'pintuan_name' => $pintuan_name, 'goods_name' => $goods_name ], true);
        } else {
            $condition = [
                [ 'is_delete', '=', 0 ],
                [ 'goods_state', '=', 1 ],
                ['verify_state', '=', 1]
            ];
            if($this->site_id > 0){
                //城市分站传website_id
                if($this->app_module == 'city'){
                    $condition[] = [ 'website_id', '=', $this->site_id ];
                }else{
                    $condition[] = [ 'site_id', '=', $this->site_id ];
                }
            }


            if (!empty($goods_name)) {
                $condition[] = [ 'goods_name', 'like', '%' . $goods_name . '%' ];
            }
            if ($is_virtual !== "") {
                $condition[] = [ 'is_virtual', '=', $is_virtual ];
            }
            if (!empty($goods_id)) {
                $condition[] = [ 'goods_id', '=', $goods_id ];
            }


            if (!empty($category_id)) {
                $goods_category_model = new GoodsCategoryModel();

                // 查询当前
                $category_list = $goods_category_model->getCategoryList([ [ 'category_id', '=', $category_id ], [ 'is_show', '=', 1 ] ], 'category_id,pid,level');
                $category_list = $category_list[ 'data' ];

                // 查询子级
                $category_child_list = $goods_category_model->getCategoryList([ [ 'pid', '=', $category_id ], [ 'is_show', '=', 1 ] ], 'category_id,pid,level');
                $category_child_list = $category_child_list[ 'data' ];

                $temp_category_list = [];
                if (!empty($category_list)) {
                    $temp_category_list = $category_list;
                } elseif (!empty($category_child_list)) {
                    $temp_category_list = $category_child_list;
                }

                if (!empty($temp_category_list)) {
                    $category_id_arr = [];
                    foreach ($temp_category_list as $k => $v) {
                        // 三级分类，并且都能查询到
                        if ($v[ 'level' ] == 3 && !empty($category_list) && !empty($category_child_list)) {
                            array_push($category_id_arr, $v[ 'pid' ]);
                        } else {
                            array_push($category_id_arr, $v[ 'category_id' ]);
                        }
                    }
                    $category_id_arr = array_unique($category_id_arr);
                    $temp_condition = [];
                    foreach ($category_id_arr as $ck => $cv) {
                        $temp_condition[] = '%,' . $cv . ',%';
                    }
                    $category_condition = $temp_condition;
                    $condition[] = [ 'category_id', 'like', $category_condition, 'or' ];
                }
            }
            if (!empty($promotion_type)) {
                $condition[] = [ 'promotion_addon', 'like', "%{$promotion_type}%" ];
            }


            if ($goods_class !== "") {
                $condition[] = [ 'goods_class', '=', $goods_class ];
            }

            if ($min_price != "" && $max_price != "") {
                $condition[] = [ 'price', 'between', [ $min_price, $max_price ] ];
            } elseif ($min_price != "") {
                $condition[] = [ 'price', '<=', $min_price ];
            } elseif ($max_price != "") {
                $condition[] = [ 'price', '>=', $max_price ];
            }

            $order = 'sort desc,create_time desc';
            $goods_model = new GoodsModel();
            $field = 'goods_id,goods_name,goods_class_name,goods_image,price,goods_stock,create_time,is_virtual,sku_id';
            $goods_list = $goods_model->getGoodsPageList($condition, $page, $page_size, $order, $field);

            if (!empty($goods_list[ 'data' ][ 'list' ])) {
                foreach ($goods_list[ 'data' ][ 'list' ] as $k => $v) {
                    $goods_sku_list = $goods_model->getGoodsSkuList([ [ 'goods_id', '=', $v[ 'goods_id' ] ] ], 'sku_id,sku_name,price,stock,sku_image,goods_id,goods_class_name');
                    $goods_sku_list = $goods_sku_list[ 'data' ];
                    $goods_list[ 'data' ][ 'list' ][ $k ][ 'sku_list' ] = $goods_sku_list;
                }

            }
        }
        return $goods_list;
    }
}