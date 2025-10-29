SET NAMES 'utf8';

-- 抽奖盘
CREATE TABLE IF NOT EXISTS `{{prefix}}lottery_board` (
  `board_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '盘ID',
  `site_id` int(11) NOT NULL DEFAULT 0 COMMENT '站点',
  `name` varchar(64) NOT NULL DEFAULT '' COMMENT '盘名称',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态',
  `round_control` text COMMENT '轮次控制JSON',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`board_id`),
  KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 抽奖格子（16格）
CREATE TABLE IF NOT EXISTS `{{prefix}}lottery_slot` (
  `slot_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `board_id` bigint(20) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0 COMMENT '格子序号0-15',
  `prize_type` varchar(16) NOT NULL DEFAULT 'thanks' COMMENT 'goods,coupon,point,balance,thanks',
  `title` varchar(64) NOT NULL DEFAULT '',
  `img` varchar(255) NOT NULL DEFAULT '',
  `goods_id` int(11) DEFAULT 0,
  `sku_id` int(11) DEFAULT 0,
  `inventory` int(11) NOT NULL DEFAULT 0,
  `weight` int(11) NOT NULL DEFAULT 0 COMMENT '抽中权重',
  `value` decimal(10,2) NOT NULL DEFAULT 0 COMMENT '奖品面值（优惠券、积分、余额）',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`slot_id`),
  KEY `idx_board` (`board_id`),
  UNIQUE KEY `uniq_board_pos` (`board_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 价档定义
CREATE TABLE IF NOT EXISTS `{{prefix}}lottery_price_tier` (
  `tier_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(64) NOT NULL DEFAULT '',
  `price` decimal(10,2) NOT NULL DEFAULT 0,
  `min_price` decimal(10,2) NOT NULL DEFAULT 0,
  `max_price` decimal(10,2) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `profit_json` text COMMENT '分润比例JSON',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`tier_id`),
  KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 设备信息与盘绑定
CREATE TABLE IF NOT EXISTS `{{prefix}}device_info` (
  `device_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `device_sn` varchar(64) NOT NULL,
  `site_id` int(11) NOT NULL DEFAULT 0,
  `owner_id` int(11) DEFAULT 0,
  `installer_id` int(11) DEFAULT 0,
  `supplier_id` int(11) DEFAULT 0,
  `promoter_id` int(11) DEFAULT 0,
  `agent_code` varchar(32) DEFAULT '',
  `board_id` bigint(20) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(11) NOT NULL DEFAULT 0,
  `update_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`device_id`),
  UNIQUE KEY `uniq_sn` (`device_sn`),
  KEY `idx_board` (`board_id`),
  KEY `idx_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 设备价档绑定
CREATE TABLE IF NOT EXISTS `{{prefix}}device_price_bind` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `device_id` bigint(20) NOT NULL,
  `tier_id` bigint(20) NOT NULL,
  `start_time` int(11) NOT NULL DEFAULT 0,
  `end_time` int(11) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bind` (`device_id`, `tier_id`, `start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 抽奖记录
CREATE TABLE IF NOT EXISTS `{{prefix}}lottery_record` (
  `record_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `device_id` bigint(20) DEFAULT NULL,
  `board_id` bigint(20) NOT NULL,
  `slot_id` bigint(20) NOT NULL,
  `prize_type` varchar(16) NOT NULL,
  `goods_id` int(11) DEFAULT 0,
  `tier_id` bigint(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0,
  `round_no` int(11) NOT NULL DEFAULT 0,
  `result` varchar(8) NOT NULL DEFAULT 'miss',
  `order_id` int(11) DEFAULT 0,
  `ext` text,
  `create_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`record_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_device_time` (`device_id`, `create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 分润记录
CREATE TABLE IF NOT EXISTS `{{prefix}}lottery_profit` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `record_id` bigint(20) NOT NULL,
  `device_id` bigint(20) NOT NULL,
  `tier_id` bigint(20) NOT NULL,
  `site_id` int(11) NOT NULL DEFAULT 0,
  `amount_total` decimal(10,2) NOT NULL DEFAULT 0,
  `platform_money` decimal(10,2) NOT NULL DEFAULT 0,
  `supplier_money` decimal(10,2) NOT NULL DEFAULT 0,
  `promoter_money` decimal(10,2) NOT NULL DEFAULT 0,
  `installer_money` decimal(10,2) NOT NULL DEFAULT 0,
  `owner_money` decimal(10,2) NOT NULL DEFAULT 0,
  `create_time` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 商品与价档绑定（每站点每商品一条）
CREATE TABLE IF NOT EXISTS `{{prefix}}lottery_goods_tier` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;