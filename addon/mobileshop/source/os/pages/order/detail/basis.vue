<template>
	<view class="order-detail-wrap">
		<!-- 订单状态 -->
		<view class="status-wrap color-base-bg" :style="{ backgroundImage: 'url(' + $util.img('upload/uniapp/order/status-wrap-bg.png') + ')' }">
			<view class="container">
				<image :src="$util.img(orderDetail.order_status_action.icon)"></image>
				<view class="status-name">
					<view>{{ orderDetail.order_status_name }}</view>
					<view class="time" v-if="orderDetail.order_status == 3">
						<text>还剩</text>
						<uni-count-down
							:day="orderDetail.take_delivery_execute_time.d"
							:hour="orderDetail.take_delivery_execute_time.h"
							:minute="orderDetail.take_delivery_execute_time.i"
							:second="orderDetail.take_delivery_execute_time.s"
							color="#fff"
							splitorColor="#fff"
							:showColon="false"
							borderColor="transparent"
							backgroundColor="transparent"
						/>
						<text>自动收货</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 地址信息 -->
		<view class="address-wrap">
			<view class="icon"><view class="iconfont iconlocation"></view></view>
			<view class="address-info">
				<view class="info">{{ orderDetail.name }} {{ orderDetail.mobile }}</view>
				<view class="detail">收货地址：{{ orderDetail.full_address }} {{ orderDetail.address }}</view>
			</view>
		</view>

		<!-- 店铺 -->
		<view class="block-wrap">
			<view class="goods-item" v-for="(goods, goodsIndex) in orderDetail.order_goods" :key="goodsIndex">
				<view class="goods-img"><image :src="$util.img(goods.sku_image, { size: 'mid' })" mode="widthFix" @error="imgError(goodsIndex)"></image></view>
				<view class="info-wrap">
					<view class="name-wrap">{{ goods.goods_name }}</view>
					<view class="spec-wrap" v-if="goods.sku_spec_format">
						<block v-for="(x, i) in goods.sku_spec_format" :key="i">{{ x.spec_value_name }} {{ i < goods.sku_spec_format.length - 1 ? '; ' : '' }}</block>
					</view>
					<view class="more-wrap">
						<view class="goods-class">{{ goods.goods_class_name }}</view>
						<text class="present-label color-base-bg" v-if="goods.is_present == 1">赠品</text>
						<view class="price-wrap">
							<view class="price">
								<text class="unit">￥</text>
								<text>{{ goods.price }}</text>
							</view>
							<view class="num">x{{ goods.num }}</view>
						</view>
					</view>
					<view class="delivery-status-name color-base-text">{{ goods.delivery_status_name }}</view>
				</view>
				<view class="action-wrap" v-if="goods.refund_status != 0" @click.stop="goRefund(goods.order_goods_id)">
					<button type="primary" size="mini">{{ goods.refund_status_name }}</button>
				</view>
			</view>
		</view>

		<!-- 订单信息 -->
		<view class="block-wrap">
			<view class="title">订单信息</view>
			<view class="order-cell">
				<text class="tit">订单编号：</text>
				<view class="box">{{ orderDetail.order_no }}</view>
				<view class="copy color-base-text" @click="$util.copy(orderDetail.order_no)">复制</view>
			</view>
			<view class="order-cell">
				<text class="tit">订单类型：</text>
				<view class="box">{{ orderDetail.order_type_name }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">订单来源：</text>
				<view class="box">{{ orderDetail.order_from_name }}</view>
			</view>
			<view class="order-cell" v-if="orderDetail.pay_status > 0">
				<text class="tit">付款方式：</text>
				<view class="box">{{ orderDetail.pay_type_name }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">买家：</text>
				<view class="box">{{ orderDetail.nickname }}</view>
			</view>
			<view class="order-cell" v-if="orderDetail.delivery_type_name">
				<text class="tit">配送方式：</text>
				<view class="box">{{ orderDetail.delivery_type_name }}</view>
			</view>
			<view class="order-cell" v-if="orderDetail.promotion_type_name != ''">
				<text class="tit">营销活动：</text>
				<view class="box">{{ orderDetail.promotion_type_name }}</view>
			</view>
			<view class="order-cell" v-if="orderDetail.buyer_message != ''">
				<text class="tit">买家留言：</text>
				<view class="box">{{ orderDetail.buyer_message }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">创建时间：</text>
				<view class="box">{{ $util.timeStampTurnTime(orderDetail.create_time) }}</view>
			</view>
			<view class="order-cell" v-if="orderDetail.close_time > 0">
				<text class="tit">关闭时间：</text>
				<view class="box">{{ $util.timeStampTurnTime(orderDetail.close_time) }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">支付时间：</text>
				<view class="box">{{ $util.timeStampTurnTime(orderDetail.pay_time) }}</view>
			</view>
			<view class="order-cell" v-if="orderDetail.remark">
				<text class="tit">卖家备注：</text>
				<view class="box">{{ orderDetail.remark }}</view>
			</view>
		</view>

		<!-- 发票信息 -->
		<template v-if="orderDetail.is_invoice == 1">
			<view class="block-wrap">
				<view class="title">发票信息</view>
				<view class="order-cell">
					<text class="tit">发票类型：</text>
					<view class="box">{{ orderDetail['invoice_type'] == 1 ? '纸质' : '电子' }}{{ orderDetail['is_tax_invoice'] == 1 ? '专票' : '普票' }}</view>
				</view>
				<view class="order-cell">
					<text class="tit">发票抬头：</text>
					<view class="box">{{ orderDetail.invoice_title }}</view>
				</view>
				<view class="order-cell">
					<text class="tit">抬头类型：</text>
					<view class="box">{{ orderDetail.invoice_title_type == 1 ? '个人' : '企业' }}</view>
				</view>
				<view class="order-cell" v-if="orderDetail.invoice_title_type == 2">
					<text class="tit">纳税人识别号：</text>
					<view class="box">{{ orderDetail.taxpayer_number }}</view>
				</view>
				<view class="order-cell">
					<text class="tit">发票内容：</text>
					<view class="box">{{ orderDetail.invoice_content }}</view>
				</view>
				<view class="order-cell" v-if="orderDetail.invoice_title_type == 1">
					<text class="tit">邮寄地址：</text>
					<view class="box">{{ orderDetail.invoice_full_address }}</view>
				</view>
				<view class="order-cell" v-else>
					<text class="tit">接收邮件：</text>
					<view class="box">{{ orderDetail.invoice_email }}</view>
				</view>
			</view>

			<view class="block-wrap">
				<view class="title">发票费用</view>
				<view class="order-cell">
					<text class="tit">发票费用：</text>
					<view class="box align-right money">￥{{ orderDetail.invoice_money }}</view>
				</view>
				<view class="order-cell">
					<text class="tit">发票税率：</text>
					<view class="box align-right money">{{ orderDetail.invoice_rate }}%</view>
				</view>
				<view class="order-cell">
					<text class="tit">邮寄费用：</text>
					<view class="box align-right money">￥{{ orderDetail.invoice_delivery_money }}</view>
				</view>
			</view>
		</template>

		<view class="block-wrap tit-auto" v-if="orderDetail.pay_status > 0">
			<view class="title">结算信息</view>
			<view class="order-cell">
				<text class="tit">店铺结算金额：</text>
				<view class="box align-right money">￥{{ orderDetail.shop_money }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">平台结算金额：</text>
				<view class="box align-right money">￥{{ orderDetail.platform_money }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">结算状态：</text>
				<view class="box align-right money">{{ orderDetail.is_settlement == 1 ? '已结算' : '待结算' }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">店铺退款金额：</text>
				<view class="box align-right money">￥{{ orderDetail.refund_shop_money }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">平台退款金额：</text>
				<view class="box align-right money">￥{{ orderDetail.refund_platform_money }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">平台优惠券平台承担金额：</text>
				<view class="box align-right money">￥{{ orderDetail.platform_coupon_money }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">平台优惠券平台承担金额（退款部分）：</text>
				<view class="box align-right money">￥{{ orderDetail.refund_platform_coupon_money }}</view>
			</view>
			<view class="order-cell">
				<text class="tit">总支出佣金：</text>
				<view class="box align-right money">￥{{ orderDetail.commission }}</view>
			</view>
		</view>

		<!-- 订单金额 -->
		<view class="block-wrap">
			<view class="order-cell">
				<text class="tit">商品金额</text>
				<view class="box align-right money bold">
					<text class="font-size-goods-tag">￥</text>
					<text class="color-title">{{ orderDetail.goods_money }}</text>
				</view>
			</view>
			<view class="order-cell" v-if="orderDetail.coupon_money > 0">
				<text class="tit">店铺优惠券</text>
				<view class="box align-right money bold">
					<text class="operator">-</text>
					<text class="font-size-goods-tag">￥</text>
					<text>{{ orderDetail.coupon_money }}</text>
				</view>
			</view>
			<view class="order-cell" v-if="orderDetail.promotion_money > 0">
				<text class="tit">店铺优惠</text>
				<view class="box align-right money bold">
					<text class="operator">-</text>
					<text class="font-size-goods-tag">￥</text>
					<text>{{ orderDetail.promotion_money }}</text>
				</view>
			</view>
			<view class="order-cell" v-if="orderDetail.adjust_money != 0">
				<text class="tit">订单调价</text>
				<view class="box align-right money bold">
					<text class="operator" v-if="orderDetail.adjust_money < 0">-</text>
					<text class="operator" v-else>+</text>
					<text class="font-size-goods-tag">￥</text>
					<text>{{ orderDetail.adjust_money | abs }}</text>
				</view>
			</view>
			<view class="order-cell" v-if="orderDetail.delivery_money > 0">
				<text class="tit">运费</text>
				<view class="box align-right money bold">
					<text class="font-size-goods-tag">￥</text>
					<text>{{ orderDetail.delivery_money }}</text>
				</view>
			</view>
			<view class="order-cell" v-if="orderDetail.platform_coupon_total_money > 0">
				<text class="tit">平台优惠券</text>
				<view class="box align-right money bold">
					<text class="operator">-</text>
					<text class="font-size-goods-tag">￥</text>
					<text>{{ orderDetail.platform_coupon_total_money }}</text>
				</view>
			</view>
			<view class="order-cell" v-if="orderDetail.balance_money > 0">
				<text class="tit">使用余额</text>
				<view class="box align-right money bold">
					<text class="operator">-</text>
					<text class="font-size-goods-tag">￥</text>
					<text>{{ orderDetail.balance_money }}</text>
				</view>
			</view>
			<view class="order-cell">
				<view class="box align-right money bold">
					<text>共{{ orderDetail.goods_num }}件商品，总计：</text>
					<text class="font-size-goods-tag color-base-text">￥</text>
					<text class="font-size-base color-base-text">{{ orderDetail.order_money }}</text>
				</view>
			</view>
		</view>
		<view class="block-wrap log" v-if="log.length">
			<view class="title color-base-text">订单日志</view>
			<view class="item" v-for="(item, index) in log" :key="index">
				<view class="action">
					<view class="title">
						<text class="font-size-base">操作：{{ item.action }}</text>
						<text class="color-sub time">{{ $util.timeStampTurnTime(item.action_time, 1) }}</text>
					</view>
					<view>
						<text class="color-tip">操作人：{{ item.nick_name }}</text>
						<br />
						<text class=" color-tip">操作端口：{{ item.module }}</text>
						<br />
						<text class=" color-tip">订单状态：{{ item.order_status_name }}</text>
					</view>
				</view>
			</view>
		</view>
		<view class="block-wrap tips">
			<view class="title color-base-text">提醒</view>
			<text>交易成功后，平台将把货款结算至你的店铺账户余额，您可以申请提现</text>
			<text>请及时关注你发出的包裹状态，确保能配送至买家手中</text>
			<text>如果买家表示未收到货或者货物有问题，请及时联系买家积极处理，友好协商</text>
		</view>

		<view class="placeholder-height"></view>

		<view class="footer-wrap">
			<view class="container">
				<button type="primary" size="mini" plain @click="orderRemark()">备注</button>
				<button type="primary" size="mini" plain @click="goLogistics()" v-if="orderDetail.package_list && orderDetail.package_list.length">查看物流</button>
				<button
					type="primary"
					size="mini"
					v-for="(actionItem, actionIndex) in orderDetail.order_status_action.action"
					:key="actionIndex"
					@click="orderAction(actionItem.action, orderId)"
				>
					{{ actionItem.title }}
				</button>
				<button v-if="orderDetail.order_status == 0" type="primary" size="mini" @click="offlinePay(orderId)">线下支付</button>
			</view>
		</view>
		<ns-order-remark ref="orderRemark" :order="tempOrder"></ns-order-remark>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import uniCountDown from '@/components/uni-count-down/uni-count-down.vue';
import nsOrderRemark from '@/components/ns-order-remark/ns-order-remark';
import action from '../js/action.js';
import detail from '../js/detail.js';
export default {
	components: {
		uniCountDown,
		nsOrderRemark
	},
	mixins: [action, detail]
};
</script>

<style lang="scss">
@import '../css/detail.scss';
</style>
<style scoped>
.time >>> .uni-countdown {
	margin: 0;
}
.time >>> .uni-countdown__number {
	border-radius: 0;
	padding: 0 4rpx;
	margin: 0;
	border: none;
	font-size: 14px;
	line-height: 1;
}
.time >>> .uni-countdown__splitor {
	font-size: 14px;
	line-height: 1;
}
.time >>> .uni-countdown__splitor.day {
}
</style>
