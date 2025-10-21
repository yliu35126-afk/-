<template>
	<view class="login">
		<view class="login-title">商家账号申请</view>
		<view class="login-input">
			<view class="iconfont icon06_huiyuanguanli color-base-text"></view>
			<input class="uni-input" placeholder="请输入商家账号" v-model="registeredList.account" />
		</view>
		<view class="login-input">
			<view class="iconfont iconmima color-base-text"></view>
			<input class="uni-input" placeholder="请输入密码" v-model="registeredList.password1" password="true" />
		</view>
		<view class="login-input">
			<view class="iconfont iconmima color-base-text"></view>
			<input class="uni-input" placeholder="请输入确认密码" v-model="registeredList.password2" password="true" />
		</view>
		<view class="login-input" v-if="captchaConfig == 1">
			<view class="iconfont iconyanzhengma1 color-base-text"></view>
			<input class="uni-input" placeholder="请输入验证码" v-model="registeredList.vcode" type="number" @confirm="toRegistered()" />
			<image class="code" :src="captcha.img" mode="aspectFit" @click="getCodeImg()"></image>
		</view>
		<button type="primary" @click="toRegistered()">申请</button>
		<view class="login-text">
			<text>已有账号？</text>
			<text class="color-base-text" @click="toLogin">立即登录</text>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import validate from '@/common/js/validate.js';
export default {
	data() {
		return {
			registeredList: {
				account: '',
				password1: '',
				password2: '',
				vcode: ''
			},
			captchaConfig: 1,
			captcha: {
				id: '',
				img: ''
			},
			isRepeat: false,
			back: ''
		};
	},
	onLoad(option) {
		this.back = option.back;
		this.getCaptchaConfig();
	},
	methods: {
		toLogin() {
			if (this.back) this.$util.redirectTo('/pages/login/login', { back: this.back });
			else this.$util.redirectTo('/pages/login/login');
		},
		//获取验证码配置
		getCaptchaConfig() {
			this.$api.sendRequest({
				url: '/shopapi/config/captchaConfig',
				success: res => {
					if (res.code >= 0 && res.data) {
						this.captchaConfig = res.data.shop_login;
						if (this.captchaConfig == 1) this.getCodeImg();
						else if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				}
			});
		},
		getCodeImg() {
			this.$api.sendRequest({
				url: '/shopapi/captcha/captcha',
				data: {
					captcha_id: this.captcha.id
				},
				success: res => {
					if (res.code >= 0 && res.data) {
						this.captcha = res.data;
						this.captcha.img = this.captcha.img.replace(/\r\n/g, '');
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		toRegistered() {
			if (this.vertify() && !this.isRepeat) {
				this.isRepeat = true;
				this.$api.sendRequest({
					url: '/shopapi/register/register',
					data: {
						captcha_id: this.captcha.id,
						username: this.registeredList.account,
						password: this.registeredList.password1,
						captcha_code: this.registeredList.vcode
					},
					success: res => {
						this.isRepeat = false;

						uni.removeStorageSync('shopPackage');
						uni.removeStorageSync('openInfo');
						uni.removeStorageSync('bankInfo');
						uni.removeStorageSync('renewObj');

						if (res.code == 0) {
							if (res.data.site_id) {
								this.$util.showToast({
									title: '账号已存在'
								});
								this.getCodeImg();
							} else {
								uni.setStorageSync('token', res.data.token);
								uni.setStorageSync('site_id', res.data.site_id);
								this.$util.redirectTo('/pages/apply/mode',{},'reLaunch');
							}
						} else {
							this.getCodeImg();
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			}
		},
		vertify() {
			let rule = [
				{
					name: 'account',
					checkType: 'required',
					errorMsg: '请输入商家账号'
				},
				{
					name: 'password1',
					checkType: 'required',
					errorMsg: '请输入密码'
				},
				{
					name: 'password2',
					checkType: 'required',
					errorMsg: '请输入确认密码'
				}
			];
			if (this.captchaConfig == 1 && this.captcha.id != '') rule.push({ name: 'vcode', checkType: 'required', errorMsg: '请输入验证码' });
			let checkRes = validate.check(this.registeredList, rule);
			if (checkRes) {
				return true;
			} else {
				this.$util.showToast({ title: validate.error });
				return false;
			}
		}
	}
};
</script>

<style lang="scss">
page {
	background-color: #fff;
}
.login {
	margin: 0 60rpx 0;
	padding-top: 180rpx;
	.login-title {
		font-size: 60rpx;
		font-weight: 500;
		display: inline-block;
		margin-bottom: 30rpx;
	}
	.login-input {
		display: flex;
		height: 70rpx;
		margin: 50rpx auto 0;
		align-items: center;
		justify-content: center;
		border-bottom: 1px solid $color-line;
		border-radius: 10rpx;
		padding: 6rpx 10rpx;
		.iconfont {
			font-size: 40rpx;
		}
		.uni-input {
			flex: 1;
			margin-left: $margin-updown;
		}
		.code {
			width: 150rpx;
			height: 70rpx;
		}
	}
	button {
		margin: 150rpx auto 0;
	}
	.login-text {
		display: flex;
		justify-content: center;
		align-items: center;
		margin-top: 50rpx;
		color: $color-tip;
		line-height: 1;
	}
}
</style>
