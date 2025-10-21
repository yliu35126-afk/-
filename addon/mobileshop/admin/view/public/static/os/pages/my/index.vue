<template>
	<view class="my">
		<view class="content">
			<view class="shop_base_info color-base-bg">
				<view class="shop_info margin-left">
					<view class="shop_title">
						<text class="user-name">{{ userInfo.username }}</text>
						<text class="group-name color-base-text" v-if="userInfo.group_name">{{ userInfo.group_name }}</text>
					</view>
				</view>
				<text class="weixincode iconfont iconrichscan_icon" @click.stop="$util.redirectTo('/pages/verify/index')"></text>
			</view>
			<view class="list">
				<view class="item-wrap" @click="$util.redirectTo('/pages/my/shop/config')">
					<view class="info">
						<image class="img" :src="$util.img('/upload/uniapp/shop_uniapp/my/store.png')" mode="aspectFit"></image>
						<text>店铺信息</text>
					</view>
					<text class="iconfont iconright"></text>
				</view>
				<view class="item-wrap" @click="$util.redirectTo('/pages/my/shop/contact')">
					<view class="info">
						<image class="img" :src="$util.img('/upload/uniapp/shop_uniapp/my/address.png')" mode="aspectFit"></image>
						<text>联系地址</text>
					</view>
					<text class="iconfont iconright"></text>
				</view>
				<view class="item-wrap" @click="$util.redirectTo('/pages/my/user/user')">
					<view class="info">
						<image class="img" :src="$util.img('/upload/uniapp/shop_uniapp/my/member.png')" mode="aspectFit"></image>
						<text>用户管理</text>
					</view>
					<text class="iconfont iconright"></text>
				</view>
				<view class="item-wrap" @click="$util.redirectTo('/pages/login/modify_pwd')">
					<view class="info">
						<image class="img" :src="$util.img('/upload/uniapp/shop_uniapp/my/password.png')" mode="aspectFit"></image>
						<text>修改密码</text>
					</view>
					<text class="iconfont iconright"></text>
				</view>
				<view class="item-wrap" @click="$util.redirectTo('/pages/property/settlement/list_store')">
					<view class="info">
						<image class="img" :src="$util.img('/upload/uniapp/shop_uniapp/index/store_jiesuan.png')" mode="aspectFit"></image>
						<text>门店结算</text>
					</view>
					<text class="iconfont iconright"></text>
				</view>
				<view class="item-wrap" @click="$util.redirectTo('/pages/property/settlement/list')">
					<view class="info">
						<image class="img" :src="$util.img('/upload/uniapp/shop_uniapp/index/shop_jiesuan.png')" mode="aspectFit"></image>
						<text>店铺结算</text>
					</view>
					<text class="iconfont iconright"></text>
				</view>
				<view class="item-wrap" @click="$util.redirectTo('/pages/property/withdraw/list')">
					<view class="info">
						<image class="img" :src="$util.img('/upload/uniapp/shop_uniapp/index/tixian.png')" mode="aspectFit"></image>
						<text>提现记录</text>
					</view>
					<text class="iconfont iconright"></text>
				</view>
				<view class="item-wrap" @click="$util.redirectTo('/pages/verify/user')">
					<view class="info">
						<image class="img" :src="$util.img('/upload/uniapp/shop_uniapp/index/verify_peo.png')" mode="aspectFit"></image>
						<text>核销人员</text>
					</view>
					<text class="iconfont iconright"></text>
				</view>
			</view>
		</view>

		<view class="footer-wrap"><view class="primary" @click="openPopup()">退出登录</view></view>
		<uni-popup ref="loginOutPopup">
			<view class="pop-wrap" @touchmove.prevent.stop>
				<view class="title">您确定要退出当前账号吗？</view>
				<view class="action-btn">
					<view @click="closePopup()">取消</view>
					<view class="color-base-text" @click="loginOut()">确定</view>
				</view>
			</view>
		</uni-popup>
	</view>
