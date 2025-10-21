<template>
	<view>
		<view class="successfully">
			<image :src="$util.img('upload/uniapp/shop_uniapp/login/apply_succeed.png')" mode=""></image>
			<text class="text color-base-text">恭喜您开店成功，快进行体验吧~</text>
		</view>
		<button type="primary" @click="goIndex()">进入店铺</button>
	</view>
</template>

<script>
export default {
	data() {
		return {};
	},
	methods: {
		goIndex() {
			this.$api.sendRequest({
				url: '/shopapi/apply/simulatedLogin',
				success: res => {
					if (res.code == 0) {
						uni.setStorageSync('token', res.data.token);
						uni.setStorageSync('site_id', res.data.site_id);
						this.$util.redirectTo('/pages/index/index', {}, 'redirectTo');
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		}
	}
};
</script>

<style lang="scss" scoped>
.successfully {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	image {
		width: 200rpx;
		height: 200rpx;
		margin-top: 200rpx;
	}
	.text {
		font-size: $font-size-toolbar;
		margin-top: 70rpx;
	}
}
button {
	margin: 60rpx 60rpx 0 60rpx;
}
</style>
