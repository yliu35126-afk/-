<template>
	<view class="login">
		<view class="login-title">商家登录</view>
		<view class="login-input">
			<view class="iconfont icon06_huiyuanguanli color-base-text"></view>
			<input class="uni-input" placeholder="请输入用户名" v-model="loginList.username" />
		</view>
		<view class="login-input">
			<view class="iconfont iconmima color-base-text"></view>
			<input class="uni-input" placeholder="请输入密码" v-model="loginList.password" password="true" />
		</view>
		<view class="login-input" v-if="captchaConfig == 1">
			<view class="iconfont iconyanzhengma1 color-base-text"></view>
			<input class="uni-input" placeholder="请输入验证码" type="number" v-model="loginList.vcode" @confirm="login()" />
			<image class="code" :src="captcha.img" mode="aspectFit" @click="getImg"></image>
		</view>
		<button type="primary" @click="login()">登录</button>
		<view class="login-text">
			<text>还没成为我们的伙伴？</text>
			<text class="color-base-text" @click="toRegistered">申请入驻</text>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import validate from '@/common/js/validate.js';
export default {
	data() {
		return {
			loginList: {
				username: '',
				password: '',
				vcode: ''
			},
			captchaConfig: 1,
			captcha: {
				id: '',
				img: ''
			},
			back: '', // 返回页
			redirect: 'redirectTo' // 跳转方式
		};
	},
	onLoad(option) {
		//防止开店过程中非法退出
		if (uni.getStorageSync('token')) uni.removeStorageSync('token');
		if (uni.getStorageSync('site_id')) uni.removeStorageSync('site_id');

		this.back = option.back || '';
		this.getCaptchaConfig();
	},
	methods: {
		toRegistered() {
			if (this.back) this.$util.redirectTo('/pages/apply/register', { back: this.back });
			else this.$util.redirectTo('/pages/apply/register');
		},
		//点击刷新验证码
		getImg() {
			this.getCodeImg();
		},
		//获取验证码配置
		getCaptchaConfig() {
			this.$api.sendRequest({
				url: '/shopapi/config/captchaConfig',
				data: {},
				success: res => {
					if (res.code >= 0 && res.data) {
						this.captchaConfig = res.data.shop_login;
						if (this.captchaConfig == 1) this.getCodeImg();
						else if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				}
			});
		},
		//获取验证码
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
		//点击登录
		login() {
			if (this.vertify()) {
				this.$api.sendRequest({
					url: '/shopapi/login/login',
					data: {
						captcha_id: this.captcha.id,
						username: this.loginList.username,
						password: this.loginList.password,
						captcha_code: this.loginList.vcode
					},
					success: res => {
						if (res.code >= 0) {
							this.$util.showToast({
								title: '登录成功'
							});
							uni.setStorageSync('token', res.data.token);
							uni.setStorageSync('site_id', res.data.site_id);
							if (res.data.site_id > 0) {
								if (this.back != '') {
									this.$util.redirectTo(decodeURIComponent(this.back), {}, this.redirect);
								} else {
									this.$util.redirectTo('/pages/index/index', {}, this.redirect);
								}
							} else {
								this.getShopStatus();
							}
						} else {
							this.getImg();
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			}
		},
		//获取店铺状态
		getShopStatus() {
			this.$api.sendRequest({
				url: '/shopapi/apply/index',
				success: res => {
					var data = res.data;
					if (res.code == 0 && data) {
						if (res.data.procedure == 1) {
							this.$util.redirectTo('/pages/apply/mode', {}, 'reLaunch');
						} else {
							this.$util.redirectTo('/pages/apply/audit', {}, 'reLaunch');
						}
					}
				}
			});
		},
		//表单验证
		vertify() {
			let rule = [];
			// 账号验证
			rule = [{ name: 'username', checkType: 'required', errorMsg: '请输入用户名' }, { name: 'password', checkType: 'required', errorMsg: '请输入密码' }];
			if (this.captchaConfig == 1 && this.captcha.id != '') rule.push({ name: 'vcode', checkType: 'required', errorMsg: '请输入验证码' });
			var checkRes = validate.check(this.loginList, rule);
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
		margin-bottom: 50rpx;
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
		margin: 180rpx auto 0;
	}
	.login-text {
		text-align: center;
		margin: 50rpx auto 0;
		color: $color-tip;
	}
}
</style>
