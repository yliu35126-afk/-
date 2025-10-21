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
    'bind' => [

    ],

    'listen' => [

        /**
         * 系统基础事件
         * 完成系统基础化操作执行
         */
        //应用初始化事件
        'AppInit' => [
            'app\event\InitConfig',
            'app\event\InitRoute',
            'app\event\InitAddon',
            'app\event\InitCron',

        ],
        'HttpRun' => [],
        'HttpEnd' => [],
        'LogLevel' => [],
        'LogWrite' => [],

        /**
         * 营销活动查询事件
         * 用于添加到对应营销活动展示
         */
        //营销活动
        'ShowPromotion' => [
            'app\event\ShowPromotion'
        ],

        /**
         * 店铺相关事件
         * 完成店铺相关功能操作
         */
        //添加店铺账户数据
        'AddShopAccount' => [],
        //添加店铺事件
        'AddShop' => [
            'app\event\AddShopDiyView',//增加默认自定义数据：网站主页、底部导航
        ],
        'ShopClose' => [
            'app\event\ShopClose'
        ],
        'CronShopClose' => [
            'app\event\CronShopClose'
        ],

        //添加门店事件
        'AddStore' => [
            'app\event\AddStoreDiyView',//增加默认自定义数据：门店主页
        ],

        /**
         * 会员相关事件
         *完成会员相关功能操作调用
         */
        //添加会员账户数据
        'AddMemberAccount' => [
            'app\event\UpdateMemberLevel',//会员账户变化检测会员等级
        ],
        //会员行为事件
        'MemberAction' => [],
        //会员营销活动标志
        'MemberPromotion' => [],
        //会员注册后执行事件
        'MemberRegister' => [
            'app\event\MemberRegister'
        ],
        'MemberLogin' => [
            'app\event\MemberLogin'
        ],


        /**
         * 支付功能事件
         * 对应支付相关功能调用
         */
        //支付异步回调(支付插件完成，作用判定支付成功，返回对应支付编号)
        'PayNotify' => [

        ],

        /**
         * 订单功能事件
         * 完成订单相关操作调用
         */
        //订单支付异步执行
        'OrderPayNotify' => [
            'app\event\OrderPayNotify',//商城订单支付异步回调
        ],
        //订单创建后执行事件
        'OrderCreate' => [
            'app\event\OrderCreateShopMember',  //创建订单后添加店铺关注
            'app\event\CreateOrderLog',  //创建订单后添加订单日志
        ],
        'OrderPay' => [
            'app\event\ShopOrderCalc',  //订单支付后店铺计算订单佣金相关

        ],  //订单支付成功后执行事件
        'OrderDelivery' => [], //订单发货
        'orderTakeDelivery' => [], //订单收货
        'OrderComplete' => [
        ],  //订单完成后执行事件
        'OrderClose' => [], //订单关闭后执行事件
        'OrderRefundFinish' => [
            'app\event\ShopOrderRefundCalc',
            'app\event\OrderRefundFinish'
        ],//订单项完成退款操作之后
        //核销类型
        'VerifyType' => [
        ],
        //核销
        'Verify' => [
            'app\event\PickupOrderVerify',//自提订单核销
            'app\event\VirtualGoodsVerify',//虚拟商品核销
        ],
        //执行店铺续签申请后店铺入驻时间续期
        'CronShopRelpay' => [
            'app\event\CronShopRelpay'
        ],
        'CronOrderClose' => [
            'app\event\CronOrderClose'
        ],
        'CronOrderTakeDelivery' => [
            'app\event\CronOrderTakeDelivery'
        ],
        //自动执行订单自动完成
        'CronOrderComplete' => [
            'app\event\CronOrderComplete'
        ],

        /**
         * 自定义模板事件
         * 自定义模板展示调用相关功能
         */
        //自定义模板
        'DiyViewUtils' => [
            'app\event\DiyViewUtils',//自定义组件
        ],
        'DiyViewEdit' => [
            'app\event\DiyViewEdit',//自定义页面编辑
        ],
        'DiyViewCreate' => [
            'app\event\DiyViewCreate',//自定义页面创建
        ],
        'DiyLink' => [
            'app\event\DiyLink',//自定义链接
        ],
        'DiyChildLink' => [
            'app\event\DiyChildLink',//自定义子级链接
        ],

        //关闭游戏
        'CloseGame' => [
            'app\event\CloseGame'
        ],
        //开启游戏
        'OpenGame' => [
            'app\event\OpenGame'
        ],

        /**
         * 物流公司
         */
        //物流跟踪
        'Trace' => [
            'app\event\Kd100Trace',//快递100 物流查询
            'app\event\KdbirdTrace'//快递鸟物流查询
        ],
        'CloseTrace' => [
            'app\event\CloseKd100Trace',//快递100 物流查询关闭
            'app\event\CloseKdbirdTrace'//快递鸟物流查询关闭
        ],

        /**
         * 消息发送
         */
        //消息模板
        'SendMessageTemplate' => [
            // 订单创建
            'app\event\MessageOrderCreate',
            // 订单关闭
            'app\event\MessageOrderClose',
            // 订单完成
            'app\event\MessageOrderComplete',
            // 订单支付
            'app\event\MessageOrderPaySuccess',
            // 订单发货
            'app\event\MessageOrderDelivery',
            // 订单收货
            'app\event\MessageOrderReceive',
            // 商家同意退款
            'app\event\MessageShopRefundAgree',
            // 商家拒绝退款
            'app\event\MessageShopRefundRefuse',
            // 核销通知
            'app\event\MessageShopVerified',
            // 注册验证
            'app\event\MessageRegisterCode',
            // 注册成功
            'app\event\MessageRegisterSuccess',
            // 找回密码
            'app\event\MessageFindCode',
            // 会员登陆成功
            'app\event\MessageLogin',
            // 帐户绑定验证码
            'app\event\MessageBindCode',
            // 动态码登陆验证码
            'app\event\MessageLoginCode',
            // 设置密码
            'app\event\MessageSetPassWord',
            // 买家发起退款提醒
            'app\event\MessageOrderRefundApply',
            // 买家已退货提醒
            'app\event\MessageOrderRefundDelivery',
            // 买家支付通知商家
            'app\event\MessageBuyerPaySuccess',
            // 买家收货通知商家
            'app\event\MessageBuyerReceive',
            // 会员申请提现通知
            'app\event\MessageUserWithdrawalApply',
            // 会员提现成功通知
            'app\event\MessageUserWithdrawalSuccess',
            // 分销申请提现通知
            'app\event\MessageFenxiaoWithdrawalApply',
            // 分销提现成功通知
            'app\event\MessageFenxiaoWithdrawalSuccess',
            // 分销提现失败通知
            'app\event\MessageFenxiaoWithdrawalError',
            // 会员注销成功通知
            'app\event\MessageCancelSuccess',
            // 会员注销失败通知
            'app\event\MessageCancelFail',
            // 会员注销申请通知
            'app\event\MessageCancelApply',
        ],
        //发送短信
        'sendSms' => [

        ],

        'Qrcode' => [
            'app\event\Qrcode'
        ],

        // api配置变更
        'ApiConfigChange' => [
            'app\event\ApiConfigChange'
        ],

        //店铺周期结算
        'ShopWithdrawPeriodCalc' => [
            'app\event\ShopWithdrawPeriodCalc',
        ],

        //PC默认导航
        'InitPcNav' => [
            'app\event\InitPcNav',
        ],

        //商品上架
        'CronGoodsTimerOn' => [
            'app\event\CronGoodsTimerOn'
        ],

        //商品下架
        'CronGoodsTimerOff' => [
            'app\event\CronGoodsTimerOff'
        ],

        //维权自动事件
        'OrderRefundApply' => [
            'app\event\OrderRefundApply'
        ],

        //添加刷新事件
        'AddMemberClusterCronRefresh' => [
            'app\event\AddMemberClusterCronRefresh'
        ],
        //会员群体定时刷新
        'CronMemberClusterRefresh' => [
            'app\event\CronMemberClusterRefresh'
        ],


        // 添加店铺演示数据
        'AddYanshiData' => [
            'app\event\AddYanshiData',//增加默认商品相关数据：商品1~3个、商品分类、商品服务
        ],

    ],

    'subscribe' => [
    ],
];
