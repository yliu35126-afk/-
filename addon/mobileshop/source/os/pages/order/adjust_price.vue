<template>
	<view class="container" :class="{'safe-area':isIphoneX}">
		<view class="tips">注意：只有订单未付款时才支持改价,改价后请联系买家刷新订单核实订单金额后再支付。</view>
		<view class="order-goods-list">
			<view class="form-title">商品信息</view>
			<view class="goods-item" v-for="(item, index) in order.order_goods" :key="index">
				<view class="goods-img"><image :src="$util.img(item.sku_image, { size: 'mid' })" mode="widthFix" @error="imgError(index)"></image></view>
				<view class="info-wrap">
					<view class="name-wrap">{{ item.sku_name }}</view>
					<view class="price-wrap">
						<view class="num">x{{ item.num }}</view>
						<view class="price">
							<text class="unit">￥</text>
							<text>{{ item.price }}</text>
						</view>
					</view>
					<view class="total-wrap">
						<text class="label">小计：</text>
						<view class="price color-base-text">
							<text class="unit">￥</text>
							<text>{{ item.goods_money }}</text>
						</view>
					</view>
				</view>
			</view>
			<view class="form-title">订单信息</view>
			<view class="order-info-wrap">
				<view class="cell">
					<text class="label">商品总额</text>
					<text class="value">{{ order.goods_money }}</text>
					<text class="unit">元</text>
				</view>
				<view class="cell">
					<text class="label">优惠</text>
					<text class="value">{{ order.promotion_money }}</text>
					<text class="unit">元</text>
				</view>
				<view class="cell">
					<text class="label">优惠券</text>
					<text class="value">{{ order.coupon_money }}</text>
					<text class="unit">元</text>
				</view>
				<view class="cell">
					<text class="label">平台优惠券</text>
					<text class="value">{{ order.platform_coupon_total_money }}</text>
					<text class="unit">元</text>
				</view>
				<view class="cell">
					<text class="label">余额</text>
					<text class="value">{{ order.balance_money }}</text>
					<text class="unit">元</text>
				</view>
				<view class="cell">
					<text class="label">发票费用</text>
					<text class="value">{{ order.invoice_money }}</text>
					<text class="unit">元</text>
				</view>
				<view class="cell">
					<text class="label">发票邮寄</text>
					<text class="value">{{ order.invoice_delivery_money }}</text>
					<text class="unit">元</text>
				</view>
				<view class="cell">
					<text class="label">总计</text>
					<text class="value">{{ order.pay_money }}</text>
					<text class="unit">元</text>
				</view>
			</view>
			<view class="form-title">调整价格</view>
			<view class="item-wrap">
				<view class="form-wrap">
					<text class="label">调整金额</text>
					<input class="uni-input" v-model="order.adjust_money" @input="adjustChange()" type="digit" placeholder="0.00" />
					<text class="unit">元</text>
				</view>
				<view class="form-wrap">
					<text class="label">运费</text>
					<input class="uni-input" v-model="order.delivery_money" @input="adjustChange()" type="digit" placeholder="0.00" />
					<text class="unit">元</text>
				</view>
			</view>
		</view>
		<view class="tips bottom">
			<view>
				<text class="dot color-base-text">·</text>
				<text class="color-base-text">实际商品金额</text>
				<text class="interval">=</text>
				<text>商品总额</text>
				<text class="interval">-</text>
				<text>优惠金额</text>
				<text class="interval">-</text>
				<text>优惠券金额</text>
				<text class="interval">-</text>
				<text>平台优惠券金额</text>
				<text class="interval">+</text>
				<text>调价</text>
			</view>
			<view>
				<text class="dot color-base-text">·</text>
				<text class="color-base-text">发票费用</text>
				<text class="interval">=</text>
				<text>实际商品金额 * 发票比率</text>
			</view>
			<view>
				<text class="dot color-base-text">·</text>
				<text>实付金额</text>
				<text class="interval">=</text>
				<text class="color-base-text">实际商品金额</text>
				<text class="interval">+</text>
				<text class="color-base-text">发票费用</text>
				<text class="interval">+</text>
				<text>运费</text>
				<text class="interval">+</text>
				<text>发票邮寄费用</text>
				<text class="interval">-</text>
				<text>余额</text>
			</view>
		</view>
		<view class="footer-wrap" :class="{'safe-area':isIphoneX}"><button type="primary" @click="save()">确定</button></view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			isIphoneX: false,
			orderId: 0,
			order: {},
			repeatFlag: false
		};
	},
	onLoad(option) {
		this.isIphoneX = this.$util.uniappIsIPhoneX();
		this.orderId = option.order_id || 0;
		this.getOrderDetail();
	},
	onShow() {},
	methods: {
		getOrderDetail() {
			this.$api.sendRequest({
				url: '/shopapi/order/getOrderDetail',
				data: {
					order_id: this.orderId
				},
				success: res => {
					if (res.code == 0) {
						this.order = res.data;
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					} else {
						this.$util.showToast({
							title: res.message
						});
						setTimeout(() => {
							uni.navigateBack({
								delta: 1
							});
						}, 1000);
					}
				}
			});
		},
		// 计算价格
		adjustChange() {
			var o_balance_money = this.order.balance_money;
			var invoice_delivery_money = this.order.invoice_delivery_money;
			var promotion_money = this.order.promotion_money;
			var platform_coupon_total_money = this.order.platform_coupon_total_money;
			var coupon_money = this.order.coupon_money;
			var goods_money = this.order.goods_money;
			var is_invoice = this.order.is_invoice;
			var adjust_money = this.order.adjust_money;
			var delivery_money = this.order.delivery_money;
			var real_goods_money =
				parseFloat(goods_money) - parseFloat(promotion_money) - parseFloat(coupon_money) + parseFloat(adjust_money) - parseFloat(platform_coupon_total_money);
			var invoice_rate = is_invoice == 1 ? this.order.invoice_rate : 0;

			var invoice_money = Math.round(((parseFloat(real_goods_money) * parseFloat(invoice_rate)) / 100) * 100) / 100;

			var total_money =
				parseFloat(goods_money) -
				parseFloat(promotion_money) -
				parseFloat(coupon_money) -
				parseFloat(platform_coupon_total_money) +
				parseFloat(adjust_money) +
				parseFloat(invoice_delivery_money) +
				parseFloat(invoice_money) +
				parseFloat(delivery_money) -
				parseFloat(o_balance_money);
			total_money = Math.round(total_money * 100) / 100;

			if (isNaN(invoice_money)) invoice_money = 0;
			if (isNaN(total_money)) total_money = 0;

			this.order.invoice_money = invoice_money.toFixed(2);
			this.order.pay_money = total_money.toFixed(2);
		},
		// 订单调价
		save() {
			if (this.order.adjust_money.length == 0) {
				this.$util.showToast({
					title: '请输入调整金额'
				});
				return;
			}

			if (isNaN(this.order.adjust_money)) {
				this.$util.showToast({ title: `[调整金额]格式输入错误` });
				return;
			}
			if (this.order.delivery_money.length == 0) {
				this.$util.showToast({
					title: '请输入运费'
				});
				return;
			}

			if (isNaN(this.order.delivery_money)) {
				this.$util.showToast({ title: `[运费]格式输入错误` });
				return;
			}

			if (this.repeatFlag) return;
			this.repeatFlag = true;

			this.$api.sendRequest({
				url: '/shopapi/order/adjustPrice',
				data: {
					order_id: this.orderId,
					adjust_money: this.order.adjust_money,
					delivery_money: this.order.delivery_money
				},
				success: res => {
					this.$util.showToast({
						title: res.message
					});
					if (res.code == 0) {
						setTimeout(() => {
							uni.navigateBack({
								delta: 1
							});
						}, 1000);
					} else {
						this.repeatFlag = false;
					}
				}
			});
		}
	}
};
</script>

