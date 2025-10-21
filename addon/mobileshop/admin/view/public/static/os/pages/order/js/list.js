export default {
	data() {
		return {
			showScreen: false,
			orderCondition: {
				order_label_list: [],
				order_status_list: []
			},
			orderConditionCurr: {
				order_label_list: 0,
				order_type_list: 0,
				order_status_list: 0,
				promotion_type: 0,
				pay_type_list: 0,
				order_from_list: 0,
				settlement_list: 0
			},
			settlement_list: [{
					type: 0,
					name: '全部'
				},
				{
					type: 1,
					name: '已结算'
				},
				{
					type: 2,
					name: '未结算'
				}
			],
			formData: {
				order_label: '',
				search: '',
				start_time: '',
				end_time: '',
				order_type: '',
				order_status: '',
				promotion_type: '',
				pay_type: '',
				order_from: '',
				settlement_state: ''
			},
			orderList: [],
			order: {
				remark: '',
			}
		};
	},
	onLoad(option) {
		this.getCondition();
	},
	onShow() {
		let orderStatus = uni.getStorageSync('order_id');
		if (orderStatus !== '') {
			this.formData.order_status = orderStatus;
			this.orderConditionCurr.order_status_list = this.findCurr(orderStatus);
			uni.removeStorageSync("order_id");
		}
		if (!this.$util.checkToken('/pages/order/list')) return;
		this.$store.dispatch('getShopInfo');

		if (this.mescroll) this.mescroll.resetUpScroll();
		this.actionCallback = () => {
			this.mescroll.resetUpScroll();
		};
	},
	methods: {
		//查找当选选中的值
		findCurr(val) {
			var data = this.orderCondition.order_status_list,
				status = 0;
			for (var index in data) {
				if (data[index].type == val) {
					status = parseInt(index) + 1;
					break;
				}
			}
			return status;
		},
		getCondition() {
			this.$api.sendRequest({
				url: '/shopapi/order/condition',
				success: res => {
					var data = res.data;
					if (res.code == 0 && data) {
						for (var index in data) {
							var arr = [];
							if (index != 'order_label_list' && index != 'order_status_list' && index != 'pay_type_list') {
								for (var index_c in data[index]) {
									var obj = {
										type: index_c
									};
									obj = Object.assign(obj, data[index][index_c]);
									arr.push(obj);
								}
							} else {
								for (var index_c in data[index]) {
									var obj = {
										type: index_c,
										name: data[index][index_c]
									};
									arr.push(obj);
								}
							}
							data[index] = arr;
						}
						this.orderCondition = data;
					}
				}
			});
		},
		bindPickerChange(e) {
			this.orderConditionCurr.order_label_list = e.detail.value;
			this.formData.order_label = this.orderCondition.order_label_list[this.orderConditionCurr.order_label_list].type;
		},
		//开始时间
		bindTimeStartChange(e) {
			if (e.detail.value >= this.formData.end_time && this.formData.end_time) {
				this.$util.showToast({
					title: '开始时间不能大于结束时间'
				});
				return false;
			}
			this.formData.start_time = e.detail.value;
		},
		//结束时间
		bindTimeEndChange(e) {
			if (e.detail.value <= this.formData.start_time) {
				this.$util.showToast({
					title: '结束时间不能小于开始时间'
				});
				return false;
			}
			this.formData.end_time = e.detail.value;
		},
		//时间段选择
		dateSelect(e) {
			this.formData.start_time = this.getDay(e);
			this.formData.end_time = this.getNowDate();
		},
		getNowDate() {
			var date = new Date();
			var y = date.getFullYear();
			var m = date.getMonth() + 1; //获取当前月份的日期
			if (m < 10) {
				m = '0' + m;
			}
			var d = date.getDate();
			if (d < 10) {
				d = '0' + d;
			}
			return y + '-' + m + '-' + d;
		},
		getDay(p_count) {
			var dd = new Date();
			dd.setDate(dd.getDate() - p_count); //获取p_count天后的日期
			var y = dd.getFullYear();
			var m = dd.getMonth() + 1; //获取当前月份的日期
			if (m < 10) {
				m = '0' + m;
			}
			var d = dd.getDate();
			if (d < 10) {
				d = '0' + d;
			}
			return y + '-' + m + '-' + d;
		},
		//数据提交
		screenData() {
			var data = this.formData;
			this.showScreen = false;
			this.$refs.mescroll.refresh();
			const query = uni.createSelectorQuery().in(this);
			query.select('#tab' + this.orderConditionCurr.order_status_list).boundingClientRect(data => {}).exec();
		},
		//标签点击事件
		uTag(val, currType, formitem) {
			if (currType == 'order_type_list') {
				var temp = this.orderCondition.order_type_list[val].status;
				var arr = [];
				for (var index in temp) {
					arr.push({
						type: index,
						name: temp[index]
					});
				}
				this.orderCondition.order_status_list = arr;
				this.orderConditionCurr.order_status_list = 0;
				this.formData.order_status = '';
				this.formData[formitem] = this.orderCondition[currType][val].type;
			} else if (currType == 'settlement_list') {
				this.formData[formitem] = this.settlement_list[val].type;
			} else {
				if (val == 0) {
					this.formData[formitem] = '';
				} else {
					this.formData[formitem] = this.orderCondition[currType][val - 1].type;
				}
			}
			this.orderConditionCurr[currType] = val;

			if (currType == 'order_type_list') {
				this.$refs.mescroll.refresh();
			}
		},
		//重置
		resetData() {
			this.order_label = '';
			this.formData.search = '';
			this.formData.start_time = '';
			this.formData.end_time = '';
			this.formData.order_type = '';
			this.formData.order_status = '';
			this.formData.promotion_type = '';
			this.formData.pay_type = '';
			this.formData.order_from = '';
			this.formData.settlement_state = '';
			this.orderConditionCurr.order_label_list = 0;
			this.orderConditionCurr.order_type_list = 0;
			this.orderConditionCurr.order_status_list = 0;
			this.orderConditionCurr.promotion_type = 0;
			this.orderConditionCurr.pay_type_list = 0;
			this.orderConditionCurr.order_from_list = 0;
			this.orderConditionCurr.settlement_list = 0;
			this.$forceUpdate();
		},
		tabChange(index) {
			this.orderConditionCurr.order_status_list = index;
			if (index == 0) {
				this.formData.order_status = '';
			} else {
				this.formData.order_status = this.orderCondition.order_status_list[index - 1].type;
			}
			this.$refs.mescroll.refresh();
		},
		searchOrder() {
			this.mescroll.resetUpScroll();
		},
		getListData(mescroll) {
			var data = {
				page_size: mescroll.size,
				page: mescroll.num
			};
			if (!this.formData.order_label) {
				this.formData.order_label = 'order_no'
			}
			if (!this.formData.order_type) {
				this.formData.order_type = 'all'
			}
			Object.assign(data, this.formData);

			this.mescroll = mescroll;
			this.$api.sendRequest({
				url: '/shopapi/order/lists',
				data: data,
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
					if (mescroll.num == 1) this.orderList = []; //如果是第一页需手动制空列表
					newArr.forEach(v => {
						v.order_status_action = JSON.parse(v.order_status_action);
						v.order_goods.forEach(cv => {
							cv.sku_spec_format = cv.sku_spec_format ? JSON.parse(cv.sku_spec_format) : [];
						});
					});
					this.orderList = this.orderList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		imgError(index, goodsIndex) {
			this.orderList[index].order_goods[goodsIndex].sku_image = this.$util.getDefaultImage().default_goods_img;
			this.$forceUpdate();
		},
		// 卖家备注
		orderRemark(order) {
			this.order = JSON.parse(JSON.stringify(order));
			this.$refs.orderRemark.show(() => {
				this.mescroll.resetUpScroll();
			})
		},
		orderAction(action, order_id) {
			try {
				this[action](order_id);
			} catch (e) {
				console.log("orderAction error：", e);
			}
		},
		// 拨打电话
		makePhoneCall(mobile) {
			uni.makePhoneCall({
				phoneNumber: mobile
			});
		},
		toDetail(order) {
			var template = '';
			if (order.order_type == 1) {
				template = 'basis';
			} else if (order.order_type == 2) {
				template = 'store';
			} else if (order.order_type == 3) {
				template = 'local';
			} else if (order.order_type == 4) {
				template = 'virtual';
			}
			this.$util.redirectTo("/pages/order/detail/" + template, {
				order_id: order.order_id,
				template: template
			});
		},
		goRefundOrderList() {
			this.$util.redirectTo('/pages/order/refund/list');
		}
	}
}
