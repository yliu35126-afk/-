<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\facade\Db;

class SeedAgents extends Command
{
    protected function configure()
    {
        $this->setName('seed:agents')
            ->setDescription('Bulk create ns_agent records by area (province/city/district)')
            ->addOption('levels', null, Option::VALUE_OPTIONAL, 'Comma-separated levels: province,city,district', 'province,city,district')
            ->addOption('site', null, Option::VALUE_OPTIONAL, 'Site ID', 0)
            ->addOption('status', null, Option::VALUE_OPTIONAL, 'Status: active|disabled', 'active')
            ->addOption('title_suffix', null, Option::VALUE_OPTIONAL, 'Suffix appended to title', '代理');
    }

    protected function execute(Input $input, Output $output)
    {
        $levelsOpt = strtolower(trim($input->getOption('levels')));
        $levels = array_filter(array_map('trim', explode(',', $levelsOpt)));
        $siteId = (int)$input->getOption('site');
        $statusOpt = strtolower(trim($input->getOption('status')));
        $status = $statusOpt === 'disabled' ? 0 : 1;
        $titleSuffix = (string)$input->getOption('title_suffix');

        // Validate levels
        $valid = ['province', 'city', 'district'];
        $levels = array_values(array_intersect($levels, $valid));
        if (empty($levels)) {
            $output->writeln('<error>No valid levels specified. Use province,city,district.</error>');
            // 兼容不同框架/版本的控制台返回码常量，使用数值 1 表示失败
            return 1;
        }

        $created = 0; $skipped = 0; $errors = 0;

        foreach ($levels as $level) {
            switch ($level) {
                case 'province':
                    $created += $this->seedByLevel(1, $siteId, $status, $titleSuffix, $skipped, $errors, $output);
                    break;
                case 'city':
                    $created += $this->seedByLevel(2, $siteId, $status, $titleSuffix, $skipped, $errors, $output);
                    break;
                case 'district':
                    $created += $this->seedByLevel(3, $siteId, $status, $titleSuffix, $skipped, $errors, $output);
                    break;
            }
        }

        $output->writeln(sprintf('<info>Done.</info> Created: %d, Skipped(existing): %d, Errors: %d', $created, $skipped, $errors));
        // 使用 0 表示成功，避免未定义常量引发的静态检查报错
        return 0;
    }

    private function seedByLevel(int $areaLevel, int $siteId, int $status, string $titleSuffix, int &$skipped, int &$errors, Output $output): int
    {
        // area fields: id, pid, name, shortname, level, status, sort
        $areas = Db::name('area')->where([['level', '=', $areaLevel], ['status', '=', 1]])->field('id,pid,name,shortname,level')->order('sort asc')->select()->toArray();
        $created = 0;
        foreach ($areas as $row) {
            $provinceId = 0; $cityId = 0; $districtId = 0; $agentLevel = '';
            if ($areaLevel === 1) {
                $provinceId = (int)$row['id'];
                $agentLevel = 'province';
            } elseif ($areaLevel === 2) {
                $cityId = (int)$row['id'];
                $provinceId = (int)$row['pid'];
                $agentLevel = 'city';
            } else {
                $districtId = (int)$row['id'];
                $cityId = (int)$row['pid'];
                // fetch province pid of city
                $city = Db::name('area')->where([['id', '=', $cityId]])->field('pid')->find();
                $provinceId = $city ? (int)$city['pid'] : 0;
                $agentLevel = 'district';
            }

            // unique condition
            $exists = Db::name('ns_agent')->where([
                ['site_id', '=', $siteId],
                ['level', '=', $agentLevel],
                ['province_id', '=', $provinceId],
                ['city_id', '=', $cityId],
                ['district_id', '=', $districtId],
            ])->value('agent_id');

            if ($exists) { $skipped++; continue; }

            $titleBase = $row['shortname'] ?: $row['name'];
            $title = $titleBase . $titleSuffix;
            try {
                Db::name('ns_agent')->insert([
                    'site_id' => $siteId,
                    'title' => $title,
                    'level' => $agentLevel,
                    'province_id' => $provinceId,
                    'city_id' => $cityId,
                    'district_id' => $districtId,
                    'member_id' => 0,
                    'status' => $status,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors++;
                $output->writeln('<error>Failed: ' . $agentLevel . ' ' . $title . ' — ' . $e->getMessage() . '</error>');
            }
        }
        $output->writeln(sprintf('<comment>Level %d [%s] processed. Created: %d, Skipped: %d</comment>', $areaLevel, $areaLevel === 1 ? 'province' : ($areaLevel === 2 ? 'city' : 'district'), $created, $skipped));
        return $created;
    }
}