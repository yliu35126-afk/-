<template>
	<view class="shop-empty">
		<view class="way-item" v-if="idExperience == 1">
			<view class="item-img color-base-bg-light"><image :src="$util.img('/upload/uniapp/apply/way1.png')" mode="aspectFit"></image></view>
			<view class="item-text">
				<text class="text-title">快速开店</text>
				<text class="text-content color-tip">一键开店，抢先体验</text>
			</view>
			<view class="item-btn color-base-bg" @click="setUpShop(1)">立即开店</view>
		</view>
		<view class="way-item">
			<view class="item-img color-base-bg-light"><image :src="$util.img('/upload/uniapp/apply/way2.png')" mode="aspectFit"></image></view>
			<view class="item-text">
				<text class="text-title">申请开店</text>
				<text class="text-content color-tip">入驻加盟，合作共赢</text>
			</view>
			<view class="item-btn color-base-bg" @click="setUpShop(2)">立即开店</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			idExperience: 0
		};
	},
	onLoad() {
		this.getShopWithdrawConfig();
	},
	methods: {
		getShopWithdrawConfig() {
			this.$api.sendRequest({
				url: '/shopapi/apply/getShopWithdrawConfig',
				success: res => {
					if (res.data) {
						this.idExperience = res.data.id_experience;
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		setUpShop(type) {
			this.$util.redirectTo('/pages/apply/agreement', {
				type
			});
		}
	}
};
</script>

<style lang="scss">
page {
	overflow: hidden;
}

.shop-empty {
	margin: 30rpx 20rpx;

	.way-item {
		display: flex;
		margin-bottom: 20rpx;
		padding: 30rpx 20rpx;
		justify-content: space-between;
		background-color: #fff;

		.item-img {
			overflow: hidden;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 20rpx;
			width: 130rpx;
			height: 130rpx;
			border-radius: 50%;

			image {
				width: 60rpx;
				height: 60rpx;
			}
		}

		.item-text {
			display: flex;
			flex-direction: column;
			justify-content: center;
			margin-right: auto;
		}

		.text-title {
			font-size: $font-size-base;
		}

		.text-content {
			font-size: $font-size-tag;
		}

		.item-btn {
			align-self: center;
			display: flex;
			align-items: center;
			justify-content: center;
			width: 160rpx;
			height: 60rpx;
			color: #fff;
			font-size: $font-size-tag;
			border-radius: 100rpx;
		}
	}
}
</style>
