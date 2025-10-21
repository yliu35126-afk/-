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

namespace addon\supply\supply\controller;

use app\model\express\ExpressTemplate as ExpressTemplateModel;
use addon\supply\model\goods\Goods as GoodsModel;
use app\model\goods\GoodsAttribute as GoodsAttributeModel;
use app\model\goods\GoodsBrand as GoodsBrandModel;
use app\model\goods\GoodsCategory as GoodsCategoryModel;
use addon\supply\model\goods\GoodsEvaluate as GoodsEvaluateModel;
use app\model\goods\GoodsCategory;

/**
 * 实物商品
 * Class Goods
 * @package addon\supply\supply\controller
 */
class Goods extends BaseSupply
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
    }

    /**
     * 商品列表
     * @return mixed
     */
    public function lists()
    {
        $goods_model = new GoodsModel();
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', "");
            $goods_state = input('goods_state', "");
            $verify_state = input('verify_state', "");
            $start_sale = input('start_sale', 0);
            $end_sale = input('end_sale', 0);
            $start_price = input('start_price', 0);
            $end_price = input('end_price', 0);
            $goods_class = input('goods_class', "");

            $condition = [ [ 'is_delete', '=', 0 ], [ 'site_id', '=', $this->supply_id ] ];

            if (!empty($search_text)) {
                $condition[] = [ 'goods_name', 'like', '%' . $search_text . '%' ];
            }

            if ($goods_class !== "") {
                $condition[] = [ 'goods_class', '=', $goods_class ];
            }

            // 上架状态
            if ($goods_state !== '') {
                $condition[] = [ 'goods_state', '=', $goods_state ];
            }

            // 审核状态
            if ($verify_state !== '') {
                $condition[] = [ 'verify_state', '=', $verify_state ];
            }
            if (!empty($start_sale)) $condition[] = [ 'sale_num', '>=', $start_sale ];
            if (!empty($end_sale)) $condition[] = [ 'sale_num', '<=', $end_sale ];
            if (!empty($start_price)) $condition[] = [ 'min_price', '>=', $start_price ];
            if (!empty($end_price)) $condition[] = [ 'max_price', '<=', $end_price ];
            $res = $goods_model->getGoodsPageList($condition, $page_index, $page_size);
            return $res;
        } else {
            $verify_state = $goods_model->getVerifyState();
            $arr = [];
            foreach ($verify_state as $k => $v) {
                // 过滤已审核状态
                if ($k != 1) {
                    $total = $goods_model->getGoodsTotalCount([ [ 'verify_state', '=', $k ], [ 'site_id', "=", $this->supply_id ] ]);
                    $total = $total[ 'data' ];
                    $arr[] = [
                        'state' => $k,
                        'value' => $v,
                        'count' => $total
                    ];
                }
            }
            $verify_state = $arr;
            $this->assign("verify_state", $verify_state);

            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([ [ 'site_id', "=", $this->supply_id ] ], 'template_id,template_name', 'is_default desc');
            $express_template_list = $express_template_list[ 'data' ];
            $this->assign("express_template_list", $express_template_list);

            $goods_category_model = new GoodsCategoryModel();
            $category_list = $goods_category_model->getCategoryTree()[ 'data' ] ?? [];
            $this->assign('category_list', $category_list);

            return $this->fetch("goods/lists");
        }
    }

    /**
     * 刷新审核状态商品数量
     */
    public function refreshVerifyStateCount()
    {
        if (request()->isAjax()) {
            $goods_model = new GoodsModel();
            $verify_state = $goods_model->getVerifyState();
            $arr = [];
            foreach ($verify_state as $k => $v) {
                // 过滤已审核状态
                if ($k != 1) {
                    $total = $goods_model->getGoodsTotalCount([ [ 'verify_state', '=', $k ], [ 'is_delete', '=', 0 ], [ 'site_id', "=", $this->supply_id ] ]);
                    $total = $total[ 'data' ];
                    $arr[] = [
                        'state' => $k,
                        'value' => $v,
                        'count' => $total
                    ];
                }
            }
            $verify_state = $arr;
            return $verify_state;
        }
    }

    /**
     * 添加商品
     * @return mixed
     */
    public function addGoods()
    {
        if (request()->isAjax()) {
            $goods_model = new GoodsModel();
            if ($this->supply_info[ "status" ] == 0) {
                return $goods_model->error([], "当前供应商处于关闭状态!");
            }
            $category_array = input("category_id", 0);// 分类id
            $category_json = json_encode($category_array);//分类字符串
            $category_id = ',' . implode(',', $category_array) . ',';
            $data = [
                'goods_name' => input("goods_name", ""),// 商品名称,
                'goods_attr_class' => input("goods_attr_class", ""),// 商品类型id,
                'goods_attr_name' => input("goods_attr_name", ""),// 商品类型名称,
                'category_id' => $category_id,
                'category_array' => $category_array,//商品分类数组
                'category_json' => $category_json,
                'goods_image' => input("goods_image", ""),// 商品主图路径
                'goods_content' => input("goods_content", ""),// 商品详情
                'goods_state' => input("goods_state", ""),// 商品状态（1.正常0下架）
                'price' => input("price", 0),// 商品价格（取第一个sku）
                'market_price' => input("market_price", 0),// 市场价格（取第一个sku）
                'cost_price' => input("cost_price", 0),// 成本价（取第一个sku）
                'sku_no' => input("sku_no", ""),// 商品sku编码
                'weight' => input("weight", ""),// 重量
                'volume' => input("volume", ""),// 体积
                'goods_stock' => input("goods_stock", 0),// 商品库存（总和）
                'goods_stock_alarm' => input("goods_stock_alarm", 0),// 库存预警
                'is_free_shipping' => input("is_free_shipping", 1),// 是否免邮
                'shipping_template' => input("shipping_template", 0),// 指定运费模板
                'goods_spec_format' => input("goods_spec_format", ""),// 商品规格格式
                'goods_attr_format' => input("goods_attr_format", ""),// 商品属性格式
                'introduction' => input("introduction", ""),// 促销语
                'keywords' => input("keywords", ""),// 关键词
                'unit' => input("unit", ""),// 单位
                'video_url' => input("video_url", ""),// 视频
                'goods_sku_data' => input("goods_sku_data", ""),// SKU商品数据
                'max_buy' => input("max_buy", 0),// 限购
                'min_buy' => input("min_buy", 0),// 起售
                'spec_type_status' => input('spec_type_status', 0),
                'site_id' => $this->supply_id,
                'site_name' => $this->supply_info[ 'title' ],
                'website_id' => $this->website_id,

                'timer_on' => strtotime(input('timer_on', 0)),//定时上架
                'timer_off' => strtotime(input('timer_off', 0)),//定时下架

                'brand_id' => input("brand_id", 0),//品牌id
                'brand_name' => input("brand_name", ''),//品牌名称
            ];

            $res = $goods_model->addGoods($data);
            return $res;
        } else {

            //获取一级商品分类
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                [ 'pid', '=', 0 ]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate,supply_commission_rate');
            $goods_category_list = $goods_category_list[ 'data' ];
            $this->assign("goods_category_list", $goods_category_list);

            //获取品牌
            $goods_brand_model = new GoodsBrandModel();
            $brand_list = $goods_brand_model->getBrandList([ [ 'site_id', 'in', ( "0,$this->supply_id" ) ] ], "brand_id, brand_name");
            $brand_list = $brand_list[ 'data' ];
            $this->assign("brand_list", $brand_list);

            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([ [ 'site_id', "=", $this->supply_id ] ], 'template_id,template_name', 'is_default desc');
            $express_template_list = $express_template_list[ 'data' ];
            $this->assign("express_template_list", $express_template_list);

            //获取商品类型
            $goods_attr_model = new GoodsAttributeModel();
            $attr_class_list = $goods_attr_model->getAttrClassList([ [ 'site_id', 'in', ( "0,$this->supply_id" ) ] ], 'class_id,class_name');
            $attr_class_list = $attr_class_list[ 'data' ];
            $this->assign("attr_class_list", $attr_class_list);

            return $this->fetch("goods/add_goods");
        }
    }

    /**
     * 编辑商品
     * @return mixed
     */
    public function editGoods()
    {
        $goods_id = input("goods_id", 0);// 商品id
        $goods_model = new GoodsModel();
        if (request()->isAjax()) {

            $goods_model = new GoodsModel();
            if ($this->supply_info[ "status" ] == 0) {
                return $goods_model->error([], "当前供应商处于关闭状态!");
            }
            $category_array = input("category_id", 0);// 分类id
            $category_json = json_encode($category_array);//分类字符串
            $category_id = ',' . implode(',', $category_array) . ',';
            $data = [
                'goods_id' => $goods_id,
                'goods_name' => input("goods_name", ""),// 商品名称,
                'goods_attr_class' => input("goods_attr_class", ""),// 商品类型id,
                'goods_attr_name' => input("goods_attr_name", ""),// 商品类型名称,
                'category_id' => $category_id,
                'category_array' => $category_array,//商品分类数组
                'category_json' => $category_json,
                'goods_image' => input("goods_image", ""),// 商品主图路径
                'goods_content' => input("goods_content", ""),// 商品详情
                'goods_state' => input("goods_state", ""),// 商品状态（1.正常0下架）
                'price' => input("price", 0),// 商品价格（取第一个sku）
                'market_price' => input("market_price", 0),// 市场价格（取第一个sku）
                'cost_price' => input("cost_price", 0),// 成本价（取第一个sku）
                'sku_no' => input("sku_no", ""),// 商品sku编码
                'weight' => input("weight", ""),// 重量
                'volume' => input("volume", ""),// 体积
                'goods_stock' => input("goods_stock", 0),// 商品库存（总和）
                'goods_stock_alarm' => input("goods_stock_alarm", 0),// 库存预警
                'is_free_shipping' => input("is_free_shipping", 1),// 是否免邮
                'shipping_template' => input("shipping_template", 0),// 指定运费模板
                'goods_spec_format' => input("goods_spec_format", ""),// 商品规格格式
                'goods_attr_format' => input("goods_attr_format", ""),// 商品属性格式
                'introduction' => input("introduction", ""),// 促销语
                'keywords' => input("keywords", ""),// 关键词
                'unit' => input("unit", ""),// 单位
                'video_url' => input("video_url", ""),// 视频
                'goods_sku_data' => input("goods_sku_data", ""),// SKU商品数据
                'max_buy' => input("max_buy", 0),// 限购
                'min_buy' => input("min_buy", 0),// 起售
                'spec_type_status' => input('spec_type_status', 0),
                'site_id' => $this->supply_id,
                'site_name' => $this->supply_info[ 'title' ],
                'website_id' => $this->website_id,

                'timer_on' => strtotime(input('timer_on', 0)),//定时上架
                'timer_off' => strtotime(input('timer_off', 0)),//定时下架

                'brand_id' => input("brand_id", 0),//品牌id
                'brand_name' => input("brand_name", ''),//品牌名称
            ];

            $res = $goods_model->editGoods($data);
            return $res;
        } else {
            $goods_info = $goods_model->editGetGoodsInfo([ [ 'goods_id', '=', $goods_id ], [ 'site_id', '=', $this->supply_id ] ]);
            $goods_info = $goods_info[ 'data' ];

            $goods_sku_list = $goods_model->getGoodsSkuList([ [ 'goods_id', '=', $goods_id ], [ 'site_id', '=', $this->supply_id ] ], "sku_id,sku_name,sku_no,sku_spec_format,price_json,market_price,cost_price,stock,weight,volume,sku_image,sku_images,goods_spec_format,spec_name,stock_alarm,is_default");
            $goods_sku_list = $goods_sku_list[ 'data' ];
            $goods_info[ 'sku_list' ] = $goods_sku_list;
            $this->assign("goods_info", $goods_info);
            //获取一级商品分类
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                [ 'pid', '=', 0 ]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate,supply_commission_rate');
            $goods_category_list = $goods_category_list[ 'data' ];
            $this->assign("goods_category_list", $goods_category_list);

            //获取品牌
            $goods_brand_model = new GoodsBrandModel();
            $brand_list = $goods_brand_model->getBrandList([ [ 'site_id', 'in', ( "0,$this->supply_id" ) ] ], "brand_id, brand_name");
            $brand_list = $brand_list[ 'data' ];
            $this->assign("brand_list", $brand_list);

            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([ [ 'site_id', "=", $this->supply_id ] ], 'template_id,template_name', 'is_default desc');
            $express_template_list = $express_template_list[ 'data' ];
            $this->assign("express_template_list", $express_template_list);

            //获取商品类型
            $goods_attr_model = new GoodsAttributeModel();
            $attr_class_list = $goods_attr_model->getAttrClassList([ [ 'site_id', 'in', ( "0,$this->supply_id" ) ] ], 'class_id,class_name');
            $attr_class_list = $attr_class_list[ 'data' ];
            $this->assign("attr_class_list", $attr_class_list);

            return $this->fetch("goods/edit_goods");
        }
    }

    /**
     * 删除商品
     */
    public function deleteGoods()
    {
        if (request()->isAjax()) {
            $goods_ids = input("goods_ids", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->modifyIsDelete($goods_ids, 1, $this->supply_id);
            return $res;
        }

    }

    /**
     * 商品回收站
     */
    public function recycle()
    {
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_keys = input('search_keys', "");
            $condition = [ [ 'is_delete', '=', 1 ], [ 'site_id', "=", $this->supply_id ] ];
            if (!empty($search_keys)) {
                $condition[] = [ 'goods_name', 'like', '%' . $search_keys . '%' ];
            }
            $goods_model = new GoodsModel();
            $res = $goods_model->getGoodsPageList($condition, $page_index, $page_size);
            return $res;
        } else {
            return $this->fetch("goods/recycle");
        }
    }

    /**
     * 商品回收站商品删除
     */
    public function deleteRecycleGoods()
    {
        if (request()->isAjax()) {
            $goods_ids = input("goods_ids", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->deleteRecycleGoods($goods_ids, $this->supply_id);
            return $res;
        }
    }

    /**
     * 商品回收站商品恢复
     */
    public function recoveryRecycle()
    {
        if (request()->isAjax()) {
            $goods_ids = input("goods_ids", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->modifyIsDelete($goods_ids, 0, $this->supply_id);
            return $res;
        }

    }

    /**
     * 商品下架
     */
    public function offGoods()
    {
        if (request()->isAjax()) {
            $goods_ids = input("goods_ids", 0);
            $goods_state = input("goods_state", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->modifyGoodsState($goods_ids, $goods_state, $this->supply_id);
            return $res;
        }

    }

    /**
     * 商品上架
     */
    public function onGoods()
    {
        if (request()->isAjax()) {
            $goods_ids = input("goods_ids", 0);
            $goods_state = input("goods_state", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->modifyGoodsState($goods_ids, $goods_state, $this->supply_id);
            return $res;
        }
    }


    /**
     * 获取商品分类列表
     * @return \multitype
     */
    public function getCategoryList()
    {
        if (request()->isAjax()) {
            $category_id = input("category_id", 0);
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                [ 'pid', '=', $category_id ]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate,supply_commission_rate');
            return $goods_category_list;
        }
    }

    /**
     * 获取商品规格列表
     * @return \multitype
     */
    public function getSpecList()
    {
        if (request()->isAjax()) {

            $attr_id = input("attr_id", "");//排除已存在的规格项
            $search_text = input("search_text", "");
            $condition = [ [ 'is_spec', '=', 1 ], [ 'site_id', 'in', ( "0,$this->supply_id" ) ] ];
            if (!empty($attr_id)) {
                $condition[] = [ 'attr_id', 'not in', $attr_id ];
            }
            if (!empty($search_text)) {
                $condition[] = [ 'attr_name', 'like', '%' . $search_text . '%' ];
            }
            $goods_attr_model = new GoodsAttributeModel();
            $spec_list = $goods_attr_model->getSpecList($condition, 'attr_id,attr_name,attr_class_name', 'attr_id desc', PAGE_LIST_ROWS);
            return $spec_list;
        }
    }


    /**
     * 获取商品规格值列表
     * @return \multitype
     */
    public function getSpecValueList()
    {
        if (request()->isAjax()) {

            $attr_id = input("attr_id", 0);
            $search_text = input("search_text", "");
            $condition = [];
            if (!empty($attr_id)) {
                $condition[] = [ 'attr_id', '=', $attr_id ];
            }
            if (!empty($search_text)) {
                $condition[] = [ 'attr_value_name', 'like', '%' . $search_text . '%' ];
            }

            $goods_attr_model = new GoodsAttributeModel();
            $spec_list = $goods_attr_model->getSpecValueList($condition, 'attr_value_id,attr_value_name');
            return $spec_list;
        }
    }

    /**
     * 获取商品属性列表
     * @return \multitype
     */
    public function getAttributeList()
    {

        if (request()->isAjax()) {
            $goods_attr_model = new GoodsAttributeModel();
            $attr_class_id = input('attr_class_id', 0);// 商品类型id
            $attribute_list = $goods_attr_model->getAttributeList([ [ 'attr_class_id', '=', $attr_class_id ], [ 'is_spec', '=', 0 ], [ 'site_id', 'in', ( "0,$this->supply_id" ) ] ], 'attr_id,attr_name,attr_class_id,attr_class_name,attr_type,attr_value_format');
            if (!empty($attribute_list[ 'data' ])) {
                foreach ($attribute_list[ 'data' ] as $k => $v) {
                    if (!empty($v[ 'attr_value_format' ])) {
                        $attribute_list[ 'data' ][ $k ][ 'attr_value_format' ] = json_decode($v[ 'attr_value_format' ], true);
                    }
                }
            }

            return $attribute_list;
        }
    }

    /**
     * 获取SKU商品列表
     * @return \multitype
     */
    public function getGoodsSkuList()
    {
        if (request()->isAjax()) {
            $goods_id = input("goods_id", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->getGoodsSkuList([ [ 'goods_id', '=', $goods_id ], [ 'site_id', '=', $this->supply_id ] ], 'sku_id,sku_name,min_price,max_price,min_num,market_price,cost_price,stock,weight,volume,sku_no,sale_num,sku_image,spec_name,goods_id');
            return $res;
        }
    }

    /**
     * 获取违规下架原因
     * @return \multitype
     */
    public function getVerifyStateRemark()
    {
        if (request()->isAjax()) {
            $goods_id = input("goods_id", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->getGoodsInfo([ [ 'goods_id', '=', $goods_id ], [ 'verify_state', 'in', [ -2, 10 ] ], [ 'site_id', '=', $this->supply_id ] ], 'verify_state_remark');
            return $res;
        }
    }

    /**
     * 商品选择组件
     * @return \multitype
     */

    /***********************************************************商品评价**************************************************/

    /**
     * 商品评价
     */
    public function evaluate()
    {
        $goods_evaluate = new GoodsEvaluateModel();

        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $explain_type = input('explain_type', ''); //1好评2中评3差评
            $is_show = input('is_show', ''); //1显示 0隐藏
            $search_text = input('search_text', ''); //搜索值
            $search_type = input('search_type', ''); //搜索类型
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            $condition = [
                [ "site_id", "=", $this->supply_id ]
            ];
            //评分类型
            if ($explain_type != "") {
                $condition[] = [ "explain_type", "=", $explain_type ];
            }
            if ($is_show != "") {
                $condition[] = [ "is_show", "=", $is_show ];
            }

            if (!empty($search_text)) {
                if (!empty($search_type)) {
                    $condition[] = [ $search_type, 'like', '%' . $search_text . '%' ];
                } else {
                    $condition[] = [ 'sku_name', 'like', '%' . $search_text . '%' ];
                }
            }
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ "create_time", ">=", date_to_time($start_time) ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ "create_time", "<=", date_to_time($end_time) ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            }
            return $goods_evaluate->getEvaluatePageList($condition, $page_index, $page_size, "create_time desc");
        } else {
            return $this->fetch("goods/evaluate");
        }

    }

    /**
     * 商品评价删除
     */
    public function deleteEvaluate()
    {

        if (request()->isAjax()) {
            $goods_evaluate = new GoodsEvaluateModel();
            $evaluate_id = input("evaluate_id", 0);
            return $goods_evaluate->deleteEvaluate($evaluate_id, $this->supply_id);
        }
    }

    /**
     * 商品评价回复
     */
    public function evaluateApply()
    {
        if (request()->isAjax()) {
            $goods_evaluate = new GoodsEvaluateModel();
            $evaluate_id = input("evaluate_id", 0);
            $explain = input("explain", 0);
            $is_first_explain = input("is_first_explain", 0);// 是否第一次回复
            $data = [
                'evaluate_id' => $evaluate_id
            ];
            if ($is_first_explain == 0) {
                $data[ 'explain_first' ] = $explain;
            } elseif ($is_first_explain == 1) {
                $data[ 'again_explain' ] = $explain;
            }

            return $goods_evaluate->evaluateApply($data);
        }
    }

    /**
     * 获取商品分类
     * @return \multitype
     */
    public function getCategoryByParent()
    {
        $pid = input('pid', 0);// 上级id
        $level = input('level', 0);// 层级
        $goods_category_model = new GoodsCategory();
        if (!empty($level)) {
            $condition[] = [ 'level', '=', $level ];
        }
        if (!empty($pid)) {
            $condition[] = [ 'pid', '=', $pid ];
        }
        $list = $goods_category_list = $goods_category_model->getCategoryByParent($condition, 'category_id,category_name,pid,level,category_id_1,category_id_2,category_id_3');
        return $list;
    }

    /**
     * 商品批量配置
     */
    public function batchGoods()
    {
        if (request()->isAjax()) {
            $goods_ids = input('goods_ids', '');//选择的商品数量
            $type = input('type', '');
            $condition = array (
                [ 'site_id', '=', $this->supply_id ],
                [ 'goods_id', 'in', $goods_ids ],
            );

            $goods_model = new GoodsModel();
            switch ( $type ) {
                case 'category':
                    $category = input('category', 0);//分类
                    $result = $goods_model->modifyCategory($category, $condition);
                    break;
                case 'shipping':
                    $is_free_shipping = input('is_free_shipping', 0);//是否免邮
                    $shipping_template = input('shipping_template', 0);//运费模板
                    $result = $goods_model->modifyGoodsShipping($is_free_shipping, $shipping_template, $condition);
                    break;
            }
            return $result;
        }
    }
}