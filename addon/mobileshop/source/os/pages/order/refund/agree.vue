<template>
	<view>
		<!-- 售后申请同意 -->
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
		</view>
		<view class="tips color-base-text">注意: 该笔订单通过在线付款，商家同意后，退款将自动原路退回买家付款账户。</view>
		<view class="footer-wrap">
			<button type="default" @click="cancel()">取消</button>
			<button type="primary" @click="save()">确认退款</button>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import refundAction from '../js/refund_action.js';
export default {
	data() {
		return {};
	},
	mixins: [refundAction],
	methods: {
		save() {
			if (this.repeatFlag) return;
			this.repeatFlag = true;
			this.$api.sendRequest({
				url: '/shopapi/orderrefund/agree',
				data: {
					order_goods_id: this.orderGoodsId
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
