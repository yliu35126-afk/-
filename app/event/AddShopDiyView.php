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

namespace app\event;

use app\model\web\DiyView as DiyViewModel;


/**
 * 增加默认自定义数据：网站主页、底部导航
 */
class AddShopDiyView
{

    public function handle($param)
    {
        if (!empty($param[ 'site_id' ])) {

            $diy_view_model = new DiyViewModel();
            $page = $diy_view_model->getPage();
            $index_value = json_encode([
                "global" => [
                    "title" => "店铺主页",
                    "openBottomNav" => true,
                    "bgColor" => "#f8f8f8",
                    "bgUrl" => "",
                    "moreLink" => [
                        "name" => ""
                    ],
                    "mpCollect" => false,
                    "navStyle" => 1,
                    "popWindow" => [
                        "imageUrl" => "",
                        "count" => -1,
                        "link" => [
                            "name" => ""
                        ],
                        "imgWidth" => "",
                        "imgHeight" => ""
                    ],
                    "textImgPosLink" => "left",
                    "textImgStyleLink" => "1",
                    "textNavColor" => "#303133",
                    "topNavColor" => "#ffffff",
                    "topNavImg" => "",
                    "topNavbg" => false
                ],
                "value" => [
//                    [
//                        "addon_name" => "",
//                        "type" => "IMAGE_ADS",
//                        "name" => "图片广告",
//                        "controller" => "ImageAds",
//                        "selectedTemplate" => "carousel-posters",
//                        "imageClearance" => 0,
//                        "height" => 0,
//                        "list" => [
//                            [
//                                "imageUrl" => "upload/default/diy_view/posters.png",
//                                "title" => "",
//                                "link" => [
//                                  "name" => ""
//                                ],
//                            ]
//                        ]
//                    ],
                    [
                        "addon_name" => "",
                        "type" => "SHOP_INFO",
                        "name" => "店铺信息",
                        "controller" => "ShopInfo",
                        "color" => "#333333",
                        "is_delete" => "0"
                    ],
                    [
                        "title" => "搜索",
                        "textColor" => "#999999",
                        "textAlign" => "left",
                        "backgroundColor" => "#ffffff",
                        "bgColor" => "#e8e8e8",
                        "defaultTextColor" => "#999999",
                        "borderType" => 2,
                        "searchType" => 1,
                        "searchImg" => "",
                        "searchStyle" => 1,
                        "addon_name" => "",
                        "type" => "SHOP_SEARCH",
                        "name" => "店内搜索",
                        "controller" => "ShopSearch",
                        "is_delete" => "0"
                    ],
                    [
                        "height" => 10,
                        "backgroundColor" => "#f4f4f4",
                        "addon_name" => "",
                        "type" => "HORZ_BLANK",
                        "name" => "辅助空白",
                        "controller" => "HorzBlank"
                    ],
                    [
                        "sources" => "default",
                        "categoryId" => 0,
                        "categoryName" => "请选择",
                        "goodsCount" => "6",
                        "goodsId" => [],
                        "style" => 1,
                        "backgroundColor" => "",
                        "marginTop" => 0,
                        "paddingLeftRight" => 0,
                        "isShowCart" => 0,
                        "cartStyle" => 1,
                        "isShowGoodName" => 1,
                        "isShowMarketPrice" => 1,
                        "isShowGoodSaleNum" => 1,
                        "isShowGoodSubTitle" => 0,
                        "goodsTag" => "default",
                        "tagImg" => [
                            "imageUrl" => ""
                        ],
                        "addon_name" => "",
                        "type" => "GOODS_LIST",
                        "name" => "商品列表",
                        "controller" => "GoodsList",
                        "is_delete" => "0"
                    ],
                    [
                        "height" => 10,
                        "backgroundColor" => "#f4f4f4",
                        "addon_name" => "",
                        "type" => "HORZ_BLANK",
                        "name" => "辅助空白",
                        "controller" => "HorzBlank"
                    ],
                    [
                        "addon_name" => "",
                        "type" => "SHOP_STORE",
                        "name" => "门店",
                        "controller" => "ShopStore"
                    ],
                ]
            ]);

            $goods_category_value = json_encode([
                "global" => [
                    "title" => "商品分类",
                    "openBottomNav" => false,
                    "bgColor" => "#ffffff",
                    "bgUrl" => ""
                ],
                "value" => [
                    [
                        "addon_name" => "",
                        "type" => "GOODS_CATEGORY",
                        "name" => "商品分类",
                        "controller" => "GoodsCategory",
                        "level" => 2,
                        "template" => 2
                    ]
                ]
            ]);

            // 网站主页
            $data = [ [
                'site_id' => $param[ 'site_id' ],
                'title' => '店铺主页',
                'name' => $page[ 'shop' ][ 'index' ][ 'name' ],
                'type' => $page[ 'shop' ][ 'port' ],
                'value' => $index_value
            ], [
                'site_id' => $param[ 'site_id' ],
                'title' => '商品分类',
                'name' => $page[ 'shop' ][ 'goods_category' ][ 'name' ],
                'type' => $page[ 'shop' ][ 'port' ],
                'value' => $goods_category_value
            ] ];

            $res = $diy_view_model->addSiteDiyViewList($data);

            // 底部导航
            $value = json_encode([
                "type" => 1,
                "backgroundColor" => "#ffffff",
                "textColor" => "#333333",
                "textHoverColor" => "#ff0036",
                "bulge" => false,
                "list" => [
                    [
                        "iconPath" => "upload/default/diy_view/bottom/shop_index.png",
                        "selectedIconPath" => "upload/default/diy_view/bottom/shop_index_selected.png",
                        "text" => "首页",
                        "link" => [
                            "name" => "SHOP_INDEX",
                            "menuOne" => "MALL_PAGE",
                            "menuTwo" => "MALL_LINK",
                            "type" => 1,
                            "wap_url" => "/otherpages/shop/index/index",
                            "title" => "店铺首页"
                        ]
                    ],
                    [
                        "iconPath" => "upload/default/diy_view/bottom/shop_category.png",
                        "selectedIconPath" => "upload/default/diy_view/bottom/shop_category_selected.png",
                        "text" => "分类",
                        "link" => [
                            "name" => "SHOP_CATEGORY",
                            "menuOne" => "MALL_PAGE",
                            "menuTwo" => "MALL_LINK",
                            "type" => 1,
                            "wap_url" => "/otherpages/shop/category/category",
                            "title" => "店铺商品分类"
                        ]
                    ],
                    [
                        "iconPath" => "upload/default/diy_view/bottom/shop_list.png",
                        "selectedIconPath" => "upload/default/diy_view/bottom/shop_list_selected.png",
                        "text" => "宝贝",
                        "link" => [
                            "name" => "SHOP_LIST",
                            "menuOne" => "MALL_PAGE",
                            "menuTwo" => "MALL_LINK",
                            "type" => 1,
                            "wap_url" => "/otherpages/shop/list/list",
                            "title" => "店铺商品列表"
                        ]
                    ],
                    [
                        "iconPath" => "upload/default/diy_view/bottom/shop_introduce.png",
                        "selectedIconPath" => "upload/default/diy_view/bottom/shop_introduce_selected.png",
                        "text" => "介绍",
                        "link" => [
                            "name" => "SHOP_INTRODUCE",
                            "menuOne" => "MALL_PAGE",
                            "menuTwo" => "MALL_LINK",
                            "type" => 1,
                            "wap_url" => "/otherpages/shop/introduce/introduce",
                            "title" => "店铺介绍"
                        ]
                    ]
                ]
            ]);

            // 店铺底部导航
            $res = $diy_view_model->setShopBottomNavConfig($value, $param[ 'site_id' ]);
            return $res;

        }

    }

}