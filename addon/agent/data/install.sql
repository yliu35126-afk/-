-- 创建代理管理表
CREATE TABLE IF NOT EXISTS `ns_agent` (
  `agent_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `site_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '站点ID',
  `title` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '代理标题',
  `level` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '级别：1省/2市/3区县',
  `province_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '省ID',
  `city_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '市ID',
  `district_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '区县ID',
  `member_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '绑定会员ID',
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态：1启用/0停用',
  `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`agent_id`),
  KEY `idx_site_level_region` (`site_id`, `level`, `province_id`, `city_id`, `district_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='代理管理';