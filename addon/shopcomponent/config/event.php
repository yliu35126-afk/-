<?php
// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        //展示活动
        'ShowPromotion'   => [
            'addon\shopcomponent\event\ShowPromotion',
        ],
        'WeappMenu'       => [
            'addon\shopcomponent\event\WeappMenu',
        ],
        'GoodsEdit' => [
            'addon\shopcomponent\event\GoodsEdit'
        ],
        // 商品删除
        'DeleteGoods' => [
            'addon\shopcomponent\event\DeleteGoods'
        ],
        // 订单支付成功
        'OrderPay' => [
            'addon\shopcomponent\event\OrderPay'
        ],
        // 发货成功
        'OrderDelivery' => [
            'addon\shopcomponent\event\OrderDelivery'
        ],
        // 订单收货
        'OrderTakeDelivery' => [
            'addon\shopcomponent\event\OrderTakeDelivery'
        ],
        // 发起维权申请
        'orderRefundApply' => [
            'addon\shopcomponent\event\OrderRefundApply'
        ],
        // 维权状态变更
        'RefundStatusChange' => [
            'addon\shopcomponent\event\RefundStatusChange'
        ],
        // 同步微信类目
        'SyncWxCategory' => [
            'addon\shopcomponent\event\SyncWxCategory'
        ],
        // 异步回调
        'shopcomponentNotify' => [
            'addon\shopcomponent\event\ShopcomponentNotify'
        ]
    ],

    'subscribe' => [
    ],
];
