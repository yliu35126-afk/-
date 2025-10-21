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
return [
    'template' => [
        [
            'name' => 'DIY_VIEW_INDEX',
            'title' => '网站主页',
            'value' => '',
            'type' => 'ADMIN',
            'icon' => ''
        ],
        [
            'name' => 'DIY_VIEW_SHOP_INDEX',
            'title' => '店铺主页',
            'value' => '',
            'type' => 'SHOP',
            'icon' => ''
        ],
    ],
    'util' => [
        [
            'name' => 'TEXT',
            'title' => '文本',
            'type' => 'SYSTEM',
            'controller' => 'Text',
            'value' => '{ title : "『文本』", textColor : "#333333", "defaultTextColor": "#333333", "alignStyle": "center", subTitle : "副标题", "marginTop": 0, "padding": 0, backgroundColor : "", "link" : {name:""}, "fontSize" : 16, "fontSizeSub" : 14, "colorSub": "#999", "defaultColorSub": "#999", "style": 1, "sub": 0, "styleName": "模板一", "isShowMore": 0, "fontWeight": 600, "moreText": "查看更多", "moreLink" : {name:""}, "btnColor": "#999", "defaultBtnColor": "#999" }',
            'sort' => '10000',
            'support_diy_view' => '',
            'max_count' => 0,
            'is_delete' => 0,
            'icon' => 'iconbiaoti',
        ],
        /* [
            'name' => 'TEXT_NAV',
            'title' => '文本导航',
            'type' => 'SYSTEM',
            'controller' => 'TextNav',
            'value' => '{ fontSize : 14, textColor : "#333333", "defaultTextColor" : "#333333", textAlign : "left", backgroundColor : "", arrangement : "vertical", list : [{ text : "『文本导航』",secondText : "","link" : {name:""}}] }',
            'sort' => '10001',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'public/static/ext/diyview/img/icon/text_nav.png',
            'icon_selected' => 'public/static/ext/diyview/img/icon/text_nav_selected.png'
        ], */
        [
            'name' => 'NOTICE',
            'title' => '公告',
            'type' => 'SYSTEM',
            'controller' => 'Notice',
            'value' => '{ "sources": "default","backgroundColor": "", "marginTop": 0, "style": 1, "isEdit": 1, "styleName": "风格一", "textColor": "#333333", "defaultTextColor": "#333333", "fontSize": 14,"list": [{"title": "公告","link": {name:""}}], "noticeIds": []}',
            'sort' => '10002',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'icongonggao1'
        ],
        [
            'name' => 'GRAPHIC_NAV',
            'title' => '图文导航',
            'type' => 'SYSTEM',
            'controller' => 'GraphicNav',
            'value' => '{ "textColor": "#666666","defaultTextColor": "#666666", "navRadius": "fillet", "backgroundColor": "","selectedTemplate": "imageNavigation", "showType": 4, "scrollSetting": "fixed", padding : 20, "marginTop": 0, "list": [{"imageUrl": "","title": "","link": {name:""}, "imgWidth": 0, "imgHeight": 0},{"imageUrl": "","title": "","link": {name:""}, "imgWidth": 0, "imgHeight": 0},{"imageUrl": "","title": "","link": {name:""}, "imgWidth": 0, "imgHeight": 0},{"imageUrl": "","title": "","link": {name:""}, "imgWidth": 0, "imgHeight": 0}]}',
            'sort' => '10003',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'icontuwendaohang'
        ],
        [
            'name' => 'IMAGE_ADS',
            'title' => '图片广告',
            'type' => 'SYSTEM',
            'controller' => 'ImageAds',
            'value' => '{ selectedTemplate : "carousel-posters", imageClearance : 0, "imageRadius": "right-angle", "carouselChangeStyle": "circle", "marginTop": 0, padding : 0, height : 0, list : [ { imageUrl : "", title : "", "link" : {name:""}, "imgWidth": 0, "imgHeight": 0} ] }',
            'sort' => '10004',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'icontupianguanggao',
        ],
        [
            'name' => 'SEARCH',
            'title' => '商品搜索',
            'type' => 'SYSTEM',
            'controller' => 'Search',
            'value' => '{ title : "搜索", "textColor": "#999999", "textAlign" : "left", "backgroundColor" : "#ffffff", "bgColor": "#e8e8e8", "defaultTextColor": "#999999", "borderType": 2, "isPosition": 1, searchType:1,searchImg:"",searchStyle:1}',
            'sort' => '10005',
            'support_diy_view' => '',
            'max_count' => 1,
            'icon' => 'iconsousuokuang'
        ],
//        [
//            'name' => 'TITLE',
//            'title' => '顶部标题',
//            'type' => 'SYSTEM',
//            'controller' => 'Title',
//            'value' => '{ "title": "『顶部标题』","backgroundColor": "","textColor": "#000000","defaultTextColor": "#000000","isOpenOperation" : false,"leftLink" : {name:""},"rightLink" : {name:""},"operationName" : "操作","fontSize" : 16}',
//            'sort' => '10006',
//            'support_diy_view' => '',
//            'max_count' => 1,
//            'icon' => 'public/static/ext/diyview/img/icon/title.png',
//            'icon_selected' => 'public/static/ext/diyview/img/icon/title_selected.png'
//        ],
        [
            'name' => 'RICH_TEXT',
            'title' => '富文本',
            'type' => 'SYSTEM',
            'controller' => 'RichText',
            'value' => '{ "backgroundColor": "","padding": 10,"html" : "", "marginTop": 0}',
            'sort' => '10007',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'iconfuwenben'
        ],
        [
            'name' => 'RUBIK_CUBE',
            'title' => '魔方',
            'type' => 'SYSTEM',
            'controller' => 'RubikCube',
            'value' => '{ "selectedTemplate": "row1-of2","backgroundColor": "","list": [{ imageUrl : "", link : {name:""} },{ imageUrl : "", link : {name:""} }], "selectedRubikCubeArray" : [] ,"diyHtml": "","customRubikCube": 4,"heightArray": ["74.25px","59px","48.83px","41.56px"],"imageGap": 0}',
            'sort' => '10008',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'iconmofang'
        ],
        // [
        //     'name'             => 'CUSTOM_MODULE',
        //     'title'            => '自定义模块',
        //     'type'             => 'SYSTEM',
        //     'controller'       => '',
        //     'value'            => '',
        //     'sort'             => '10009',
        //     'support_diy_view' => '',
        //     'max_count'        => 0,
        //     'icon' => 'public/static/ext/diyview/img/icon/customizing.png',
        //     'icon_selected' => 'public/static/ext/diyview/img/icon/customizing_selected.png'
        // ],
        [
            'name' => 'HORZ_LINE',
            'title' => '辅助线',
            'type' => 'SYSTEM',
            'controller' => 'HorzLine',
            'value' => '{ "color" : "#000000", "defaultColor" : "#000000", "margin" : 0, "borderStyle": "solid", "padding": 0 }',
            'sort' => '10011',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'iconfuzhuxian'
        ],
        [
            'name' => 'HORZ_BLANK',
            'title' => '辅助空白',
            'type' => 'SYSTEM',
            'controller' => 'HorzBlank',
            'value' => '{ height : 10, backgroundColor : "", "marginLeftRight": 0 }',
            'sort' => '10012',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'iconfuzhukongbai'
        ],
        // [
        //     'name'             => 'VIDEO',
        //     'title'            => '视频',
        //     'type'             => 'SYSTEM',
        //     'controller'       => '',
        //     'value'            => '',
        //     'sort'             => '10013',
        //     'support_diy_view' => '',
        //     'max_count'        => 0,
        //     'icon' => 'public/static/ext/diyview/img/icon/video.png',
        //     'icon_selected' => 'public/static/ext/diyview/img/icon/video_selected.png'
        // ],
        // [
        //     'name'             => 'VOICE',
        //     'title'            => '语音',
        //     'type'             => 'SYSTEM',
        //     'controller'       => '',
        //     'value'            => '',
        //     'sort'             => '10014',
        //     'support_diy_view' => '',
        //     'max_count'        => 0,
        //     'icon' => 'public/static/ext/diyview/img/icon/sound.png',
        //     'icon_selected' => 'public/static/ext/diyview/img/icon/sound_selected.png'
        // ],
        [
            'name' => 'GOODS_LIST',
            'title' => '商品列表',
            'type' => 'SYSTEM',
            'controller' => 'GoodsList',
            'value' => '{ "sources" : "default", "categoryId" : 0, "categoryName": "请选择", "goodsCount" : "6", "goodsId": [], "style": 1, "backgroundColor": "", "marginTop": 0, "paddingLeftRight": 0, "isShowCart": 0, "cartStyle": 1, "isShowGoodName": 1, "isShowMarketPrice": 1, "isShowGoodSaleNum": 1, "isShowGoodSubTitle": 0, "goodsTag": "default", "tagImg": {"imageUrl": ""}}',
            'sort' => '10016',
            'support_diy_view' => '',
            'max_count' => 0,
            'icon' => 'iconshangpinliebiao'
        ],
        [
            'name' => 'SHOP_INFO',
            'title' => '店铺信息',
            'type' => 'SYSTEM',
            'controller' => 'ShopInfo',
            'value' => '{ "color" : "#333333" }',
            'sort' => '10017',
            'support_diy_view' => 'DIY_VIEW_SHOP_INDEX',
            'max_count' => 1,
            'icon' => 'iconmendianxinxi'
        ],
        // [
        //     'name' => 'RANK_LIST',
        //     'title' => '排行榜',
        //     'type' => 'SYSTEM',
        //     'controller' => 'RankList',
        //     'value' => '{ "sources" : "category", goodsId : "", "categoryId" : 0, "categoryLevel" : 1, "goodsCount" : "6" }',
        //     'sort' => '10018',
        //     'support_diy_view' => 'DIY_VIEW_SHOP_INDEX',
        //     'max_count' => 1,
        //     'icon' => 'public/static/ext/diyview/img/icon/paihang.png',
        //     'icon_selected' => 'public/static/ext/diyview/img/icon/paihang_selected.png',
        // ],
        [
            'name' => 'SHOP_SEARCH',
            'title' => '店内搜索',
            'type' => 'SYSTEM',
            'controller' => 'ShopSearch',
            'value' => '{ title : "搜索", "textColor": "#999999", "textAlign" : "left", "backgroundColor" : "#ffffff", "bgColor": "#e8e8e8", "defaultTextColor": "#999999", "borderType": 2 ,searchType:1,searchImg:"",searchStyle:1}',
            'sort' => '10019',
            'support_diy_view' => 'DIY_VIEW_SHOP_INDEX',
            'max_count' => 1,
            'icon' => 'iconsousuo11',
        ],
        [
            'name' => 'SHOP_STORE',
            'title' => '门店',
            'type' => 'SYSTEM',
            'controller' => 'ShopStore',
            'value' => '{}',
            'sort' => '10020',
            'support_diy_view' => 'DIY_VIEW_SHOP_INDEX',
            'max_count' => 1,
            'icon' => 'iconmendianzhuye',
        ],
        [
            'name' => 'GOODS_CATEGORY',
            'title' => '商品分类',
            'type' => 'SYSTEM',
            'controller' => 'GoodsCategory',
            'value' => '{"level":"1","template":"1"}',
            'sort' => '10021',
            'support_diy_view' => 'DIY_VIEW_INDEX,DIY_VIEW_SHOP_INDEX',
            'max_count' => 1,
            'icon' => 'iconshangpinfenlei'
        ],
        [
            'name' => 'TOP_CATEGORY',
            'title' => '分类导航',
            'type' => 'SYSTEM',
            'controller' => 'TopCategory',
            'value' => '{"title":"首页","selectColor":"#FF4544","nsSelectColor":"#333333",backgroundColor : "",styleType:"line"}',
            'sort' => '10022',
            'support_diy_view' => 'DIY_VIEW_INDEX,DIY_VIEW_SHOP_INDEX',
            'max_count' => 1,
            'icon' => 'iconfenleidaohang',
        ],
        [
            'name' => 'FLOAT_BTN',
            'title' => '浮动按钮',
            'type' => 'SYSTEM',
            'controller' => 'FloatBtn',
            'value' => '{ "textColor": "#ffffff", "backgroundColor": "",baseBtnBottom:0,"btnBottom":"0","bottomPosition": "4", subTitle : "", "list": [{"imageUrl": "","title": "","link": {name:""}}]}',
            'sort' => '10023',
            'support_diy_view' => '',
            'max_count' => 1,
            'icon' => 'iconfudonganniu'
        ],
        [
            'name' => 'SHOP_CONTENT',
            'title' => '店铺详情',
            'type' => 'SYSTEM',
            'controller' => 'ShopContent',
            'value' => '{"color":"#FF0150","addon_name":"","type":"SHOP_CONTENT","name":"内容展示","controller":"ShopContent","is_delete":"0","list":[{"type":1,"text":"首页","path":"upload/default/diy_view/shop_index/shop_index.png","select_path":"upload/default/diy_view/shop_index/shop_index_selected.png"},{"type":2,"text":"全部商品","path":"upload/default/diy_view/shop_index/shop_goods.png","select_path":"upload/default/diy_view/shop_index/shop_goods_selected.png"},{"type":3,"text":"上新商品","path":"upload/default/diy_view/shop_index/shop_new.png","select_path":"upload/default/diy_view/shop_index/shop_new_selected.png"},{"type":4,"text":"店铺活动","path":"upload/default/diy_view/shop_index/shop_promotion.png","select_path":"upload/default/diy_view/shop_index/shop_promotion_selected.png"}],"index":"1","sort":"1","lazyLoadCss":false,"lazyLoad":true,"outerCountJs":1}',
            'sort' => '10024',
            'support_diy_view' => 'DIY_VIEW_SHOP_INDEX',
            'max_count' => 1,
            'icon' => 'iconcaidan-'
        ],
    ],
    'link' => [
        [
            'name' => 'MALL_PAGE',
            'title' => '商城页面',
            'parent' => '',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 1,
            'child_list' => [
                [
                    'name' => 'MALL_LINK',
                    'title' => '商城链接',
                    'parent' => '',
                    'wap_url' => '',
                    'web_url' => '',
                    'sort' => 0,
                    'child_list' => [
                        [
                            'name' => 'BASICS_LINK',
                            'title' => '基础链接',
                            'parent' => '',
                            'wap_url' => '',
                            'web_url' => '',
                            'sort' => 0,
                            'child_list' => [
                                [
                                    'name' => 'INDEX',
                                    'title' => '主页',
                                    'parent' => '',
                                    'wap_url' => '/pages/index/index/index',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'GOODS_CATEGORY',
                                    'title' => '商品分类',
                                    'parent' => '',
                                    'wap_url' => '/pages/goods/category/category',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'SHOPPING_TROLLEY',
                                    'title' => '购物车',
                                    'parent' => '',
                                    'wap_url' => '/pages/goods/cart/cart',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'SHOPPING_NOTICE',
                                    'title' => '公告',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/notice/list/list',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'SHOPPING_HELP',
                                    'title' => '帮助',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/help/list/list',
                                    'web_url' => '',
                                    'sort' => 0
                                ]
                            ]
                        ],
                        [
                            'name' => 'SHOP_LINK',
                            'title' => '店铺链接',
                            'parent' => '',
                            'wap_url' => '',
                            'web_url' => '',
                            'sort' => 0,
                            'child_list' => [
                                [
                                    'name' => 'SHOP_STREET',
                                    'title' => '店铺街',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/shop/street/street',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'SHOP_INDEX',
                                    'title' => '店铺首页',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/shop/index/index',
                                    'web_url' => '',
                                    'support_diy_view' => 'DIY_VIEW_SHOP_INDEX',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'SHOP_INTRODUCE',
                                    'title' => '店铺介绍',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/shop/introduce/introduce',
                                    'web_url' => '',
                                    'support_diy_view' => 'DIY_VIEW_SHOP_INDEX',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'SHOP_CATEGORY',
                                    'title' => '店内分类',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/shop/category/category',
                                    'web_url' => '',
                                    'support_diy_view' => 'DIY_VIEW_SHOP_INDEX',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'SHOP_LIST',
                                    'title' => '店铺列表',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/shop/list/list',
                                    'web_url' => '',
                                    'support_diy_view' => 'DIY_VIEW_SHOP_INDEX',
                                    'sort' => 0
                                ]
                            ]
                        ],
                        [
                            'name' => 'MEMBER',
                            'title' => '会员中心',
                            'parent' => '',
                            'wap_url' => '',
                            'web_url' => '',
                            'sort' => 1,
                            'child_list' => [
                                [
                                    'name' => 'MEMBER_CENTER',
                                    'title' => '会员中心',
                                    'parent' => '',
                                    'wap_url' => '/pages/member/index/index',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'OBLIGATION_ORDER',
                                    'title' => '待付款订单',
                                    'parent' => '',
                                    'wap_url' => '/pages/order/list/list?status=waitpay',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'DELIVER_ORDER',
                                    'title' => '待发货订单',
                                    'parent' => '',
                                    'wap_url' => '/pages/order/list/list?status=waitsend',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'TAKE_DELIVER_ORDER',
                                    'title' => '待收货订单',
                                    'parent' => '',
                                    'wap_url' => '/pages/order/list/list?status=waitconfirm',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'EVALUATE_ORDER',
                                    'title' => '待评价订单',
                                    'parent' => '',
                                    'wap_url' => '/pages/order/list/list?status=waitrate',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'REFUND_ORDER',
                                    'title' => '退款订单',
                                    'parent' => '',
                                    'wap_url' => '/pages/order/activist/activist',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'MEMBER_INFO',
                                    'title' => '个人资料',
                                    'parent' => '',
                                    'wap_url' => '/pages/member/info/info',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'SHIPPING_ADDRESS',
                                    'title' => '收货地址',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/member/address/address',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'BALANCE',
                                    'title' => '我的余额',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/member/balance/balance',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'MEMBER_INTEGRAL',
                                    'title' => '我的积分',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/member/point/point',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'SIGN_IN',
                                    'title' => '签到',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/member/signin/signin',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'MEMBER_LEVEL',
                                    'title' => '会员等级',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/member/level/level',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'FOOTPRINT',
                                    'title' => '我的足迹',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/member/footprint/footprint',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'ATTENTION',
                                    'title' => '我的关注',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/member/collection/collection',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'ACCOUNT',
                                    'title' => '账户列表',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/member/account/account',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'COUPON',
                                    'title' => '优惠券',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/member/coupon/coupon',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                                [
                                    'name' => 'VERIFICATION_PLATFORM',
                                    'title' => '核销台',
                                    'parent' => '',
                                    'wap_url' => '/otherpages/verification/index/index',
                                    'web_url' => '',
                                    'sort' => 0
                                ],
                            ]
                        ],
                    ]
                ],

                [
                    'name' => 'MIC_PAGE',
                    'title' => '微页面',
                    'parent' => '',
                    'wap_url' => '/pages/index/index/index',
                    'web_url' => '',
                    'sort' => 1,
                    'child_list' => []
                ],
                [
                    'name' => 'MARKETING_LINK',
                    'title' => '营销链接',
                    'parent' => '',
                    'wap_url' => '/pages/index/index/index',
                    'web_url' => '',
                    'sort' => 2,
                    'child_list' => []
                ],
                [
                    'name' => 'GOODS_CATEGORY',
                    'title' => '商品分类',
                    'parent' => '',
                    'wap_url' => '',
                    'web_url' => '',
                    'sort' => 3,
                    'child_list' => []
                ]
            ]
        ],
        [
            'name' => 'COMMODITY',
            'title' => '商品',
            'parent' => '',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 2,
            'child_list' => [
                [
                    'name' => 'ALL_GOODS',
                    'title' => '全部商品',
                    'parent' => '',
                    'wap_url' => '',
                    'web_url' => '',
                    'child_list' => [],
                    'sort' => 1,
                ]
            ]
        ],

        [
            'name' => 'GAME',
            'title' => '小游戏',
            'parent' => '',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 3,
            'child_list' => []
        ],
        [
            'name' => 'CUSTOM_LINK',
            'title' => '自定义链接',
            'parent' => '',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 4,
            'child_list' => []
        ],
        [
            'name' => 'OTHER_APPLET',
            'title' => '其他小程序',
            'parent' => '',
            'wap_url' => '',
            'web_url' => '',
            'sort' => 5,
            'child_list' => []
        ]
    ]
];
