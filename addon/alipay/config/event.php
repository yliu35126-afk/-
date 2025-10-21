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
    'bind'      => [
        
    ],

    'listen'    => [
        //支付异步回调
        'PayNotify' => [
            'addon\alipay\event\PayNotify'
        ],
        //支付方式，后台查询
        'PayType' => [
            'addon\alipay\event\PayType'
        ],
        //支付，前台应用
        'Pay' => [
            'addon\alipay\event\Pay'
        ],
        'PayClose' => [
            'addon\alipay\event\PayClose'
        ],
        'PayRefund' => [
            'addon\alipay\event\PayRefund'
        ],
        'PayTransfer' => [
            'addon\alipay\event\PayTransfer'
        ],
        'TransferType' => [
            'addon\alipay\event\TransferType'
        ]
    ],

    'subscribe' => [
    ],
];
