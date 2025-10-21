<template>
	<view class="agreement">
		<view class="agreement-title">签订入驻协议</view>
		<view class="agreement-content">
			<view class="agreement-item">{{ title }}</view>
			<rich-text :nodes="content"></rich-text>
		</view>
		<view class="agreement-btn">
			<view @click="isChecked()" class="checkbox-wrap">
				<text class="checkbox iconfont" :class="checked ? 'iconfuxuankuang1 color-base-text' : 'iconfuxuankuang2'"></text>
				<text class="color-base-text">我已阅读并同意以上协议</text>
			</view>
			<button type="primary" @click="toApplyInfo()">下一步，填写申请信息</button>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import htmlParser from '@/common/js/html-parser';
export default {
	data() {
		return {
			type: 1,
			checked: false,
			title: '',
			content: ''
		};
	},
	onLoad(option) {
		this.type = option.type || 1;
	},
	onShow() {
		this.initData();
	},
	methods: {
		isChecked() {
			this.checked = !this.checked;
		},
		toApplyInfo() {
			if (this.checked == false) {
				this.$util.showToast({
					title: '请先同意协议'
				});
			} else {
				if (this.type == 1) {
					this.$util.redirectTo('/pages/apply/fastinfo');
				} else if (this.type == 2) {
					this.$util.redirectTo('/pages/apply/shopset');
				}
			}
		},
		initData() {
			this.$api.sendRequest({
				url: '/shopapi/apply/index',
				success: res => {
					var data = res.data;
					if (res.code == 0 && data.shop_apply_agreement.content) {
						this.title = data.shop_apply_agreement.title;
						this.content = htmlParser(data.shop_apply_agreement.content);
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		}
	}
};
</script>

<style lang="scss">
page {
	background-color: #fff;
}

.agreement {
	width: 100%;
	padding: 50rpx 30rpx;
	overflow: hidden;
	box-sizing: border-box;

	.agreement-title {
		font-size: 40rpx;
		font-weight: bold;
		text-align: center;
		margin-bottom: 30rpx;
	}

	.agreement-content {
		width: 100%;

		.agreement-item {
			font-size: 30rpx;
			text-align: justify;
		}
	}

	.agreement-btn {
		text-align: center;

		.checkbox-wrap {
			.checkbox {
				color: $color-tip;
				margin-right: 10rpx;
			}
			text {
				vertical-align: middle;
				display: inline-block;
			}
		}
		button {
			margin-top: 40rpx;
		}
	}
}
</style>
