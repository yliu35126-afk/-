export default {
	data() {
		return {
			showScreen: false,
			isShow: false,
			formData: {
				verifier_name: "",
				start_time: '',
				end_time: '',
				verify_code: '',
				verify_type: ''
			},
			recordsList: [],
			pickerCurr: 0,
			picker: [{
					date_text: '全部',
					date_value: ''
				},
				{
					date_text: '订单自提',
					date_value: 'pickup'
				},
				{
					date_text: '虚拟商品',
					date_value: 'virtualgoods'
				}
			],
			verifyType: [],
			verify_type: 0
		};
	},
	onLoad() {
		this.getverifyType();
	},
	onShow() {
		if (!this.$util.checkToken('/pages/verify/records')) return;
		if (this.$refs.mescroll) {
			this.$refs.mescroll.refresh();
		}
	},
	methods: {
		search() {
			this.$refs.mescroll.refresh();
		},
		pickerChange(e) {
			this.pickerCurr = e.detail.value;
			this.formData.verify_type = e.detail.value;
			this.$refs.mescroll.refresh();
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
		getListData(mescroll) {
			var data = {
				page_size: mescroll.size,
				page: mescroll.num
			};
			Object.assign(data, this.formData);
			this.$api.sendRequest({
				url: '/shopapi/verify/records',
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
					if (mescroll.num == 1) this.recordsList = []; //如果是第一页需手动制空列表
					this.recordsList = this.recordsList.concat(newArr); //追加新数据

					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					this.isShow = true;
				}
			});
		},
		//核销类型
		getverifyType() {
			this.$api.sendRequest({
				url: '/shopapi/verify/verifyType',
				success: res => {
					var arr = [{
						date_value: '',
						date_name: '全部'
					}];
					for (var index in res.data) {
						arr.push({
							date_value: index,
							date_name: res.data[index].name
						})
					}
					this.verifyType = arr;
				}
			});
		},
		//标签点击事件
		uTag(val) {
			this.verify_type = val;
			this.formData.verify_type = this.verifyType[this.verify_type].date_value;
		},
		//数据提交
		screenData() {
			var data = this.formData;
			this.showScreen = false;
			this.$refs.mescroll.refresh();
		},
		//数据重置
		resetData() {
			this.formData.verifier_name = '';
			this.formData.start_time = '';
			this.formData.end_time = '';
			this.formData.verify_code = '';
			this.formData.verify_type = '';
			this.verify_type = 0;
		},
		imgError(index, index_c) {
			this.recordsList[index].item_array[index_c].img = this.$util.getDefaultImage().default_goods_img;
			this.$forceUpdate();
		}
	}
};
