<?php
namespace addon\device_turntable\event;

/**
 * 设备版抽奖 - 推广卡片（商家端显示）
 */
class ShowPromotion
{
    public function handle()
    {
        return [
            'admin' => [
                [
                    'name' => 'device_turntable',
                    'show_type' => 'admin',
                    'title' => '设备抽奖（设备线）',
                    'description' => '线下设备抽奖（扫码参与）',
                    'icon' => 'addon/turntable/icon.png',
                    'url' => 'device_turntable://admin/bind/lists'
                ]
            ],
            'shop' => [
                [
                    'name' => 'device_turntable',
                    'show_type' => 'shop',
                    'title' => '设备抽奖（设备线）',
                    'description' => '线下设备抽奖（扫码参与）',
                    'icon' => 'addon/turntable/icon.png',
                    // 商家中心点击后进入设备-价档绑定（商家端）
                    'url' => 'device_turntable://shop/bind/lists',
                    // 店铺促销区展示
                ]
            ]
        ];
    }
}