</template>

<script>
export default {
	data() {
		return {
			userInfo: {}
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/index/index')) return;
		this.$nextTick(function() {
			this.userInfo = uni.getStorageSync('user_info') ? JSON.parse(uni.getStorageSync('user_info')) : {};
			this.$forceUpdate();
		});
	},
	methods: {
		loginOut() {
			uni.removeStorageSync('token');
			uni.removeStorageSync('site_id');
			uni.removeStorageSync('shop_info');
			uni.removeStorageSync('user_info');
			uni.removeStorageSync('shopPackage');
			uni.removeStorageSync('openInfo');
			uni.removeStorageSync('bankInfo');
			uni.removeStorageSync('renewObj');
			this.closePopup();
			this.$util.redirectTo('/pages/login/login');
		},
		openPopup() {
			this.$refs.loginOutPopup.open();
		},
		closePopup() {
			this.$refs.loginOutPopup.close();
		}
	}
};
</script>

<style lang="scss">
.my {
	.content {
		background-color: #fff;
		padding: 30rpx 0 10rpx;
	}
	.shop_base_info {
		display: flex;
		align-items: center;
		color: #fff;
		margin: 0 $margin-both;
		padding: 30rpx 0;
		border-radius: $border-radius;
		.shop_info {
			flex: 1;
			.shop_title {
				color: #fff;
				font-size: $font-size-toolbar;
				font-weight: 600;
				text {
					vertical-align: middle;
					display: inline-block;
					overflow: hidden;
					overflow: hidden;
					text-overflow: ellipsis;
					white-space: nowrap;
				}
				.user-name {
					max-width: 300rpx;
				}
				.group-name {
					font-size: $font-size-goods-tag;
					margin-left: $margin-updown;
					border: 1px solid #fff;
					background-color: #fff;
					border-radius: 50rpx;
					padding: 0 13rpx;
					height: 40rpx;
					line-height: 40rpx;
					font-weight: 400;
					max-width: 200rpx;
				}
			}
			.shop_other_info {
				color: #fff;
				margin-top: 10rpx;
				font-size: $font-size-tag;
			}
		}
		.weixincode {
			margin: 0 $margin-both;
			font-size: 36rpx;
			color: #fff;
		}
	}
	.list {
		margin: 0 $margin-both;
		padding-top: 30rpx;
		background-color: #fff;
		border-radius: $border-radius;
		.item-wrap {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 25rpx 0;
			border-bottom: 1px solid $color-line;
			&:last-child {
				border: none;
			}
			.info {
				display: flex;
				align-items: center;
				.img {
					height: 40rpx;
					width: 40rpx;
					margin-right: $margin-both;
					vertical-align: center;
				}
			}
			.iconfont {
				font-size: $font-size-activity-tag;
				color: $color-tip;
			}
		}
	}
	.primary {
		height: 100rpx;
		line-height: 100rpx;
		border-radius: $border-radius;
		background-color: #fff;
		color: $color-title;
		margin: 0;
		text-align: center;
	}
}
.footer-wrap {
	margin-top: 40rpx;
}
.pop-wrap {
	width: 80vw;
	.title {
		padding: $padding 30rpx;
		margin: $margin-both 0;
		text-align: center;
		position: relative;
		.close {
			position: absolute;
			right: 30rpx;
			top: 20rpx;
			height: 60rpx;
			width: 60rpx;
		}
	}
	.flex {
		display: flex;
		justify-content: space-between;
		margin: 0 $margin-both;
		padding: 30rpx 0;
		align-items: center;
		border-bottom: 1px solid $color-line;
		&.last_child {
			border-bottom: 0;
		}
		.flex_right {
			flex: 1;
			text-align: right;
		}
	}
	.action-btn {
		display: flex;
		justify-content: space-between;
		border-top: 1px solid $color-line;

		> view {
			flex: 1;
			text-align: center;
			padding: $padding;
		}
	}
}
</style>
