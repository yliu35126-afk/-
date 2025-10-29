<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class CheckAgent extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('check:agent')
            ->setDescription('Check whether addon "agent" is installed and routes usable');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            // 检查插件记录
            $info = model('addon')->getInfo([['name','=', 'agent']]);
            if ($info) {
                $output->writeln('[OK] addon "agent" is installed.');
            } else {
                $output->writeln('[FAIL] addon "agent" not found in addon table.');
                return 1;
            }

            // 简单检查菜单是否已刷新（存在后台菜单条目）
            $menuCount = model('menu')->getCount([
                ['app_module','=', 'admin'],
                ['addon','=', 'agent']
            ]);
            $output->writeln('[INFO] admin menu items for agent: ' . $menuCount);

            // 输出建议访问的 URL
            $output->writeln('[URL] 访问后台列表: index.php?s=addons/agent/admin/agent/lists.html');
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('[ERROR] ' . $e->getMessage());
            return 1;
        }
    }
}
