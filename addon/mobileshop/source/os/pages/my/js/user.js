import validate from '@/common/js/validate.js';
export default {
	data() {
		return {
			shopInfo: {},
			searchMemberName: '',
			dataList: [],
			mescroll: null,
			password: {
				newPwd: '',
				againNew: '',
				uid: 0
			},
			repeatFlag:false
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/my/user/user')) return;
		this.shopInfo = uni.getStorageSync('shop_info') ? JSON.parse(uni.getStorageSync('shop_info')) : {};
		if (this.$refs.mescroll) this.$refs.mescroll.refresh();
	},
	methods: {
		showHide(item='') {
			let stop = false;
			this.dataList.forEach(v => {
				if (v.is_off == 1) {
					stop = true;
				}
				v.is_off = 0;
			});

			if (!stop && item != '') item.is_off = 1;
		},
		getListData(mescroll) {
			this.mescroll = mescroll;
			this.$api.sendRequest({
				url: '/shopapi/user/user',
				data: {
					page: mescroll.num,
					page_size: mescroll.size,
					search_keys: this.searchMemberName
				},
				success: res => {
					let newArr = [];
					let msg = res.message;
					if (res.code == 0 && res.data) {
						newArr = res.data.list;
					} else {
						this.$util.showToast({
							title: msg
						});
					}
					mescroll.endSuccess(newArr.length);
					//设置列表数据
					if (mescroll.num == 1) this.dataList = []; //如果是第一页需手动制空列表
					newArr.forEach(v => {
						v.is_off = 0;
					});
					this.dataList = this.dataList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		searchMember() {
			this.mescroll.resetUpScroll();
		},
		linkSkip(item) {
			if (item) {
				item.is_off = 0;
				this.$util.redirectTo("/pages/my/user/user_edit", {
					'uid': item.uid
				});
			} else {
				this.$util.redirectTo("/pages/my/user/user_edit");
			}
		},
		deleteUser(item) {
			if (this.repeatFlag) return;
			this.repeatFlag = true;
			uni.showModal({
				title: '提示',
				content: '确定要删除该用户吗？',
				success: res => {
					if (res.confirm) {
						this.$api.sendRequest({
							url: '/shopapi/user/deleteUser',
							data: {
								uid: item.uid
							},
							success: res => {
								this.$util.showToast({
									title: res.message
								})
								if (res.code >= 0) {
									this.$refs.mescroll.refresh();
								}
								this.repeatFlag = false;
							}
						});
					}else{
						this.repeatFlag = false;
					}
				},
			})
		},
		changePass(item) {
			item.is_off = 0;
			this.password.uid = item.uid;
			this.$refs.editPasswordPop.open();
		},
		closeEditPasswordPop() {
			this.password.newPwd = '';
			this.password.againNew = '';
			this.password.uid = 0
			this.$refs.editPasswordPop.close();
		},
		modifyPassword() {
			if (this.repeatFlag) return;
			this.repeatFlag = true;
			if (this.vertify()) {
				this.$api.sendRequest({
					url: '/shopapi/user/modifyPassword',
					data: {
						uid: this.password.uid,
						password: this.password.newPwd
					},
					success: res => {
						this.$util.showToast({
							title: res.message
						})
						if (res.code == 0) {
							this.closeEditPasswordPop();
						}
						this.repeatFlag = false;
					}
				});
			}else{
				this.repeatFlag = false;
			}
		},
		//表单验证
		vertify() {
			let rule = [];
			rule = [{
				name: 'newPwd',
				checkType: 'required',
				errorMsg: '密码不能为空'
			}, ];

			var checkRes = validate.check(this.password, rule);
			if (checkRes) {
				if (this.password.newPwd != this.password.againNew) {
					this.$util.showToast({
						title: '两次密码不一致'
					});
					return false;
				}
				return true;
			} else {
				this.$util.showToast({
					title: validate.error
				});
				return false;
			}
		}
	}
};
