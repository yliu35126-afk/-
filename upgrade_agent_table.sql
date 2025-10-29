-- 代理主体表（区域→代理）
-- 方案A：按省/市/区三级精确绑定，同一区域唯一代理，找不到归平台

CREATE TABLE IF NOT EXISTS `ns_agent` (
  `agent_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '代理主键',
  `site_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '站点/商户归属（可选）',
  `title` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '代理名称',
  `level` TINYINT NOT NULL DEFAULT 0 COMMENT '层级：1省 2市 3区',
  `province_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '省ID',
  `city_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '市ID',
  `district_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '区ID',
  `member_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '关联会员/账户（可选）',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `create_time` INT(11) NOT NULL DEFAULT 0,
  `update_time` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`agent_id`),
  UNIQUE KEY `uniq_region_level` (`level`, `province_id`, `city_id`, `district_id`),
  KEY `idx_region_all` (`province_id`, `city_id`, `district_id`, `status`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='区域代理主体';

-- 示例：插入一个省级代理（请按需修改 ID 与名称）
-- INSERT INTO `ns_agent` (`title`,`level`,`province_id`,`status`,`create_time`) VALUES ('示例省代理',1,110000,1,UNIX_TIMESTAMP());