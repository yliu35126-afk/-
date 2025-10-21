export default {
	data() {
		return {
			procedureState: '',
			canvas: true,
			cWidth: '',
			cHeight: '',
			arr: [],
			transaction_statistics: 'stat_day',
			transaction_time: 7,
			order_transaction_time: 7,
			data: {
				shop_info: {
					group_name: '',
					site_name: '',
					category_name: ''
				},
				stat_day: {
					order_total: '0.00',
					order_count: 0,
					order_pay_count: 0,
					collect_shop: 0,
					visit_count: 0
				},
				stat_yesterday: {
					order_total: '0.00',
					order_count: 0,
					order_pay_count: 0,
					collect_shop: 0,
					visit_count: 0
				},
				num_data: {
					waitsend: 0,
					waitsend: 0,
					refund: 0,
					goods_stock_alarm: 0,
					audit_refuse_count: 0
				},
				notice_list: []
			},
			order_info: {},
			order_total: {
				categories: [],
				series: []
			},
			order_pay_count: {
				categories: [],
				series: []
			},
			total_money: {
				order_pay_count: 0,
				order_total: 0
			},
			refCurr: '',

			pickerCurr: {
				order_total: 0,
				order_pay_count: 0
			},
			picker: [{
					date_value: 7,
					date_text: "7天"
				},
				{
					date_value: 15,
					date_text: "15天"
				},
				{
					date_value: 30,
					date_text: "30天"
				}
			]
		};
	},
	async onShow() {
		if (!this.$util.checkToken('/pages/index/index')) return;
		await this.initData();
	},
	async onPullDownRefresh() {
		await this.initData();
	},
	methods: {
		async initData() {
			await this.getData();
			this.getOrderInfo();
			this.$store.dispatch('getShopInfo');
		},
		//公告详情
		toNoticeDetail(val) {
			this.$util.redirectTo('/pages/notice/detail', {
				notice_id: val
			})
		},
		//获取首页信息
		async getData() {
			var res = await this.$api.sendRequest({
				url: '/shopapi/index/index',
				async: false
			});
			if (res.code >= 0 && res.data) {
				this.data = res.data;
				//续签标识
				var renewObj = {};
				renewObj.is_reopen = res.data.is_reopen;
				renewObj.cert_id = res.data.shop_info.cert_id;
				uni.setStorage({
					key: 'renewObj',
					data: JSON.stringify(renewObj)
				});

			} else {
				this.$util.showToast({
					title: res.message
				});
			}
			uni.stopPullDownRefresh();
			if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
		},
		//去认证
		toCert() {
			if (this.data.shop_info.cert_id) this.$util.redirectTo('/pages/cret/index');
		},
		//获取订单信息
		getOrderInfo() {
			var time = this.refCurr == 'order_total' ? this.transaction_time : this.order_transaction_time;
			this.$api.sendRequest({
				url: '/shopapi/statistics/orderstatistics',
				data: {
					day: time
				},
				success: res => {
					if (res.code >= 0 && res.data) {
						this.order_info = res.data;
						var timeTemp = [],
							timeTempYear = [];
						var time = this.refCurr == 'order_total' ? this.transaction_time : this.order_transaction_time;
						for (var index = (time - 1); index >= 0; index--) {
							timeTemp.push(this.getDay(-index));
							timeTempYear.push(this.getDay(-index, 1));
						}
						this.order_total.categories = this.order_pay_count.categories = timeTemp;
						this.order_total.series = [{
							name: '销售额',
							data: res.data.order_total,
							color: '#FF6A00',
							time: timeTempYear
						}];
						this.order_pay_count.series = [{
							name: '订单数',
							data: res.data.order_pay_count,
							color: '#FF6A00',
							time: timeTempYear
						}]
						if (this.$refs[this.refCurr] && this.refCurr) {
							this.$refs[this.refCurr][0].changeData(this.refCurr, this[this.refCurr]);
							this.total_money[this.refCurr] = this.sum(res.data[this.refCurr], this.refCurr == 'order_total' ? 1 : '');
						} else {
							this.total_money.order_total = this.sum(res.data.order_total, 1);
							this.total_money.order_pay_count = this.sum(res.data.order_pay_count);
							this.initChart();
						}
					}
				}
			});
		},
		//计算和
		sum(arr, toFixed = '') {
			var s = 0;
			arr.forEach(function(val, idx, arr) {
				s += parseFloat(val);
			}, 0);

			return s.toFixed(toFixed ? 2 : '');
		},
		//日期处理
		getDay(p_count, type = '') {
			var dd = new Date();
			dd.setDate(dd.getDate() + p_count); //获取p_count天后的日期
			var y = dd.getFullYear();
			var m = dd.getMonth() + 1; //获取当前月份的日期
			if (m < 10) {
				m = '0' + m;
			}
			var d = dd.getDate();
			if (d < 10) {
				d = '0' + d;
			}
			if (type) {
				return y + "-" + m + "-" + d;
			} else {
				return m + "-" + d;
			}
		},
		//时间段切换
		dayChange(val, id) {
			this.refCurr = id;
			if (id == 'order_total') {
				this.transaction_time = val;
			} else {
				this.order_transaction_time = val;
			}
			this.getOrderInfo();
		},
		transactionChange(val) {
			this.transaction_statistics = val;
		},
		//图表
		initChart() {
			this.cWidth = uni.upx2px(660);
			this.cHeight = uni.upx2px(400);
			this.getServerData();
		},
		//图表数据的处理
		getServerData() {
			var Data = {
				yAxisdisabled: true,
				xAxisgridColor: 'transparent',
				yAxisgridType: 'dash',
				yAxisgridColor: '#eeeeee',
				yAxisdashLength: 5,
				animation: true,
				enableScroll: true,
				scrollPosition: 'right',
				scrollColor: 'transparent',
				scrollBackgroundColor: 'transparent',
				extra: {
					area: {
						addLine: true,
						opacity: 0.5,
						width: 2,
						type: 'curve'
					}
				},
				legend: false,
			}
			this.order_total = Object.assign(this.order_total, Data, {
				'unit': "元"
			});
			this.order_pay_count = Object.assign(this.order_pay_count, Data, {
				unit: "笔"
			});
			var serverData = [{
					title: '销售额',
					opts: this.order_total,
					chartType: "area",
					extraType: "curve",
					id: "order_total",
				},
				{
					title: '订单数',
					opts: this.order_pay_count,
					chartType: "area",
					extraType: "curve",
					id: "order_pay_count",
				}
			];
			this.arr = serverData;
		},
		getProcedureState() {
			//处理续签
			if (this.data.shop_info.cert_id != 0) {
				if (this.data.is_reopen == 1) this.$util.redirectTo('/pages/apply/shopset');
				else this.$util.redirectTo('/pages/renew/apply');
				return;
			}

			//处理立即认证
			this.$api.sendRequest({
				url: '/shopapi/apply/index',
				success: res => {
					var data = res.data;
					if (res.code == 0 && data) {
						if (res.data.procedure == 1) {
							this.$util.redirectTo('/pages/apply/shopset', {}, 'reLaunch');
						} else {
							this.$util.redirectTo('/pages/apply/audit', {}, 'reLaunch');
						}
					}
				}
			});
		},
		pendingLink(url, key, val) {
			this.$util.redirectTo(url);
			uni.setStorage({
				key: key,
				data: val
			});
		},
		imgError() {
			this.data.shop_info.logo = this.$util.getDefaultImage().default_headimg;
		},
		pickerChange(val, e) {
			this.pickerCurr[val] = e.detail.value;
			this.dayChange(this.picker[this.pickerCurr[val]].date_value, val)
		}
	},
	onShareAppMessage(res) {
		var title = '多商户手机版商家端';
		var path = '/pages/index/index';
		return {
			title: title,
			path: path,
			success: res => {},
			fail: res => {}
		};
	},
};
