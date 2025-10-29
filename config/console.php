<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'addon:install' => app\command\AddonInstall::class,
        'seed:turntable_slots' => app\command\SeedTurntableSlots::class,
        'seed:agents' => app\command\SeedAgents::class,
        'check:agent' => app\command\CheckAgent::class,
        'agent:upgrade' => app\command\AgentUpgrade::class,
    ],
];
