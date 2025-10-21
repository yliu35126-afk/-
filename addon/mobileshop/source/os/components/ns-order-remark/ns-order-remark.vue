<template>
	<!-- 卖家备注弹出层 -->
	<uni-popup ref="remarkPopup" type="center">
		<view class="popup" @touchmove.prevent.stop>
			<view class="popup-header">
				<text class="tit">卖家备注</text>
				<text class="iconfont iconclose" @click="close()"></text>
			</view>
			<view class="popup-body"><textarea class="uni-input" v-model="order.remark" placeholder="请输入卖家备注" maxlength="200" /></view>
			<view class="popup-footer">
				<button type="primary" @click="saveRemark()">确定</button>
			</view>
		</view>
	</uni-popup>
</template>

<script>
export default {
	data() {
		return {
			callback: null,
			repeatFlag: false
		};
	},
	props: {
		order: {
			type: Object,
			default: () => {
				return {
					remark: ''
				};
			}
		}
	},
	methods: {
		show(callback) {
			this.callback = callback;
			this.$refs['remarkPopup'].open();
		},
		close() {
			this.$refs['remarkPopup'].close();
		},
		saveRemark() {
			if (this.order.remark.length == 0) {
				this.$util.showToast({
					title: '请输入卖家备注'
				});
				return;
			}

			if (this.repeatFlag) return;
			this.repeatFlag = true;

			this.$api.sendRequest({
				url: '/shopapi/order/orderRemark',
				data: {
					order_id: this.order.order_id,
					remark: this.order.remark
				},
				success: res => {
					if (res.code == 0) {
						if (this.callback) this.callback();
						this.close();
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
.popup {
	width: 80vw;
	background: #fff;
	border-top-left-radius: 24rpx;
	border-top-right-radius: 24rpx;

	.popup-header {
		display: flex;
		border-bottom: 2rpx solid $color-line;
		position: relative;
		padding: 40rpx;

		.tit {
			flex: 1;
			font-size: $font-size-toolbar;
			line-height: 1;
			text-align: center;
		}
		.iconfont {
			line-height: 1;
			position: absolute;
			right: 30rpx;
			top: 50%;
			transform: translate(0, -50%);
			color: $color-tip;
			font-size: $font-size-toolbar;
		}
	}

	.popup-body {
		padding: 20rpx;
		textarea {
			width: 100%;
		}
	}

	.popup-footer {
		height: 120rpx;
	}
}
</style>
