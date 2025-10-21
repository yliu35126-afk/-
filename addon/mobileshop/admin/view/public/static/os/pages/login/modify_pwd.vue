<template>
	<view>
		<view class="item-wrap">
			<view class="form-wrap">
				<view class="label">原密码</view>
				<input class="uni-input" placeholder="请输入原密码" v-model="formData.old_pass" maxlength="100" password="true" />
			</view>
			<view class="form-wrap">
				<view class="label">新密码</view>
				<input class="uni-input" placeholder="请输入新密码" v-model="formData.new_pass" maxlength="100" password="true" />
			</view>
			<view class="form-wrap">
				<view class="label">确认密码</view>
				<input class="uni-input" placeholder="请输入确认密码" v-model="formData.repeat_pass" maxlength="100" password="true" />
			</view>
		</view>
		<button type="primary" @click="save()">保存</button>
	</view>
</template>

<script>
import validate from '@/common/js/validate.js';
export default {
	data() {
		return {
			formData: {
				old_pass: '',
				new_pass: '',
				repeat_pass: ''
			}
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/my/index')) return;
	},
	methods: {
		save() {
			if (!this.vertify()) return;
			this.$api.sendRequest({
				url: '/shopapi/login/modifyPassword',
				data: {
					new_pass: this.formData.new_pass,
					old_pass: this.formData.old_pass
				},
				success: res => {
					this.$util.showToast({
						title: res.message
					});
					if (res.code == 0) {
						setTimeout(() => {
							uni.navigateBack({
								delta: 1
							});
						}, 1000);
					}
				}
			});
		},
		//表单验证
		vertify() {
			let rule = (rule = [
				{ name: 'old_pass', checkType: 'required', errorMsg: '请输入原密码' },
				{ name: 'new_pass', checkType: 'required', errorMsg: '请输入新密码' },
				{ name: 'repeat_pass', checkType: 'required', errorMsg: '请输入确认密码' }
			]);
			var checkRes = validate.check(this.formData, rule);
			if (checkRes) {
				if (this.formData.new_pass != this.formData.repeat_pass) {
					this.$util.showToast({
						title: '两次密码不一致'
					});
					return false;
				}
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
.item-wrap {
	background: #fff;
	margin-top: $margin-updown;
	.form-wrap {
		display: flex;
		align-items: center;
		margin: 0 $margin-both;
		border-bottom: 1px solid $color-line;
		min-height: 100rpx;
		line-height: 100rpx;
		&:last-child {
			border-bottom: none;
		}
		.label {
			vertical-align: middle;
			margin-right: $margin-both;
		}
		input {
			vertical-align: middle;
			display: inline-block;
			flex: 1;
			text-align: right;
		}
	}
}
button {
	margin-top: 40rpx;
}
</style>
