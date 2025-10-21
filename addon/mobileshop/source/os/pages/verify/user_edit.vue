<template>
	<view class="user iphone-safe-area">
		<view class="item-wrap">
			<view class="form-wrap">
				<text class="label">核销人员姓名</text>
				<input class="uni-input" placeholder="请输入核销人员姓名" v-model="formData.verifier_name" maxlength="100" />
			</view>
		</view>
		<view class="item-wrap">
			<view class="form-wrap">
				<text class="label">关联前台会员</text>
				<input class="uni-input" placeholder="请输入会员名或手机" maxlength="100" v-model="verifyUSerInfo.member_account" @confirm="searchAccount()" />
				<text class="margin-left iconfont iconsousuo color-base-text" @click="searchAccount()"></text>
			</view>
			<view class="userlist" v-if="searchUSerInfo">
				<image
					class="image"
					:src="searchUSerInfo.headimg ? $util.img(searchUSerInfo.headimg) : $util.img($util.getDefaultImage().default_headimg)"
					mode="aspectFit"
					@error="imgError()"
				></image>
				<view class="base_info margin-left">
					<view>用户名：{{ searchUSerInfo.username }}</view>
					<view>手机号：{{ searchUSerInfo.mobile }}</view>
				</view>
				<text class="iconfont iconclose" @click="clearSearch()"></text>
			</view>
			<view class="tip">关联会员后才能在手机上使用核销台功能，否则无法在手机上核销</view>
		</view>
		<button type="primary" @click="save()">保存</button>
		<loading-cover ref="loadingCover" v-if="formData.verifier_id"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			verifyUSerInfo: {
				verifier_name: ''
			},
			searchUSerInfo: '',
			formData: {
				verifier_name: '',
				search_text: '',
				member_id: 0,
				verifier_id: 0
			}
		};
	},
	onLoad(option) {
		if (option.verifier_id) {
			this.formData.verifier_id = option.verifier_id;
			this.getUserDetail();
			uni.setNavigationBarTitle({
				title: '编辑核销人员'
			});
		} else {
			uni.setNavigationBarTitle({
				title: '添加核销人员'
			});
		}
	},
	onShow() {
		var url = this.formData.verifier_id ? '/pages/verify/user_edit?verifier_id=' + this.formData.verifier_id : '/pages/verify/user_edit';
		if (!this.$util.checkToken(url)) return;
	},
	computed: {},
	methods: {
		clearSearch() {
			this.searchUSerInfo = '';
		},
		searchAccount() {
			if (!this.verifyUSerInfo.member_account) {
				this.searchUSerInfo = '';
				this.formData.member_id = 0;
				return false;
			}
			this.$api.sendRequest({
				url: '/shopapi/verify/searchMember',
				data: { search_text: this.verifyUSerInfo.member_account },
				success: res => {
					if (res.code >= 0 && res.data) {
						this.searchUSerInfo = res.data;
						this.formData.search_text = this.verifyUSerInfo.member_account;
						this.formData.member_id = res.data.member_id;
					} else {
						this.searchUSerInfo = '';
						this.formData.member_id = 0;
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		getUserDetail() {
			this.$api.sendRequest({
				url: '/shopapi/verify/verifyUSerInfo',
				data: { verifier_id: this.formData.verifier_id },
				success: res => {
					if (res.code >= 0 && res.data) {
						this.verifyUSerInfo = res.data;
						this.formData.verifier_name = res.data.verifier_name;
						this.formData.member_id = res.data.member_id;
						this.searchAccount();
					}
				}
			});
		},
		save() {
			var url = this.formData.verifier_id ? '/shopapi/verify/editUser' : '/shopapi/verify/addUser';
			if (!this.formData.verifier_name) {
				this.$util.showToast({
					title: '核销人员姓名不能为空'
				});
				return false;
			}
			if (!this.formData.member_id) {
				this.$util.showToast({
					title: '关联前台会员不能为空'
				});
				return false;
			}
			this.$api.sendRequest({
				url: url,
				data: this.formData,
				success: res => {
					this.$util.showToast({
						title: res.message
					});
					setTimeout(() => {
						uni.navigateBack({
							delta: 1
						});
					}, 1000);
				}
			});
		},
		imgError() {
			this.searchUSerInfo.headimg = this.$util.getDefaultImage().default_headimg;
			this.$forceUpdate();
		}
	}
};
</script>

<style lang="scss">
.user {
	padding-top: 1px;
}
.item-wrap {
	background: #fff;
	margin-top: $margin-updown;
	margin-left: $margin-both;
	margin-right: $margin-both;
	border-radius: $border-radius;

	padding-bottom: 1px;
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
		.required {
			font-weight: bold;
		}
		.label {
			align-self: baseline;
			min-width: 150rpx;
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
.tip {
	color: $color-tip;
	font-size: $font-size-tag;
	padding: 20rpx 30rpx 20rpx;
}
.userlist {
	display: flex;
	align-items: center;
	margin: 30rpx;
	padding: 20rpx 30rpx;
	border: 1px solid $color-line;
	border-radius: $border-radius;
	.image {
		width: 80rpx;
		height: 80rpx;
	}
	.base_info {
		flex: 1;
	}
	.iconfont {
		align-self: flex-start;

		color: $color-tip;
	}
}
button {
	margin-top: 40rpx;
}
</style>