<style lang="scss">
.tips {
	margin: $margin-updown $margin-both;
	&,
	view,
	text {
		color: $color-tip;
		font-size: $font-size-tag;
	}
	.interval {
		margin: 0 4rpx;
	}
	.dot {
		font-weight: bold;
		margin-right: 10rpx;
	}
	&.bottom {
		margin-bottom: 160rpx;
	}
}
.order-goods-list {
	.goods-item {
		background: #fff;
		margin-top: $margin-updown;
		display: flex;
		position: relative;
		flex-flow: row wrap;
		padding: 20rpx 30rpx;

		.goods-img {
			width: 160rpx;
			height: 160rpx;
			overflow: hidden;
			border-radius: $border-radius;
			margin-right: 20rpx;

			image {
				width: 100%;
				height: 100%;
			}
		}

		.info-wrap {
			flex: 1;
			display: flex;
			flex-direction: column;
			.name-wrap {
				font-size: $font-size-base;
				line-height: 1.3;
				overflow: hidden;
				text-overflow: ellipsis;
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
				margin-bottom: 10rpx;
			}
			.price-wrap {
				display: flex;
				margin-top: 16rpx;
				.num {
					color: $color-tip;
					font-size: $font-size-activity-tag;
					line-height: 1;
				}

				.price {
					display: inline-block;
					line-height: 1;
					font-size: $font-size-base;
					flex: 1;
					text-align: right;
				}
				.unit {
					font-size: $font-size-activity-tag;
				}
			}
		}

		.total-wrap {
			text-align: right;
			.label {
				color: $color-tip;
			}
			.num {
				color: $color-tip;
				font-size: $font-size-activity-tag;
				line-height: 1;
			}
			.price {
				display: inline-block;
				font-weight: bold;
				font-size: $font-size-toolbar;
				text-align: right;
			}
			.unit {
				font-size: $font-size-activity-tag;
				margin: 0;
			}
		}
	}
	.order-info-wrap {
		background: #fff;
		margin-top: $margin-updown;
		.cell {
			display: flex;
			align-items: center;
			margin: 0 $margin-both;
			height: 80rpx;
			line-height: 80rpx;
			.label {
				vertical-align: middle;
				margin-right: $margin-both;
			}
			.value {
				vertical-align: middle;
				display: inline-block;
				flex: 1;
				text-align: right;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: pre;
			}
		}
	}
	.form-title {
		margin: $margin-updown $margin-both;
		color: $color-tip;
	}
	.unit {
		margin-left: 20rpx;
		width: 40rpx;
	}
	.item-wrap {
		background: #fff;
		margin-top: $margin-updown;
		.form-wrap {
			display: flex;
			align-items: center;
			margin: 0 $margin-both;
			border-bottom: 1px solid $color-line;
			height: 100rpx;
			line-height: 100rpx;
			&:last-child {
				border-bottom: none;
			}
			.label {
				vertical-align: middle;
				margin-right: $margin-both;
			}
			input {
				vertical-align: middle;
				display: inline-block;
				flex: 1;
				text-align: right;
			}
		}
	}
}
.footer-wrap {
	position: fixed;
	background-color: #fff;
	width: 100%;
	bottom: 0;
	padding: 40rpx 0;
	z-index: 10;
	padding-bottom: 40rpx;
	padding-bottom: 40rpx;
}
.container{
	padding-bottom: 40rpx;
	padding-bottom: 40rpx;
}
.safe-area{
	padding-bottom: calc(constant(safe-area-inset-bottom) + 40rpx);
	padding-bottom: calc(env(safe-area-inset-bottom) + 40rpx);
}
</style>
