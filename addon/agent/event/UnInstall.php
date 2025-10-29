<?php
namespace addon\agent\event;

/**
 * 插件卸载事件：清理资源（保留数据表以防误删）
 */
class UnInstall
{
    /**
     * 执行卸载
     */
    public function handle()
    {
        // 如需删除表，可在 data/uninstall.sql 中处理；此处仅返回成功
        return success();
    }
}