<?php
namespace app\api\controller;

use think\facade\Db;

/**
 * 分润调试网关（仅联调用，非生产）
 */
class ProfitDebug extends BaseApi
{
    /**
     * 设置价档分润配置（百分比）：platform/supplier/promoter/installer/owner
     * 请求示例：tierId=1&platform=5&supplier=35&promoter=20&installer=10&owner=30
     */
    public function seedTierConfig()
    {
        $tierId    = intval($this->params['tierId'] ?? 0);
        $platform  = floatval($this->params['platform'] ?? 0);
        $supplier  = floatval($this->params['supplier'] ?? 0);
        $promoter  = floatval($this->params['promoter'] ?? 0);
        $installer = floatval($this->params['installer'] ?? 0);
        $owner     = floatval($this->params['owner'] ?? 0);
        if ($tierId <= 0) return $this->response($this->error('', '缺少 tierId'));

        $cfg = [
            'platform_percent' => $platform,
            'supplier_percent' => $supplier,
            'promoter_percent' => $promoter,
            'installer_percent'=> $installer,
            'owner_percent'    => $owner,
        ];

        Db::name('lottery_price_tier')->where('tier_id', $tierId)->update([
            'profit_json' => json_encode($cfg, JSON_UNESCAPED_UNICODE),
            'update_time' => time(),
        ]);

        return $this->response(success(0, 'ok', ['tierId' => $tierId, 'config' => $cfg]));
    }
}