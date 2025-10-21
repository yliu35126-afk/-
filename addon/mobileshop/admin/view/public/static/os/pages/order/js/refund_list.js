export default {
	data() {
		return {
			showScreen: false,
			orderCondition: {
				refund_status_list: [],
				refund_type_list: []
			},
			orderConditionCurr: {
				refund_status_list: 0,
				refund_type_list: 0
			},
			formData: {
				sku_name: '',
				order_no: '',
				refund_no: '',
				start_time: '',
				end_time: '',
				refund_status: '',
				refund_type: ''
			},
			dataList: []
		};
	},
	onLoad() {
		this.getCondition();
	},
	onShow() {
		if (!this.$util.checkToken('/pages/order/refund/list')) return;

		if (this.mescroll) this.mescroll.resetUpScroll();
	},
	computed: {
		upperStatusList() {
			return this.orderCondition.refund_status_list;
		}
	},
	methods: {
		getCondition() {
			var res = this.$api.sendRequest({
				url: '/shopapi/orderrefund/condition',
				success: res => {
					var data = res.data;
					if (res.code == 0 && data) {
						for (var index in data) {
							var arr = [];
							if (index == 'refund_status_list') {
								for (var index_c in data[index]) {
									var obj = data[index][index_c];
									arr.push(obj);
								}
							} else {
								for (var index_c in data[index]) {
									var obj = {
										status: index_c,
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
		//开始时间
		bindTimeStartChange(e) {
			if (this.formData.end_time && e.detail.value >= this.formData.end_time) {
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
			this.searchType = 1;
			var data = this.formData;
			this.showScreen = false;
			this.$refs.mescroll.refresh();
			const query = uni.createSelectorQuery().in(this);
			query.select('#tab' + this.orderConditionCurr.refund_status_list).boundingClientRect(data => {}).exec();
		},
		//标签点击事件
		uTag(val, currType, formitem) {
			this.formData[formitem] = this.orderCondition[currType][val].status;
			this.orderConditionCurr[currType] = val;
		},
		//重置
		resetData() {
			this.formData.sku_name = '';
			this.formData.order_no = '';
			this.formData.refund_no = '';
			this.formData.start_time = '';
			this.formData.end_time = '';
			this.formData.refund_status = '';
			this.formData.refund_type = '';
			this.orderConditionCurr.refund_status_list = 0;
			this.orderConditionCurr.refund_type_list = 0;
			this.$forceUpdate();
		},
		tabChange(index) {
			this.orderConditionCurr.refund_status_list = index;
			this.formData.refund_status = this.orderCondition.refund_status_list[index].status;
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
			Object.assign(data, this.formData);
			if(data.refund_status === 0) data.refund_status = '';

			this.mescroll = mescroll;
			this.$api.sendRequest({
				url: '/shopapi/orderrefund/lists',
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
					if (mescroll.num == 1) this.dataList = []; //如果是第一页需手动制空列表
					newArr.forEach(v => {
						v.sku_spec_format = v.sku_spec_format ? JSON.parse(v.sku_spec_format) : [];
					});
					this.dataList = this.dataList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		imgError(index) {
			this.dataList[index].sku_image = this.$util.getDefaultImage().default_goods_img;
			this.$forceUpdate();
		},
		toDetail(order) {
			this.$util.redirectTo("/pages/order/refund/detail", {
				order_goods_id: order.order_goods_id
			});
		},
		goOrderList() {
			this.$util.redirectTo('/pages/order/list', {}, 'redirectTo');
		}
	}
}
