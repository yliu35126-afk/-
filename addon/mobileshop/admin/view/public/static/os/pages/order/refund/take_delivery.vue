<template>
	<view>
		<!-- 维权收货 -->
		<view class="item-wrap">
			<view class="form-wrap">
				<text class="label">退款方式</text>
				<text class="value">{{ detail.refund_type == 1 ? '仅退款' : '退货退款' }}</text>
			</view>
			<view class="form-wrap">
				<text class="label">退款金额</text>
				<text class="value color-base-text money">
					￥{{ detail.refund_apply_money }}{{ detail.refund_delivery_money > 0 ? '(含运费' + detail.refund_delivery_money + ')' : '' }}
				</text>
			</view>
			<view class="form-wrap">
				<text class="label">退货地址</text>
				<text class="value">{{ detail.refund_address }}</text>
			</view>
			<view class="form-wrap">
				<text class="label">是否入库</text>
				<view class="value">
					<view class="radio-wrap" @click="isRefundStock = 0">
						<text class="radio iconfont" :class="isRefundStock == 0 ? 'iconyuan_checked color-base-text' : 'iconyuan_checkbox'"></text>
						<text class="txt">否</text>
					</view>
					<view class="radio-wrap" @click="isRefundStock = 1">
						<text class="radio iconfont" :class="isRefundStock == 1 ? 'iconyuan_checked color-base-text' : 'iconyuan_checkbox'"></text>
						<text class="txt">是</text>
					</view>
				</view>
			</view>
		</view>
		<view class="tips color-base-text">注意: 需你同意退款申请，买家才能退货给你；买家退货后你需再次确认收货后，退款将自动原路退回至买家付款账户。</view>
		<view class="footer-wrap">
			<button type="default" @click="cancel()">取消</button>
			<button type="primary" @click="save()">确认收到退货</button>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import refundAction from '../js/refund_action.js';
export default {
	data() {
		return {
			// 是否入库
			isRefundStock: 0
		};
	},
	mixins: [refundAction],
	methods: {
		save() {
			if (this.repeatFlag) return;
			this.repeatFlag = true;

			this.$api.sendRequest({
				url: '/shopapi/orderrefund/receive',
				data: {
					order_goods_id: this.orderGoodsId,
					is_refund_stock: this.isRefundStock
				},
				success: res => {
					if (res.code == 0) {
						setTimeout(() => {
							this.cancel();
						}, 1000);
					}
					this.repeatFlag = false;
					this.$util.showToast({
						title: res.message
					});
				}
			});
		}
	}
};
</script>

<style lang="scss">
@import '../css/refund_action.scss';
</style>
