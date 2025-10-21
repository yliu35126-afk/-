
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
