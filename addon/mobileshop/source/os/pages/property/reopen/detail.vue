<template>
	<view class="withdrawal iphone-safe-area">
		<view class="dl">
			<view class="dt">店铺名称</view>
			<view class="dd">{{ base_info.site_name }}</view>
		</view>
		<view class="dl">
			<view class="dt">开店套餐</view>
			<view class="dd">{{ base_info.shop_group_name }}</view>
		</view>
		<view class="dl">
			<view class="dt">续签时长</view>
			<view class="dd">{{ base_info.apply_year }}年</view>
		</view>
		<view class="dl">
			<view class="dt">支付凭证</view>
			<view class="dd"><image class="img" :src="$util.img(base_info.paying_money_certificate)" @click="previewMedia()" mode="aspectFit"></image></view>
		</view>
		<view class="dl">
			<view class="dt">付款凭证说明</view>
			<view class="dd">{{ base_info.paying_money_certificate_explain }}</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			id: 0,
			base_info: {}
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/property/reopen/detail?id=' + this.id)) return;
	},
	onLoad(option) {
		if (option.id) {
			this.id = option.id;
			this.getBaseInfo();
		} else {
			this.$util.goBack('/pages/property/reopen/list');
		}
	},
	methods: {
		getBaseInfo() {
			this.$api.sendRequest({
				url: '/shopapi/shopreopen/getReopenInfo',
				data: {
					id: this.id
				},
				success: res => {
					if (res.code >= 0) {
						this.base_info = res.data;
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		previewMedia() {
			var paths = [this.$util.img(this.base_info.paying_money_certificate)];
			uni.previewImage({
				current: 0,
				urls: paths
			});
		}
	}
};
</script>

<style lang="scss">
.withdrawal {
	padding: $padding 0;
	border-radius: $border-radius;
	overflow: hidden;
	margin: 0 $margin-both;
	.dl {
		display: flex;
		justify-content: space-between;
		padding: 30rpx;
		border-bottom: 1px solid $color-line;
		background-color: #fff;
		&:last-child {
			border-bottom: 0 !important;
		}
		.dt{
			min-width:  200rpx;
		}
		.dd {
			flex:1;
			text-align: right;
			word-break:break-all;
			.img {
				height: 80rpx;
				width: 80rpx;
			}
		}
	}
}
</style>
