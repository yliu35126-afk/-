<?php
namespace addon\agent\event;

/**
 * 插件安装事件：创建所需数据表
 */
class Install
{
    /**
     * 执行安装
     */
    public function handle()
    {
        // 创建 ns_agent 表（若不存在）
        execute_sql('addon/agent/data/install.sql');
        return success();
    }
}