<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * Upgrade script for Agent module: add commission & validity fields, create auxiliary tables if missing.
 */
class AgentUpgrade extends Command
{
    protected function configure()
    {
        $this->setName('agent:upgrade')
            ->setDescription('Upgrade agent module: extend ns_agent table and create related tables');
    }

    protected function execute(Input $input, Output $output)
    {
        try {
            $this->extendAgentTable($output);
            $this->createLotteryPriceTier($output);
            $this->createDevicePriceBind($output);
            $output->writeln('<info>Agent module upgrade completed.</info>');
            return 0;
        } catch (\Throwable $e) {
            $output->writeln('<error>Upgrade failed: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }

    private function extendAgentTable(Output $output)
    {
        // Detect table name (Niushop prefix is typically ns_). Fallback to agent if not found.
        $table = $this->detectTable(['ns_agent', 'agent']);
        if (!$table) {
            $output->writeln('<comment>Agent table not found (ns_agent/agent). Skipping.</comment>');
            return;
        }

        $columns = Db::query("SHOW COLUMNS FROM `{$table}`");
        $existing = array_column($columns, 'Field');

        $alterSql = [];
        if (!in_array('commission_rate', $existing)) {
            $alterSql[] = "ADD COLUMN `commission_rate` DECIMAL(10,2) NULL DEFAULT NULL COMMENT '佣金比例'";
        }
        if (!in_array('validity_start_date', $existing)) {
            $alterSql[] = "ADD COLUMN `validity_start_date` DATE NULL DEFAULT NULL COMMENT '有效期开始'";
        }
        if (!in_array('validity_end_date', $existing)) {
            $alterSql[] = "ADD COLUMN `validity_end_date` DATE NULL DEFAULT NULL COMMENT '有效期结束'";
        }
        if (!in_array('agent_phone', $existing)) {
            $alterSql[] = "ADD COLUMN `agent_phone` VARCHAR(20) NULL DEFAULT NULL COMMENT '代理电话'";
        }
        if (!in_array('agent_contact_name', $existing)) {
            $alterSql[] = "ADD COLUMN `agent_contact_name` VARCHAR(100) NULL DEFAULT NULL COMMENT '联系人姓名'";
        }

        if ($alterSql) {
            $sql = "ALTER TABLE `{$table}` " . implode(', ', $alterSql);
            Db::execute($sql);
            $output->writeln('<info>Extended table ' . $table . ' with commission/validity/contact fields.</info>');
        } else {
            $output->writeln('<comment>Agent table already up-to-date.</comment>');
        }
    }

    private function createLotteryPriceTier(Output $output)
    {
        // Optional table for price tier commissions
        $table = $this->detectTable(['lottery_price_tier']);
        if ($table) {
            $output->writeln('<comment>Table lottery_price_tier already exists. Skipping creation.</comment>');
            return;
        }

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `lottery_price_tier` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tier_name` VARCHAR(100) NOT NULL,
    `commission_rate` DECIMAL(10,2) NULL DEFAULT NULL,
    `province_commission` DECIMAL(10,2) NULL DEFAULT NULL,
    `city_commission` DECIMAL(10,2) NULL DEFAULT NULL,
    `district_commission` DECIMAL(10,2) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='价档及分润设置';
SQL;
        Db::execute($sql);
        $output->writeln('<info>Created table lottery_price_tier.</info>');
    }

    private function createDevicePriceBind(Output $output)
    {
        $table = $this->detectTable(['device_price_bind']);
        if ($table) {
            $output->writeln('<comment>Table device_price_bind already exists. Skipping creation.</comment>');
            return;
        }

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `device_price_bind` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `device_id` INT NULL DEFAULT NULL,
    `agent_code` VARCHAR(50) NULL DEFAULT NULL,
    `price_tier_id` INT NULL DEFAULT NULL,
    `start_time` TIMESTAMP NULL DEFAULT NULL,
    `end_time` TIMESTAMP NULL DEFAULT NULL,
    `status` TINYINT(1) DEFAULT 1,
    INDEX(`agent_code`),
    INDEX(`price_tier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备与价档绑定（含代理）';
SQL;
        Db::execute($sql);
        $output->writeln('<info>Created table device_price_bind.</info>');
    }

    private function detectTable(array $candidates)
    {
        foreach ($candidates as $name) {
            try {
                Db::query("SHOW TABLES LIKE '{$name}'");
                $exists = Db::query("SELECT 1 FROM information_schema.tables WHERE table_name='{$name}'");
                if ($exists) return $name;
            } catch (\Throwable $e) {
                // ignore
            }
        }
        return null;
    }
}