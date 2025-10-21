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

namespace app\shopapi\controller;

use app\model\goods\VirtualGoods as VirtualGoodsModel;

/**
 * 虚拟商品
 * Class Virtualgoods
 * @package app\shop\controller
 */
class Virtualgoods extends BaseApi
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) {
            echo $this->response($token);
            exit;
        }
    }

    /**
     * 添加商品
     * @return mixed
     */
    public function addGoods()
    {
        $category_array = isset($this->params[ 'category_id' ]) ? $this->params[ 'category_id' ] : 0;// 分类id
        $category_json = '["' . $category_array . '"]';//分类字符串
        if (!empty($category_array)) {
            $category_array = explode(",", $category_array);
        }
        $category_id = ',' . implode(',', $category_array) . ',';

        $data = [
            'goods_name' => isset($this->params[ 'goods_name' ]) ? $this->params[ 'goods_name' ] : '',// 商品名称,
            'goods_attr_class' => isset($this->params[ 'goods_attr_class' ]) ? $this->params[ 'goods_attr_class' ] : '',// 商品类型id,
            'goods_attr_name' => isset($this->params[ 'goods_attr_name' ]) ? $this->params[ 'goods_attr_name' ] : '',// 商品类型名称,
            'site_id' => $this->site_id,
            'category_id' => $category_id,
            'category_array' => $category_array,//商品分类数组
            'category_json' => $category_json,
            'goods_image' => isset($this->params[ 'goods_image' ]) ? $this->params[ 'goods_image' ] : '',// 商品主图路径
            'goods_content' => isset($this->params[ 'goods_content' ]) ? $this->params[ 'goods_content' ] : '',// 商品详情
            'goods_state' => isset($this->params[ 'goods_state' ]) ? $this->params[ 'goods_state' ] : '',// 商品状态（1.正常0下架）
            'price' => isset($this->params[ 'price' ]) ? $this->params[ 'price' ] : 0,// 商品价格（取第一个sku）
            'market_price' => isset($this->params[ 'market_price' ]) ? $this->params[ 'market_price' ] : '',// 市场价格（取第一个sku）
            'cost_price' => isset($this->params[ 'cost_price' ]) ? $this->params[ 'cost_price' ] : 0,// 成本价（取第一个sku）
            'sku_no' => isset($this->params[ 'sku_no' ]) ? $this->params[ 'sku_no' ] : '',// 商品sku编码
            'weight' => isset($this->params[ 'weight' ]) ? $this->params[ 'weight' ] : '',// 重量
            'volume' => isset($this->params[ 'volume' ]) ? $this->params[ 'volume' ] : '',// 体积
            'goods_stock' => isset($this->params[ 'goods_stock' ]) ? $this->params[ 'goods_stock' ] : 0,// 商品库存（总和）

            'goods_stock_alarm' => isset($this->params[ 'goods_stock_alarm' ]) ? $this->params[ 'goods_stock_alarm' ] : 0,// 库存预警
            'is_free_shipping' => isset($this->params[ 'is_free_shipping' ]) ? $this->params[ 'is_free_shipping' ] : 1,// 是否免邮
            'shipping_template' => isset($this->params[ 'shipping_template' ]) ? $this->params[ 'shipping_template' ] : 0,// 指定运费模板
            'goods_spec_format' => isset($this->params[ 'goods_spec_format' ]) ? $this->params[ 'goods_spec_format' ] : '',// 商品规格格式
            'goods_attr_format' => isset($this->params[ 'goods_attr_format' ]) ? $this->params[ 'goods_attr_format' ] : '',// 商品属性格式
            'introduction' => isset($this->params[ 'introduction' ]) ? $this->params[ 'introduction' ] : '',// 促销语
            'keywords' => isset($this->params[ 'keywords' ]) ? $this->params[ 'keywords' ] : '',// 关键词
            'unit' => isset($this->params[ 'unit' ]) ? $this->params[ 'unit' ] : '',// 单位
            'sort' => isset($this->params[ 'sort' ]) ? $this->params[ 'sort' ] : 0,// 排序,
            'video_url' => isset($this->params[ 'video_url' ]) ? $this->params[ 'video_url' ] : '',// 视频
            'goods_sku_data' => isset($this->params[ 'goods_sku_data' ]) ? $this->params[ 'goods_sku_data' ] : '',// SKU商品数据
            'label_id' => isset($this->params[ 'label_id' ]) ? $this->params[ 'label_id' ] : '',// 商品分组id
            'max_buy' => isset($this->params[ 'max_buy' ]) ? $this->params[ 'max_buy' ] : '',// 限购
            'min_buy' => isset($this->params[ 'min_buy' ]) ? $this->params[ 'min_buy' ] : '',// 起售
            'timer_on' => isset($this->params[ 'timer_on' ]) ? strtotime($this->params[ 'timer_on' ]) : 0,//定时上架
            'timer_off' => isset($this->params[ 'timer_off' ]) ? strtotime($this->params[ 'timer_off' ]) : 0,//定时下架

            'website_id' => $this->website_id,//城市分站id
            'site_name' => $this->shop_info[ 'site_name' ],//店铺名
            'brand_id' => isset($this->params[ 'brand_id' ]) ? $this->params[ 'brand_id' ] : '',//品牌id
            'brand_name' => isset($this->params[ 'brand_name' ]) ? $this->params[ 'brand_name' ] : '',//品牌名称
            'goods_shop_category_ids' => isset($this->params[ 'goods_shop_category_ids' ]) ? $this->params[ 'goods_shop_category_ids' ] : '',// 店内分类id,逗号隔开,//供应商id
            'supplier_id' => isset($this->params[ 'supplier_id' ]) ? $this->params[ 'supplier_id' ] : 0,//供应商id
            'virtual_indate' => isset($this->params[ 'virtual_indate' ]) ? $this->params[ 'virtual_indate' ] : 0,//有效期
        ];

        $virtual_goods_model = new VirtualGoodsModel();
        $res = $virtual_goods_model->addGoods($data);
        return $this->response($res);
    }

    /**
     * 编辑商品
     * @return mixed
     */
    public function editGoods()
    {
        $virtual_goods_model = new VirtualGoodsModel();

        $category_array = isset($this->params[ 'category_id' ]) ? $this->params[ 'category_id' ] : 0;// 分类id
        $category_json = '["' . $category_array . '"]';//分类字符串
        if (!empty($category_array)) {
            $category_array = explode(",", $category_array);
        }
        $category_id = ',' . implode(',', $category_array) . ',';

        $data = [
            'goods_id' => isset($this->params[ 'goods_id' ]) ? $this->params[ 'goods_id' ] : 0,// 商品id
            'goods_name' => isset($this->params[ 'goods_name' ]) ? $this->params[ 'goods_name' ] : '',// 商品名称,
            'goods_attr_class' => isset($this->params[ 'goods_attr_class' ]) ? $this->params[ 'goods_attr_class' ] : '',// 商品类型id,
            'goods_attr_name' => isset($this->params[ 'goods_attr_name' ]) ? $this->params[ 'goods_attr_name' ] : '',// 商品类型名称,
            'site_id' => $this->site_id,
            'category_id' => $category_id,
            'category_json' => $category_json,

            'goods_image' => isset($this->params[ 'goods_image' ]) ? $this->params[ 'goods_image' ] : '',// 商品主图路径
            'goods_content' => isset($this->params[ 'goods_content' ]) ? $this->params[ 'goods_content' ] : '',// 商品详情
            'goods_state' => isset($this->params[ 'goods_state' ]) ? $this->params[ 'goods_state' ] : '',// 商品状态（1.正常0下架）
            'price' => isset($this->params[ 'price' ]) ? $this->params[ 'price' ] : 0,// 商品价格（取第一个sku）
            'market_price' => isset($this->params[ 'market_price' ]) ? $this->params[ 'market_price' ] : '',// 市场价格（取第一个sku）
            'cost_price' => isset($this->params[ 'cost_price' ]) ? $this->params[ 'cost_price' ] : 0,// 成本价（取第一个sku）
            'sku_no' => isset($this->params[ 'sku_no' ]) ? $this->params[ 'sku_no' ] : '',// 商品sku编码
            'weight' => isset($this->params[ 'weight' ]) ? $this->params[ 'weight' ] : '',// 重量
            'volume' => isset($this->params[ 'volume' ]) ? $this->params[ 'volume' ] : '',// 体积
            'goods_stock' => isset($this->params[ 'goods_stock' ]) ? $this->params[ 'goods_stock' ] : 0,// 商品库存（总和）

            'goods_stock_alarm' => isset($this->params[ 'goods_stock_alarm' ]) ? $this->params[ 'goods_stock_alarm' ] : 0,// 库存预警
            'is_free_shipping' => isset($this->params[ 'is_free_shipping' ]) ? $this->params[ 'is_free_shipping' ] : 1,// 是否免邮
            'shipping_template' => isset($this->params[ 'shipping_template' ]) ? $this->params[ 'shipping_template' ] : 0,// 指定运费模板
            'goods_spec_format' => isset($this->params[ 'goods_spec_format' ]) ? $this->params[ 'goods_spec_format' ] : '',// 商品规格格式
            'goods_attr_format' => isset($this->params[ 'goods_attr_format' ]) ? $this->params[ 'goods_attr_format' ] : '',// 商品属性格式
            'introduction' => isset($this->params[ 'introduction' ]) ? $this->params[ 'introduction' ] : '',// 促销语
            'keywords' => isset($this->params[ 'keywords' ]) ? $this->params[ 'keywords' ] : '',// 关键词
            'unit' => isset($this->params[ 'unit' ]) ? $this->params[ 'unit' ] : '',// 单位
            'sort' => isset($this->params[ 'sort' ]) ? $this->params[ 'sort' ] : 0,// 排序,
            'video_url' => isset($this->params[ 'video_url' ]) ? $this->params[ 'video_url' ] : '',// 视频
            'goods_sku_data' => isset($this->params[ 'goods_sku_data' ]) ? $this->params[ 'goods_sku_data' ] : '',// SKU商品数据
            'label_id' => isset($this->params[ 'label_id' ]) ? $this->params[ 'label_id' ] : '',// 商品分组id
            'max_buy' => isset($this->params[ 'max_buy' ]) ? $this->params[ 'max_buy' ] : '',// 限购
            'min_buy' => isset($this->params[ 'min_buy' ]) ? $this->params[ 'min_buy' ] : '',// 起售
            'timer_on' => isset($this->params[ 'timer_on' ]) ? strtotime($this->params[ 'timer_on' ]) : 0,//定时上架
            'timer_off' => isset($this->params[ 'timer_off' ]) ? strtotime($this->params[ 'timer_off' ]) : 0,//定时下架

            'spec_type_status' => isset($this->params[ 'spec_type_status' ]) ? strtotime($this->params[ 'spec_type_status' ]) : 0,
            'category_array' => $category_array,
            'website_id' => $this->website_id,//城市分站id
            'site_name' => $this->shop_info[ 'site_name' ],//店铺名

            'brand_id' => isset($this->params[ 'brand_id' ]) ? $this->params[ 'brand_id' ] : '',//品牌id
            'brand_name' => isset($this->params[ 'brand_name' ]) ? $this->params[ 'brand_name' ] : '',//品牌名称
            'goods_shop_category_ids' => isset($this->params[ 'goods_shop_category_ids' ]) ? $this->params[ 'goods_shop_category_ids' ] : '',// 店内分类id,逗号隔开,//供应商id
            'supplier_id' => isset($this->params[ 'supplier_id' ]) ? $this->params[ 'supplier_id' ] : 0,//供应商id
            'virtual_indate' => isset($this->params[ 'virtual_indate' ]) ? $this->params[ 'virtual_indate' ] : 0,//有效期
        ];
        $res = $virtual_goods_model->editGoods($data);
        return $this->response($res);

    }

}