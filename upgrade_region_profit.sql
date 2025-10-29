-- 区域分润与价档固定金额字段升级脚本（幂等、向后兼容）
-- 执行前请备份数据库，生产环境建议在维护窗口执行

-- 1) 设备信息表：增加省/市/区字段，用于区域分润 target_id 的来源占位
ALTER TABLE `device_info`
  ADD COLUMN `province_id` BIGINT NOT NULL DEFAULT 0 COMMENT '设备所在省ID' AFTER `promoter_id`,
  ADD COLUMN `city_id` BIGINT NOT NULL DEFAULT 0 COMMENT '设备所在市ID' AFTER `province_id`,
  ADD COLUMN `district_id` BIGINT NOT NULL DEFAULT 0 COMMENT '设备所在区ID' AFTER `city_id`;

-- 索引优化：区域检索（可选）
ALTER TABLE `device_info`
  ADD INDEX `idx_device_region` (`province_id`, `city_id`, `district_id`);

-- 2) 价档表：增加省/市/区固定金额字段，用于按模板直接入库固定金额
ALTER TABLE `lottery_price_tier`
  ADD COLUMN `province_profit` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '省级固定分润金额' AFTER `max_price`,
  ADD COLUMN `city_profit` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '市级固定分润金额' AFTER `province_profit`,
  ADD COLUMN `district_profit` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '区级固定分润金额' AFTER `city_profit`;

-- 兼容字段命名（如历史使用 *_fixed），建议统一为 *_profit；可视情况保留别名字段：
-- ALTER TABLE `lottery_price_tier`
--   ADD COLUMN `province_fixed` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '省级固定分润金额(别名)';
-- ALTER TABLE `lottery_price_tier`
--   ADD COLUMN `city_fixed` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '市级固定分润金额(别名)';
-- ALTER TABLE `lottery_price_tier`
--   ADD COLUMN `district_fixed` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '区级固定分润金额(别名)';

-- 索引优化：固定金额字段（可选），提升筛选特定模板的速度
ALTER TABLE `lottery_price_tier`
  ADD INDEX `idx_tier_fixed_profit` (`province_profit`, `city_profit`, `district_profit`);

-- 3) 分润表无需结构变更：沿用 `ns_lottery_profit(role, target_id, amount, status, ...)`
--    新增角色 'province' / 'city' / 'district'，`target_id` 暂存区域ID或代理ID（取决于映射方案）

-- 回滚示例（如因误加字段需删除，务必评估数据影响）：
-- ALTER TABLE `device_info` DROP COLUMN `province_id`, DROP COLUMN `city_id`, DROP COLUMN `district_id`;
-- ALTER TABLE `lottery_price_tier` DROP COLUMN `province_profit`, DROP COLUMN `city_profit`, DROP COLUMN `district_profit`;