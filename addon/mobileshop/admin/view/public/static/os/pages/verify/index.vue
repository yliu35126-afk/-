<template>
	<view class="verify iphone-safe-area">
		<view class="scan">
			<view class="recode color-base-text" @click="$util.redirectTo('/pages/verify/records')">核销记录</view>
			<image :src="$util.img('/upload/uniapp/shop_uniapp/verify/verify.png')" mode="aspectFit" @click="scanCode()"></image>
			<view class="tip color-tip">点击此区域扫描核销码</view>
		</view>
		<view class="menu_item">
			<view class="menu_title">
				<text class="line color-base-bg margin-right"></text>
				<text>输入核销码</text>
			</view>
			<view class="menu_content">
				<input placeholder="请输入核销码" v-model="verify_code" class="uni-input" @confirm="search()" />
				<button type="primary" :disabled="verify_code == ''" @click="search">提交核销码</button>
			</view>
		</view>
		<view v-if="search_info">
			<view class="list">
				<view class="goods" v-for="(item_c, index) in item_array" :key="index">
					<image class="img" :src="$util.img(item_c.img)" mode="aspectFit" @error="imgError(index)"></image>
					<view class="info">
						<view class="goods_name">{{ item_c.name }}</view>
						<view class="flex">
							<view class="flex_left">x{{ item_c.num }}</view>
							<view class="flex_right">
								<text class="font-size-tag">￥</text>
								{{ item_c.price }}
							</view>
						</view>
					</view>
				</view>
				<view class="other_info" v-for="(item_r, index_r) in remark_array" :key="index_r">{{ item_r.title }}：{{ item_r.value }}</view>
			</view>
			<button type="primary" @click="verify">验证使用</button>
		</view>
	</view>
</template>

