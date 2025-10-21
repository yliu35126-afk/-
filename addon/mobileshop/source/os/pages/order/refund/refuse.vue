<template>
	<view>
		<!-- 售后申请拒绝 -->
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
			<view class="form-wrap reason">
				<text class="label">拒绝理由</text>
				<textarea class="uni-input" v-model="refundRefuseReason" placeholder="请输入您的拒绝理由" maxlength="200" />
			</view>
		</view>
		<view class="tips color-base-text">注意: 建议你与买家协商后，再确定是否拒绝退款。如你拒绝退款后，买家可修改退款申请协议重新发起退款。</view>
		<view class="footer-wrap">
			<button type="default" @click="cancel()">取消</button>
			<button type="primary" @click="save()">确认拒绝</button>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import refundAction from '../js/refund_action.js';
export default {
	data() {
		return {
			refundRefuseReason: ''
		};
	},
	mixins: [refundAction],
	methods: {
		save() {
			if (this.refundRefuseReason.length == 0) {
				this.$util.showToast({
					title: '请输入拒绝理由'
				});
				return;
			}

			if (this.repeatFlag) return;
			this.repeatFlag = true;

			this.$api.sendRequest({
				url: '/shopapi/orderrefund/refuse',
				data: {
					order_goods_id: this.orderGoodsId,
					refund_refuse_reason: this.refundRefuseReason
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
