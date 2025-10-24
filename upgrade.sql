
ALTER TABLE `goods_grab` MODIFY COLUMN `category_id` int(11) NOT NULL DEFAULT 0 COMMENT '商品分类' AFTER `is_virtual`;

ALTER TABLE `message` MODIFY COLUMN `support_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '支持场景 如小程序  wep端' AFTER `sitemessage_json`;

ALTER TABLE `order_goods` ADD COLUMN `out_aftersale_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '关联视频号订单' AFTER `is_fenxiao`;

ALTER TABLE `order_goods` MODIFY COLUMN `is_refund_stock` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否返还库存' AFTER `refund_address`;

ALTER TABLE `order_refund_log` MODIFY COLUMN `action_way` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '2' COMMENT '操作类型1买家2卖家' AFTER `action`;

ALTER TABLE `promotion_presale_order` MODIFY COLUMN `sku_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '商品名称' AFTER `sku_id`;

ALTER TABLE `promotion_presale_order` MODIFY COLUMN `relate_order_id` int(11) NOT NULL COMMENT '关联订单id' AFTER `deposit_agreement`;

ALTER TABLE `servicer` MODIFY COLUMN `nickname` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '客服昵称' AFTER `user_id`;

ALTER TABLE `servicer` MODIFY COLUMN `avatar` varchar(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '客服头像' AFTER `nickname`;

ALTER TABLE `servicer_dialogue` MODIFY COLUMN `message` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT '消息内容 纯文本消息' AFTER `content`;

ALTER TABLE `site_member_message` MODIFY COLUMN `delete_time` int(11) NOT NULL COMMENT '删除时间' AFTER `is_delete`;

ALTER TABLE `site_member_sub_message` MODIFY COLUMN `status` int(11) NOT NULL COMMENT '状态  0未送达  1已送达  2已接收' AFTER `text`;

ALTER TABLE `supplier` MODIFY COLUMN `longitude` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `website_id`;

ALTER TABLE `supplier` MODIFY COLUMN `latitude` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' AFTER `longitude`;

-- 概率控制：为设备表增加概率模式与配置
ALTER TABLE `device_info`
  ADD COLUMN `prob_mode` ENUM('round','live') NOT NULL DEFAULT 'round' COMMENT '概率模式：round=轮次，live=实时' AFTER `status`,
  ADD COLUMN `prob_json` TEXT NULL COMMENT '概率配置JSON（每格weight/inventory等）' AFTER `prob_mode`,
  ADD COLUMN `auto_reset` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '轮次结束是否自动重置' AFTER `prob_json`;

-- 概率控制：设备-格子映射（按设备覆盖默认盘槽位的权重/兜底）
CREATE TABLE IF NOT EXISTS `device_probability_grid` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `device_id` BIGINT(20) NOT NULL,
  `board_id` BIGINT(20) NOT NULL,
  `grid_no` TINYINT(2) NOT NULL COMMENT '1-16 格号',
  `slot_id` BIGINT(20) NOT NULL COMMENT '关联 lottery_slot.slot_id',
  `weight` INT(11) NOT NULL DEFAULT 0 COMMENT '展示/计算权重',
  `allow_fallback` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否允许兜底奖',
  `inventory_override` INT(11) NOT NULL DEFAULT 0 COMMENT '覆盖该格轮次库存（0表示不覆盖）',
  `create_time` INT(11) NOT NULL DEFAULT 0,
  `update_time` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_dev_grid` (`device_id`,`grid_no`),
  KEY `idx_device` (`device_id`),
  KEY `idx_board_grid` (`board_id`,`grid_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='设备概率格配置';

-- 增加设备签名秘钥，用于设备数据回传签名校验
ALTER TABLE `device_info`
  ADD COLUMN `device_secret` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '设备签名秘钥' AFTER `auto_reset`;

-- 为抽奖记录增加轮次号与库存快照，便于审计与复盘
ALTER TABLE `lottery_record`
  ADD COLUMN `round_no` INT(11) NOT NULL DEFAULT 0 COMMENT '轮次号，随轮次重置累加' AFTER `amount`,
  ADD COLUMN `inv_snapshot` TEXT NULL COMMENT '抽奖时库存快照JSON' AFTER `round_no`;

-- 奖品主表：抽奖奖品管理
CREATE TABLE IF NOT EXISTS `lottery_prize` (
  `prize_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('virtual','cash','goods') NOT NULL DEFAULT 'goods' COMMENT '奖品类型',
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '活动价/面值',
  `stock` INT(11) NOT NULL DEFAULT 0 COMMENT '库存数量',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '状态：1启用 0禁用',
  `create_time` INT(11) NOT NULL DEFAULT 0,
  `update_time` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`prize_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='抽奖奖品主表';

-- 分润明细：按角色细化分润
CREATE TABLE IF NOT EXISTS `ns_lottery_profit` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT(20) NOT NULL DEFAULT 0 COMMENT '关联订单ID',
  `record_id` BIGINT(20) NOT NULL DEFAULT 0 COMMENT '关联抽奖记录ID',
  `role` VARCHAR(32) NOT NULL COMMENT '角色，如province_agent/merchant',
  `target_id` BIGINT(20) NOT NULL DEFAULT 0 COMMENT '对应会员/主体ID',
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '分润金额',
  `status` ENUM('settled','revoked','pending') NOT NULL DEFAULT 'pending' COMMENT '分润状态',
  `settle_time` INT(11) NOT NULL DEFAULT 0 COMMENT '结算时间',
  `create_time` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_record` (`record_id`),
  KEY `idx_role_target` (`role`,`target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='抽奖分润明细';
