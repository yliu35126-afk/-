<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'addon:install' => app\command\AddonInstall::class,
        'seed:turntable_slots' => app\command\SeedTurntableSlots::class,
    ],
];
