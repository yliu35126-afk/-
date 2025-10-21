<template>
	<view>
		<view class="item-wrap">
			<view class="form-wrap">
				<text class="label">用户名</text>
				<input class="uni-input" :placeholder="uid > 0 ? user_info.username : '请输入用户名'" v-model="user_info.username" maxlength="100" :disabled="uid > 0" />
			</view>
			<block v-if="uid <= 0">
				<view class="form-wrap">
					<text class="label">密码</text>
					<input class="uni-input" placeholder="请输入密码" v-model="password" password="true" maxlength="100" />
				</view>
				<view class="form-wrap">
					<text class="label">再次输入密码</text>
					<input class="uni-input" placeholder="请再次输入密码" v-model="againPassword" password="true" maxlength="100" />
				</view>
			</block>
			<view class="form-wrap more-wrap">
				<text class="label">用户组</text>
				<picker :value="pickerCurr" @change="pickerChange" :range="groupList" range-key="group_name" class="picker">
					<text class="uni-input" :class="{ 'color-tip': !user_info.group_id }">{{ user_info.group_id ? groupList[pickerCurr].group_name : '请选择用户组' }}</text>
					<text class="iconfont iconright"></text>
				</picker>
			</view>
			<view class="form-wrap" v-if="uid > 0">
				<text class="label">用户状态</text>
				<view class="picker"><switch :checked="user_info.status == 1" @change="switchChange" style="transform: scale(0.7);" /></view>
			</view>
		</view>
		<button type="primary" @click="save()">保存</button>
		<loading-cover ref="loadingCover" v-if="uid > 0"></loading-cover>
	</view>
</template>

<script>
import validate from '@/common/js/validate.js';
export default {
	data() {
		return {
			uid: 0,
			user_info: {
				username: '',
				status: 1,
				group_id: ''
			},
			password: '',
			againPassword: '',
			groupList: [
				{
					group_name: ''
				}
			],
			pickerCurr: 0,
			repeatFlag: false
		};
	},
	async onLoad(option) {
		this.uid = option.uid || 0;
		await this.getgroupList();
		if (this.uid) this.getDetails();
	},
	onShow() {
		if (!this.$util.checkToken('/pages/my/user/user_edit?uid=' + this.uid)) return;
	},
	methods: {
		getDetails() {
			this.$api.sendRequest({
				url: '/shopapi/user/info',
				data: { uid: this.uid },
				success: res => {
					let data = res.data;
					if (res.code == 0 && data) {
						this.user_info.username = res.data.username;
						this.user_info.status = res.data.status;
						this.user_info.group_id = res.data.group_id;
						this.findCurrValue(this.groupList);
					} else {
						this.$util.redirectTo('/pages/my/user/user');
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		async getgroupList() {
			var res = await this.$api.sendRequest({
				url: '/shopapi/user/groupList',
				async: false
			});
			if (res.code == 0) {
				this.groupList = res.data;
			}
		},
		//查找当前选中值
		findCurrValue(val) {
			for (var index in val) {
				if (val[index].group_id == this.user_info.group_id) {
					this.pickerCurr = index;
					break;
				}
			}
		},
		pickerChange(e) {
			if (this.groupList.length == 0) return;
			this.pickerCurr = e.target.value;
			this.user_info.group_id = this.groupList[this.pickerCurr].group_id;
		},
		switchChange(e) {
			this.user_info.status = e.target.value ? 1 : 0;
		},
		save() {
			var data = this.user_info;
			if (!this.uid) {
				var url = '/shopapi/user/addUser';
				data.password = this.password;
				data.group_id = this.groupList[this.pickerCurr].group_id;
				if (!this.vertify()) return;
			} else {
				var url = '/shopapi/user/editUser';
				data.uid = this.uid;
			}

			if (this.repeatFlag) return;
			this.repeatFlag = true;

			this.$api.sendRequest({
				url: url,
				data: data,
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
					} else {
						this.repeatFlag = false;
					}
				}
			});
		},
		//表单验证
		vertify() {
			let rule = [
				{ name: 'username', checkType: 'required', errorMsg: '用户名不能为空' },
				{ name: 'password', checkType: 'required', errorMsg: '请输入密码' },
				{ name: 'againPassword', checkType: 'required', errorMsg: '请再次输入密码' }
			];
			var data = {
				username: this.user_info.username,
				password: this.password,
				againPassword: this.againPassword
			};
			var checkRes = validate.check(data, rule);
			if (checkRes) {
				if (this.password != this.againPassword) {
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
@import '../css/edit.scss';
button {
	margin-top: 40rpx;
}
</style>
