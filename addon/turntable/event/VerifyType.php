<?php
namespace addon\turntable\event;

/**
 * 扩展核销类型：转盘实物奖品
 */
class VerifyType
{
    public function handle($params = [])
    {
        // 返回合并的类型定义
        return [
            'turntable_goods' => [
                'name' => '转盘实物奖品',
            ],
        ];
    }
}