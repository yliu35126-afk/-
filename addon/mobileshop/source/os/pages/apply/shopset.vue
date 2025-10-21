<template>
	<view class="shop-set">
		<view class="set-title">选择开店套餐</view>
		<view class="set-content">
			<view class="set-item" v-for="(item, index) in group_info" :key="index" @click="setChange(index)">
				<view class="item-title">
					<text class="title-name">{{ item.group_name }}</text>
					<text class="title-desc uni-line-hide" v-if="item.remark">{{ item.remark }}</text>
					<view class="title-price color-base-bg">￥{{ item.fee }}/年</view>
				</view>
				<view class="item-content">
					<view class="module-item" v-for="(promotion, index) in item.promotion" :key="index">
						<text class="list-yes module-ident color-base-text iconyuan_checked iconfont" v-if="promotion.is_checked == 1"></text>
						<text class="list-no module-ident iconcuohao iconfont" v-else></text>
						<view class="module-text">{{ promotion.title }}</view>
					</view>
				</view>
			</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			isRenew: false, //是否续签
			group_info: []
		};
	},
	onShow() {
		this.getSetInfo();
		let renewObj = uni.getStorageSync('renewObj') ? JSON.parse(uni.getStorageSync('renewObj')) : null;
		if (renewObj && renewObj.cert_id != 0) this.isRenew = true;
	},
	methods: {
		setChange(index) {
			let obj = {};
			obj.group_id = this.group_info[index].group_id;
			obj.group_name = this.group_info[index].group_name;
			uni.setStorage({
				key: 'shopPackage',
				data: JSON.stringify(obj)
			});
			if (this.isRenew) this.$util.redirectTo('/pages/renew/apply');
			else this.$util.redirectTo('/pages/apply/openinfo');
		},
		//获取套餐信息
		getSetInfo() {
			this.$api.sendRequest({
				url: '/shopapi/apply/groupInfo',
				success: res => {
					if (res.code == 0 && res.data) {
						this.group_info = res.data;
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

<style lang="scss" scoped>
.shop-set {
	padding: 40rpx 30rpx;

	.set-title {
		margin: 0 0 20rpx;
		font-size: $font-size-toolbar;
		font-weight: bold;
		color: $color-title;
	}

	.set-item {
		padding: 30rpx;
		margin-bottom: 20rpx;
		background-color: #fff;
		border-radius: 10rpx;

		.item-title {
			position: relative;
			display: flex;
			flex-direction: column;
			padding-bottom: 30rpx;
			border-bottom: 2rpx solid $color-line;

			.title-name {
				font-size: $font-size-toolbar;
				color: $color-title;
				width: 65%;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			.title-desc {
				margin-top: 12rpx;
				font-size: $font-size-tag;
				color: $color-tip;
			}

			.title-price {
				position: absolute;
				top: 0;
				right: 0;
				display: flex;
				align-items: center;
				justify-content: center;
				width: 200rpx;
				height: 55rpx;
				color: #fff;
				font-size: $font-size-tag;
				border-radius: 100rpx;
			}
		}

		.item-content {
			display: flex;
			flex-wrap: wrap;
			margin-top: 30rpx;
		}

		.module-item {
			display: flex;
			flex-wrap: wrap;
			align-items: center;
			width: 210rpx;

			.module-ident {
				margin-right: 10rpx;
				color: $color-disabled;
				font-size: $font-size-toolbar;
				border-radius: 50%;
			}

			.module-text {
				overflow: hidden;
				white-space: nowrap;
				text-overflow: ellipsis;
				width: 166rpx;
			}
		}
	}
}
</style>
