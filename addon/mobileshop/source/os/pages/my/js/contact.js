import validate from '@/common/js/validate.js';
import Config from '@/common/js/config.js';
export default {
	data() {
		return {
			shopInfo: {
				start_time:'',
				end_time:''
			},
			dateObj: {
				startDate: '',
				endDate: ''
			},
			work_weekArr: [],
			work_weekText: '',
			addressValue: '',
			option: {},
			week_list: [{
					name: "周一",
					value: 1,
					is_select: false
				},
				{
					name: "周二",
					value: 2,
					is_select: false
				},
				{
					name: "周三",
					value: 3,
					is_select: false
				},
				{
					name: "周四",
					value: 4,
					is_select: false
				},
				{
					name: "周五",
					value: 5,
					is_select: false
				},
				{
					name: "周六",
					value: 6,
					is_select: false
				},
				{
					name: "周日",
					value: 7,
					is_select: false
				}
			]
		};
	},
	onLoad(option) {
		if (option.name) this.option = option;
	},
	onShow() {
		if (!this.$util.checkToken('/pages/my/shop/contact')) return;
		this.shopInfo = uni.getStorageSync('addressInfo') ? JSON.parse(uni.getStorageSync('addressInfo')) : JSON.parse(uni.getStorageSync('shop_info'));
		var tempObj = this.shopInfo.work_week ? this.shopInfo.work_week : '';
		var tempArr = tempObj ? tempObj.split(',') : [];
		this.work_weekArr = tempArr;
		this.initSelect();
		//地址
		if (Object.values(this.option).length && this.option.name && this.shopInfo) {
			this.shopInfo.address = this.option.name;
			this.getAddress(this.option.latng);
			var tempLatng = this.option.latng.split(',');
			this.shopInfo.latitude = tempLatng[0];
			this.shopInfo.longitude = tempLatng[1];
		}
	},
	methods: {
		openWeek() {
			this.initSelectPoup();
			this.$refs.openWeek.open();
		},
		save() {
			if (!this.vertify()) return;
			if (!this.shopInfo.longitude || !this.shopInfo.longitude) {
				this.$util.showToast({
					title: "请选择地理位置"
				})
				return;
			}
			if (this.shopInfo.start_time > this.shopInfo.end_time) {
				this.$util.showToast({
					title: "结束时间不能小于开始时间"
				})
				return;
			}
			this.$api.sendRequest({
				url: '/shopapi/shop/contact',
				data: this.shopInfo,
				success: res => {
					this.$util.showToast({
						title: res.message
					})
					if (res.code >= 0) {
						uni.setStorageSync('shop_info', JSON.stringify(this.shopInfo));
						uni.removeStorageSync('addressInfo');
						setTimeout(() => {
							uni.navigateBack({
								delta: 1
							});
						}, 1000);
					}
				}
			});
		},
		selectedChange(val) {
			this.week_list[val - 1].is_select = !this.week_list[val - 1].is_select;
			var arr = [];
			this.week_list.forEach(function(item) {
				if (item.is_select) arr.push(item.value)
			})
			this.work_weekArr = arr;
		},
		selectedChangeBtn() {
			this.shopInfo.work_week = this.work_weekArr.toString();
			this.selectTextChange();
			this.$refs.openWeek.close();
		},
		selectTextChange() {
			var tempArr = [];
			this.week_list.forEach(function(item) {
				if (item.is_select) tempArr.push(item.name)
			})
			this.work_weekText = tempArr.join('，');
		},
		bindStartDateChange(e) {
			var date = new Date();
			var y = date.getFullYear();
			var m = date.getMonth() + 1;
			var d = date.getDate();
			var startTime = y + '-' + m + '-' + d + ' ' + e.detail.value
			var endTime = y + '-' + m + '-' + d + ' ' + this.timeHourMinute(
				this.shopInfo.end_time);
			if (startTime >= endTime && this.shopInfo.end_time) {
				this.$util.showToast({
					title: "开始时间不能大于结束时间"
				})
				return;
			}
			this.shopInfo.start_time = this.$util.timeTurnTimeStamp(startTime);
			console.log(this.shopInfo.start_time);
		},
		backStartDateChange(e) {
			this.dateObj.startDate = '';
			this.shopInfo.start_time = '';
		},
		bindEndDateChange(e) {
			var date = new Date();
			var y = date.getFullYear();
			var m = date.getMonth() + 1;
			var d = date.getDate();
			var startTime = y + '-' + m + '-' + d + ' ' + this.timeHourMinute(
				this.shopInfo.start_time);
			var endTime = y + '-' + m + '-' + d + ' ' + e.detail.value;
			if (startTime >= endTime) {
				this.$util.showToast({
					title: "结束时间不能小于开始时间"
				})
				return;
			}
			this.shopInfo.end_time = this.$util.timeTurnTimeStamp(endTime);
		},
		backEndDateChange() {
			this.dateObj.endDate = '';
			this.shopInfo.end_time = '';
		},
		initSelect() {
			for (var i in this.work_weekArr) {
				var val = this.work_weekArr[i];
				this.week_list[val - 1].is_select = true;
			}
			this.selectTextChange();
		},
		initSelectPoup() {
			var arr = this.work_weekText ? this.work_weekText.split('，') : [];
			for (var i in this.week_list) {
				var val = this.week_list[i].name;
				this.week_list[i].is_select = false;
				for (var j in arr) {
					if (arr[j] == val) this.week_list[i].is_select = true;
				}
			}
			this.selectTextChange();
		},
		//获取详细地址
		getAddress(value) {
			this.$api.sendRequest({
				url: '/api/memberaddress/tranAddressInfo',
				data: {
					latlng: value
				},
				success: res => {
					if (res.code == 0) {
						this.shopInfo.full_address = '';
						this.shopInfo.full_address += res.data.province != undefined ? res.data.province : '';
						this.shopInfo.full_address += res.data.city != undefined ? '-' + res.data.city : '';
						this.shopInfo.full_address += res.data.district != undefined ? '-' + res.data.district : '';
						this.shopInfo.province = res.data.province_id;
						this.shopInfo.province_name = res.data.province;
						this.shopInfo.city = res.data.city_id;
						this.shopInfo.city_name = res.data.city;
						this.shopInfo.district = res.data.district_id;
						this.shopInfo.district_name = res.data.district;
					} else {
						this.$util.showToast({
							title: '数据有误'
						});
					}
				}
			});
		},
		selectAddress() {
			var urlencode = this.shopInfo;
			uni.setStorageSync('addressInfo', JSON.stringify(urlencode));
			// #ifdef MP
			uni.chooseLocation({
				success: res => {
					this.option.name = res.name;
					this.option.latng = res.latitude + ',' + res.longitude;
				},
				fail(res) {
					uni.getSetting({
						success: function(res) {
							var statu = res.authSetting;
							if (!statu['scope.userLocation']) {
								uni.showModal({
									title: '是否授权当前位置',
									content: '需要获取您的地理位置，请确认授权，否则地图功能将无法使用',
									success(tip) {
										if (tip.confirm) {
											uni.openSetting({
												success: function(data) {
													if (data.authSetting['scope.userLocation'] === true) {
														this.$util.showToast({
															title: '授权成功'
														});
														//授权成功之后，再调用chooseLocation选择地方
														setTimeout(function() {
															uni.chooseLocation({
																success: data => {
																	this.option.name = res.name;
																	this.option.latng = res.latitude + ',' + res.longitude;
																}
															});
														}, 1000);
													}
												}
											});
										} else {
											this.$util.showToast({
												title: '授权失败'
											});
										}
									}
								});
							}
						}
					});
				}
			});
			// #endif
			// #ifdef H5
			
			let backurl = Config.h5Domain + '/pages/my/shop/contact';
			window.location.href = 'https://apis.map.qq.com/tools/locpicker?search=1&type=0&backurl=' + encodeURIComponent(
				backurl) + '&key=' + Config.mpKey + '&referer=myapp';
			// #endif
		},
		//时间转换
		timeHourMinute(timeStamp) {
			if (timeStamp != undefined && timeStamp != "" && timeStamp > 0) {
				var date = new Date();
				date.setTime(timeStamp * 1000);
				var h = date.getHours();
				h = h < 10 ? ('0' + h) : h;
				var minute = date.getMinutes();
				minute = minute < 10 ? ('0' + minute) : minute;
				return h + ':' + minute;
			} else {
				return "";
			}
		},
		//表单验证
		vertify() {
			let rule = [];
			rule = [{
					name: 'name',
					checkType: 'required',
					errorMsg: '联系人姓名不能为空'
				},
				{
					name: 'mobile',
					checkType: 'required',
					errorMsg: '手机号不能为空'
				},
				{
					name: 'mobile',
					checkType: 'phoneno',
					errorMsg: '请输入正确的手机号'
				}
			];
			var checkRes = validate.check(this.shopInfo, rule);
			if (checkRes) {
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
