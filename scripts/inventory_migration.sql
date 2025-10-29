-- 添加当前库存字段并初始化
ALTER TABLE `device_probability_grid`
  ADD COLUMN `inventory_current` INT(11) NOT NULL DEFAULT 0 COMMENT '当前轮库存' AFTER `inventory_override`;

-- 以现有覆盖库存初始化当前库存
UPDATE `device_probability_grid` SET `inventory_current` = `inventory_override` WHERE `inventory_current` = 0;

-- 辅助索引（如需，已存在 uniq_dev_grid 唯一约束）
CREATE INDEX `idx_device_grid_invcur` ON `device_probability_grid` (`device_id`, `inventory_current`);