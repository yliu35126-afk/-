export default {
	data() {
		return {
			cWidth: 0,
			cHeight: 0,
			canvas: true,
			arr: [],
			tableArea: {
				categories: [],
				series: []
			},
			order_total: {
				categories: [],
				series: []
			},
			order_pay_count: {
				categories: [],
				series: []
			},
			goods_pay_count: {
				categories: [],
				series: []
			},
			refCurr: '',
			transaction_time: 7,
			picker: {
				'gaikuang': [{
						date_type: 0,
						date_text: '今日实时',
					},
					{
						date_type: 1,
						date_text: '近7天',
					},
					{
						date_type: 2,
						date_text: '近30天'
					}
				],
				'table': [{
						date_type: 1,
						date_text: '近7天',
						day: 7
					},
					{
						date_type: 2,
						date_text: '近30天',
						day: 30
					}
				]
			},
			pickerCurr: {
				gaikuang: 0,
				table: 0,
				order_total: 0,
				order_pay_count: 0,
				goods_pay_count: 0

			},
			total_money: {
				gaikuang: 0,
				table: 0,
				order_total: 0,
				order_pay_count: 0,
				goods_pay_count: 0

			},
			order_info: {},
			shop_info: {}
		}
	},
	onShow() {
		if (!this.$util.checkToken('/pages/statistics/transaction')) return;
		this.getChartsInfo();
		this.getTotalData();
		this.getTotalData(1);
	},
	methods: {
		selectCharts(val) {
			this.refCurr = val;
		},
		bindPickerChange(id, e) {
			this.pickerCurr[id] = e.target.value;
			this.transaction_time = this.picker.table[e.target.value].day;
			this.getChartsInfo();
			this.getTotalData(this.picker.table[e.target.value].date_type);
		},
		pickerChangeShop(e) {
			this.pickerCurr.gaikuang = e.target.value;
			this.getTotalData();
		},
		//图表
		init() {
			this.cWidth = uni.upx2px(630);
			this.cHeight = uni.upx2px(400);
			this.getServerData();
		},
		//获取交易概况时实信息
		getTotalData(val) {
			var data_type = val ? val : this.picker.gaikuang[this.pickerCurr.gaikuang].date_type;
			this.$api.sendRequest({
				url: '/shopapi/statistics/order',
				data: {
					date_type: data_type
				},
				success: res => {
					if (res.code >= 0) {
						if (val) {
							if (this.refCurr) {
								this.total_money[this.refCurr] = res.data[this.refCurr];
							} else {
								this.total_money.order_total = res.data.order_total;
								this.total_money.order_pay_count = res.data.order_pay_count;
								this.total_money.goods_pay_count = res.data.goods_pay_count;
							}
						} else {
							this.shop_info = res.data;
						}
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		
		//获取统计的图表信息
		getChartsInfo() {
			var date_type = this.refCurr ? this.picker.table[this.pickerCurr[this.refCurr]].date_type : this.picker.table[this.pickerCurr[
				'order_pay_count']].date_type;
			this.$api.sendRequest({
				url: '/shopapi/statistics/getOrderStatList',
				data: {
					date_type: date_type
				},
				success: res => {
					if (res.code >= 0) {
						this.order_info = res.data;
						var timeTemp = [];
						for (var index = (this.transaction_time - 1); index >= 0; index--) {
							timeTemp.push(this.getDay(-index))
						}
						this.order_total.categories = this.order_pay_count.categories = this.goods_pay_count.categories = timeTemp;
						this.order_total.series = [{
							name: '订单金额',
							data: res.data.order_total,
							color: '#FF6A00',
							time: res.data.time
						}];
						this.order_pay_count.series = [{
							name: '订单数',
							data: res.data.order_pay_count,
							color: '#FF6A00',
							time: res.data.time
						}];
						this.goods_pay_count.series = [{
							name: '订单商品数',
							data: res.data.goods_pay_count,
							color: '#FF6A00',
							time: res.data.time
						}];
						if (this.$refs[this.refCurr] && this.refCurr) {
							this.$refs[this.refCurr][0].changeData(this.refCurr, this[this.refCurr]);
						} else {

							this.init();
						}
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		getDay(p_count) {
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
			return m + "-" + d;
		},
		getServerData() {
			var Data = {
				enableScroll: false,
				yAxisdisabled: true,
				xAxisgridColor: 'transparent',
				yAxisgridType: 'dash',
				yAxisgridColor: '#eeeeee',
				animation: true,
				enableScroll: true,
				scrollColor: 'transparent',
				scrollPosition: 'right',
				scrollBackgroundColor: 'transparent',
				extra: {
					area: {
						addLine: true,
						opacity: 0.5,
						width: 2,
						color: '#FF6A00',
						type: 'curve'
					},
				},
				legend: false
			}
			this.order_total = Object.assign(this.order_total, Data, {
				unit: "元"
			});
			this.order_pay_count = Object.assign(this.order_pay_count, Data, {
				unit: "笔"
			});
			this.goods_pay_count = Object.assign(this.goods_pay_count, Data, {
				unit: "件"
			});
			var serverData = [{
					title: '订单金额',
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

				},
				{
					title: '订单商品数',
					opts: this.goods_pay_count,
					chartType: "area",
					extraType: "curve",
					id: "goods_pay_count",

				}
			];
			this.arr = serverData;
		}
	}
}
