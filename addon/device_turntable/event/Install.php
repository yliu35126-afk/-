<?php
namespace addon\device_turntable\event;

class Install
{
    public function handle()
    {
        // 设备抽奖插件不创建新表，依赖 turntable 已有结构
        return success();
    }
}