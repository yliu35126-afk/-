<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */
namespace addon\turntable\event;

use think\facade\Db;

/**
 * 商品编辑后事件：保存转盘价档绑定
 */
class GoodsEdit
{
    /**
     * 处理事件
     * @param array $data 包含 goods_id, site_id
     * @return bool
     */
    public function handle($data)
    {
        $site_id = isset($data['site_id']) ? intval($data['site_id']) : 0;
        $goods_id = isset($data['goods_id']) ? intval($data['goods_id']) : 0;
        if ($site_id <= 0 || $goods_id <= 0) return true;

        // 读取前端提交（兼容单选与多选）
        $enable = intval(input('turntable_enable', 0));
        $tier_id = intval(input('turntable_tier_id', 0)); // 兼容老字段
        $tier_ids = input('turntable_tier_ids/a', []);    // 新的多选字段

        // 表名（含前缀）
        $prefix = config('database.connections.mysql.prefix');
        $table = $prefix . 'lottery_goods_tier';

        // 确保绑定表存在（幂等）
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL,
  `goods_id` int(11) NOT NULL,
  `enable` tinyint(1) NOT NULL DEFAULT 0,
  `tier_id` int(11) NOT NULL DEFAULT 0,
  `tier_ids` text NULL,
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_site_goods` (`site_id`,`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='转盘-商品价档绑定';";
            Db::execute($sql);
        } catch (\Throwable $e) {
            // 忽略创建失败，后续操作将被 try/catch 保护
        }

        // 未启用则删除绑定
        if ($enable === 0) {
            try {
                Db::name('lottery_goods_tier')->where([['site_id', '=', $site_id], ['goods_id', '=', $goods_id]])->delete();
            } catch (\Throwable $e) {}
            return true;
        }

        // 归一化选择列表
        $chosen_ids = [];
        if (is_array($tier_ids) && !empty($tier_ids)) {
            foreach ($tier_ids as $tid) {
                $tid = intval($tid);
                if ($tid > 0) $chosen_ids[$tid] = true;
            }
        }
        if (empty($chosen_ids) && $tier_id > 0) {
            $chosen_ids[$tier_id] = true;
        }
        if (empty($chosen_ids)) return true; // 未选择则忽略

        // 批量校验价档有效，并按供货价范围进行“全部命中”校验
        $valid_ids = [];
        try {
            $ids = array_keys($chosen_ids);
            $tiers = Db::name('lottery_price_tier')->where([
                ['site_id', '=', $site_id],
                ['status', '=', 1],
                ['tier_id', 'in', $ids]
            ])->column('min_price,max_price', 'tier_id');

            // 获取供货价（优先取提交，其次取库中）
            $cost_price = floatval(input('cost_price', 0));
            if ($cost_price <= 0) {
                try {
                    $cost_price = floatval(Db::name('goods')->where([
                        ['goods_id', '=', $goods_id],
                        ['site_id', '=', $site_id]
                    ])->value('cost_price'));
                } catch (\Throwable $e2) {}
            }

            foreach ($ids as $id) {
                if (!isset($tiers[$id])) continue;
                $min = floatval($tiers[$id]['min_price'] ?? 0);
                $max = floatval($tiers[$id]['max_price'] ?? 0);
                // 若未设置范围(min=max=0)，不做范围限制；否则校验包含关系
                if ($min == 0 && $max == 0) {
                    $valid_ids[] = $id;
                    continue;
                }
                if ($cost_price > 0) {
                    if ($cost_price >= $min && ($max == 0 || $cost_price <= $max)) {
                        $valid_ids[] = $id;
                    }
                }
            }
        } catch (\Throwable $e) {
            // 查询异常则不保存绑定
            return true;
        }
        // 全满足：需要“有效ID数量 == 选择数量”，否则不保存；
        // 说明：未设置范围的价档视作天然满足
        if (empty($valid_ids) || count($valid_ids) !== count($ids)) {
            return true;
        }

        // 写入/更新绑定
        try {
            $now = time();
            $exists = Db::name('lottery_goods_tier')->where([['site_id', '=', $site_id], ['goods_id', '=', $goods_id]])->find();
            $row = [
                'site_id' => $site_id,
                'goods_id' => $goods_id,
                'enable' => 1,
                // 兼容老字段：取第一个有效价档为代表
                'tier_id' => intval(reset($valid_ids)),
                'tier_ids' => json_encode(array_values($valid_ids), JSON_UNESCAPED_UNICODE),
                'update_time' => $now
            ];
            if ($exists) {
                Db::name('lottery_goods_tier')->where([['id', '=', $exists['id']]])->update($row);
            } else {
                $row['create_time'] = $now;
                Db::name('lottery_goods_tier')->insert($row);
            }
        } catch (\Throwable $e) {
            // 忽略错误，不影响商品保存
        }

        return true;
    }
}