<script>
import Weixin from '@/common/js/wx-jssdk.js';
export default {
	data() {
		return {
			verify_code: '',
			search_info: '',
			item_array: [],
			remark_array: []
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/verify/index')) return;
	},
	methods: {
		search() {
			this.$api.sendRequest({
				url: '/shopapi/verify/verifyCard',
				data: {
					verify_code: this.verify_code
				},
				success: res => {
					if (res.code >= 0 && res.data) {
						this.search_info = res.data;
						this.item_array = res.data.data.item_array;
						this.remark_array = res.data.data.remark_array;
					} else {
						this.remark_array = [];
						this.item_array = [];
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		verify() {
			this.$api.sendRequest({
				url: '/shopapi/verify/verify',
				data: {
					verify_code: this.verify_code
				},
				success: res => {
					var that = this;
					this.$util.showToast({
						title: res.message
					});
					if (res.code >= 0) {
						setTimeout(() => {
							that.$util.redirectTo('/pages/verify/records');
						}, 1000);
					}
				}
			});
		},

		scanCode() {
			// #ifdef H5
			if (!this.$util.isWeiXin()) {
				this.$util.showToast({
					title: 'H5端不支持扫码核销'
				});
				return;
			}
			// #endif
			// #ifdef MP
			uni.scanCode({
				onlyFromCamera: true,
				success: res => {
					if (res.scanType == 'WX_CODE' && res.errMsg == 'scanCode:ok') {
						try {
							var code = this.getQueryVariable(res.path,'code');
							this.verify_code = code;
							this.search();
						} catch (e) {
							this.$util.showToast({
								title: e.message
							});
						}
					}else if(res.scanType == 'QR_CODE' && res.errMsg == 'scanCode:ok'){
						var code = this.getQueryVariable(res.result,'code');
						this.verify_code = code;
						this.search();
						
					} else {
						this.$util.showToast({
							title: res.errorMsg?res.errorMsg:'请扫描小程序二维码'
						});
					}
				}
			});
			// #endif
			// #ifdef H5
			if (this.$util.isWeiXin()) {
				if (uni.getSystemInfoSync().platform == 'ios') {
					var url = uni.getStorageSync('initUrl');
				} else {
					var url = location.href;
				}
				this.$api.sendRequest({
					url: '/wechat/api/wechat/jssdkconfig',
					data: {
						url: url
					},
					success: jssdkRes => {
						if (jssdkRes.code == 0) {
							var wxJS = new Weixin();
							wxJS.init(jssdkRes.data);
							wxJS.scanQRCode(res => {
								if (res.resultStr) {
									location.href = res.resultStr;
								}
							});
						} else {
							this.$util.showToast({
								title: jssdkRes.message
							});
						}
					}
				});
			}
			// #endif
		},
		getQueryVariable(url_val,name)
		{
	　　　　var url = url_val;
	　　　　var arrObj = url.split("?");
	　　　　if (arrObj.length > 1) {
	　　　　　　var arrPara = arrObj[1].split("&");
	　　　　　　var arr;
	　　　　　　for (var i = 0; i < arrPara.length; i++) {
	　　　　　　　　arr = arrPara[i].split("=");
	
	　　　　　　　　if (arr != null && arr[0] == name) {
	　　　　　　　　　　return arr[1];
	　　　　　　　　}
	　　　　　　}
	　　　　　　return "";
	　　　　}
	　　　　else {
	　　　　　　return "";
	　　　　}
		},

		imgError(index) {
			this.item_array[index].img = this.$util.getDefaultImage().default_goods_img;
			this.$forceUpdate();
		}
	}
};
</script>

<style lang="scss">
.verify {
	padding: 1px 0;
}
.scan {
	text-align: center;
	padding: 30rpx;
	margin: 30rpx $margin-both;
	background-color: #fff;
	border-radius: $border-radius;
	image {
		width: 300rpx;
		height: 300rpx;
	}
	.recode {
		text-align: right;
		margin-bottom: 30rpx;
	}
	.tip {
		margin-top: 20rpx;
	}
}
.menu_item {
	margin: 30rpx $margin-both 0;
	padding-top: 25rpx;
	padding-bottom: 30rpx;
	.menu_title {
		font-size: $font-size-toolbar;
		font-weight: bold;
		margin-bottom: 10rpx;
		.line {
			display: inline-block;
			height: 28rpx;
			width: 4rpx;
			border-radius: 4rpx;
		}
	}
	.menu_content {
		display: flex;
		align-items: center;
		margin-top: 20rpx;
		background-color: #fff;
		border-radius: $border-radius;
		.uni-input {
			flex: 1;
			padding: 0 30rpx;
		}
		button {
			border-radius: $border-radius;
			border-top-left-radius: 0;
			border-bottom-left-radius: 0;
			margin: 0;
		}
	}
}
.list {
	background-color: #fff;
	margin: 0 $margin-both $margin-both;
	padding: 0 30rpx 20rpx;
	border-radius: $border-radius;
	.title {
		display: flex;
		align-items: center;
		padding: 20rpx 0;
		font-size: $font-size-tag;
		border-bottom: 1px solid $color-line;
		.time {
			flex: 1;
			color: $color-tip;
		}
		.status {
			margin-left: 30rpx;
		}
	}
	.goods {
		display: flex;
		padding: 30rpx 0;
		.img {
			height: 140rpx;
			width: 140rpx;
			min-width: 140rpx;
		}
		.info {
			flex: 1;
			margin-left: 30rpx;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			.goods_name {
				overflow: hidden;
				text-overflow: ellipsis;
				display: -webkit-box; //作为弹性伸缩盒子模型显示。
				-webkit-box-orient: vertical; //设置伸缩盒子的子元素排列方式--从上到下垂直排列
				-webkit-line-clamp: 2; //
				line-height: 1.5;
			}

			.flex {
				margin-top: 10rpx;
				display: flex;
				justify-content: space-between;
				align-items: baseline;
				.flex_left {
					font-size: $font-size-tag;
					color: $color-tip;
				}
			}
		}
	}
	.other_info {
		color: $color-tip;
		font-size: $font-size-tag;
	}
}
</style>
