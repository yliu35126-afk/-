<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class AddonInstall extends Command
{
    protected function configure()
    {
        $this->setName('addon:install')
            ->setDescription('Install an addon by name')
            ->addArgument('name')
            ->addOption('lite', null, null, 'Skip diy_view refresh and perform a lightweight install')
            ->addOption('refresh', null, null, 'Refresh menus and diy_view without touching addon table');
    }

    protected function execute(Input $input, Output $output)
    {
        $name = $input->getArgument('name') ?: 'turntable';
        $lite = $input->getOption('lite') !== null; // presence indicates lite mode
        $refreshOnly = $input->getOption('refresh') !== null;
        try {
            $output->writeln('[START] addon install: ' . $name . ($lite ? ' (lite)' : ''));
            $model = new \app\model\system\Addon();
            $output->writeln('[STEP] model created');
            if ($refreshOnly) {
                // Refresh menus and diy_view for an already-installed addon
                $output->writeln('[STEP] refreshing menus...');
                $ref = new \ReflectionClass($model);
                $m = $ref->getMethod('installMenu');
                $m->setAccessible(true);
                $installMenu = $m->invoke($model, $name);
                if (is_array($installMenu) && isset($installMenu['code']) && $installMenu['code'] != 0) {
                    $output->writeln("[FAIL] installMenu failed: " . ($installMenu['message'] ?? 'unknown'));
                    return 1;
                }
                $output->writeln('[STEP] refreshing diy_view...');
                $resR = $model->refreshDiyView($name);
                if (is_array($resR) && isset($resR['code']) && $resR['code'] != 0) {
                    $output->writeln("[FAIL] refreshDiyView failed: " . ($resR['message'] ?? 'unknown'));
                    return 1;
                }
                $output->writeln("[OK] refresh completed for '{$name}'.");
                return 0;
            }

            if ($lite) {
                // Run preInstall only, then install menus and add addon record without diy_view refresh
                $pre = (new \ReflectionClass($model))->getMethod('repairInstall')->invoke($model, $name);
                if (is_array($pre) && isset($pre['code']) && $pre['code'] != 0) {
                    $output->writeln("[FAIL] preInstall failed: " . ($pre['message'] ?? 'unknown'));
                    return 1;
                }
                // install menus (call private method via reflection)
                $ref = new \ReflectionClass($model);
                $method = $ref->getMethod('installMenu');
                $method->setAccessible(true);
                $installMenu = $method->invoke($model, $name);
                if (is_array($installMenu) && isset($installMenu['code']) && $installMenu['code'] != 0) {
                    $output->writeln("[FAIL] installMenu failed: " . ($installMenu['message'] ?? 'unknown'));
                    return 1;
                }
                // add addon record
                $addons_model = model('addon');
                $info = require 'addon/' . $name . '/config/info.php';
                $info['create_time'] = time();
                $info['icon'] = 'addon/' . $name . '/icon.png';
                $ok = $addons_model->add($info);
                if (!$ok) {
                    $output->writeln("[FAIL] write addon record failed");
                    return 1;
                }
                $output->writeln("[OK] lite install completed for '{$name}'.");
                return 0;
            }

            $res = $model->install($name);
            $msg = is_array($res) && isset($res['message']) ? $res['message'] : '';
            if (is_array($res) && isset($res['code']) && $res['code'] == 0) {
                $output->writeln("[OK] addon '{$name}' installed successfully.");
                return 0;
            } else {
                $output->writeln("[FAIL] install '{$name}' failed: " . ($msg ?: 'unknown error'));
                $output->writeln("[DETAIL] " . var_export($res, true));
                return 1;
            }
        } catch (\Throwable $e) {
            $output->writeln('[EXCEPTION] ' . $e->getMessage());
            $output->writeln('[TRACE] ' . $e->getFile() . ':' . $e->getLine());
            return 2;
        }
    }
}