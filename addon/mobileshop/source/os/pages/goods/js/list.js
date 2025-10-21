export default {
	data() {
		return {
			statusList: [{
					id: 0,
					name: '销售中',
					goods_state: 1,
					verify_state: 1
				},
				{
					id: 1,
					name: '仓库中',
					goods_state: 0,
					verify_state: 1
				},
				{
					id: 2,
					name: '待审核',
					goods_state: '',
					verify_state: 0
				},
				{
					id: 3,
					name: '审核失败',
					goods_state: '',
					verify_state: -2
				},
				{
					id: 4,
					name: '违规下架',
					goods_state: '',
					verify_state: 10
				}
			],
			status: -1,
			dataList: [],
			searchGoodsName: '',
			showScreen: false,
			goodsCondition: [],
			goodsConditionCurr: {
				goods_promotion_type: 0,
				goods_class: 0
			},
			goodsClass: [{
					name: '全部',
					type: ''
				},
				{
					name: '实物商品',
					type: 1
				},
				{
					name: '虚拟商品',
					type: 2
				},
			],
			formData: {
				search_text: '',
				promotion_type: '',
				start_sale: '',
				end_sale: '',
				goods_class: ""
			}
		};
	},
	onShow() {
		let status = uni.getStorageSync('status');
		if (status) {
			this.status = status;
			uni.removeStorageSync("status");
		}
		if (!this.$util.checkToken('/pages/goods/list')) return;
		this.$store.dispatch('getShopInfo');
		if (this.mescroll) this.mescroll.resetUpScroll();
		this.getCondition();
	},
	methods: {
		showHide(val) {
			val.is_off = !val.is_off;
		},
		shwoOperation(item = '') {
			let stop = false;
			this.dataList.forEach(v => {
				if (v.is_off == 1) {
					stop = true;
				}
				v.is_off = 0;
			});
			if (!stop && item != '') item.is_off = 1;
		},
		tabChange(status = -1) {
			this.status = status;
			this.mescroll.resetUpScroll();
		},
		getCondition() {
			this.$api.sendRequest({
				url: '/shopapi/goods/condition',
				success: res => {
					let data = res.data;
					if (res.code == 0 && data) {
						for (let index in data) {
							let arr = [{
								name: "全部",
								type: ''
							}];
							for (let index_c in data[index]) {
								arr.push(data[index][index_c]);
							}
							data[index] = arr;
						}
						this.goodsCondition = data;
					}
				}
			});
		},
		getListData(mescroll) {
			let data = {
				page_size: mescroll.size,
				page: mescroll.num,
				search_text: this.searchGoodsName
			};

			if (this.status != -1 && this.statusList[this.status].verify_state !== '') data.verify_state = this.statusList[this.status]
				.verify_state;
			if (this.status != -1 && this.statusList[this.status].goods_state !== '') data.goods_state = this.statusList[this.status]
				.goods_state;
			data = Object.assign(data, this.formData);
			this.mescroll = mescroll;
			this.$api.sendRequest({
				url: '/shopapi/goods/lists',
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
						v.is_off = 0;
					});
					this.dataList = this.dataList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		deleteGoods(item) {
			uni.showModal({
				title: '删除',
				content: '删除后进入回收站，确定删除吗?',
				success: res => {
					item.is_off = 0;
					if (res.confirm) {
						this.$api.sendRequest({
							url: '/shopapi/goods/deleteGoods',
							data: {
								goods_ids: item.goods_id
							},
							success: res => {
								this.$util.showToast({
									title: res.message
								});
								if (res.code == 0) {
									this.mescroll.resetUpScroll();
								}
							}
						});
					}
				}
			});
		},
		offGoods(item) {
			item.is_off = 0;
			this.$api.sendRequest({
				url: '/shopapi/goods/offGoods',
				data: {
					goods_state: 0,
					goods_ids: item.goods_id
				},
				success: res => {
					this.$util.showToast({
						title: res.message
					});
					if (res.code == 0) {
						this.mescroll.resetUpScroll();
					}
				}
			});
		},
		onGoods(item) {
			item.is_off = 0;
			this.$api.sendRequest({
				url: '/shopapi/goods/onGoods',
				data: {
					goods_state: 1,
					goods_ids: item.goods_id
				},
				success: res => {
					this.$util.showToast({
						title: res.message
					});
					if (res.code == 0) {
						this.mescroll.resetUpScroll();
					}
				}
			});
		},
		copyGoods(item) {
			uni.showModal({
				title: '复制',
				content: '复制商品会存放在仓库中,确定复制吗',
				success: res => {
					if (res.confirm) {
						this.$api.sendRequest({
							url: '/shopapi/goods/copyGoods',
							data: {
								goods_id: item.goods_id
							},
							success: res => {
								if (res.code == 0) {
									this.mescroll.resetUpScroll();
									this.$util.showToast({
										title: '商品已放入仓库中'
									});
								} else {
									this.$util.showToast({
										title: res.message
									});
								}
							}
						});
					}
					item.is_off = 0;
				}
			});
		},
		getVerifyStateRemark(item) {
			this.$api.sendRequest({
				url: '/shopapi/goods/getVerifyStateRemark',
				data: {
					goods_id: item.goods_id
				},
				success: res => {
					if (res.code != 0 && !res.data) return false;
					let data = res.data.verify_state_remark ? res.data.verify_state_remark : '暂无违规信息';
					uni.showModal({
						title: '违规原因',
						content: data,
						showCancel: false,
						success: res => {
							item.is_off = 0;
						}
					});
					return false;
				}
			});
		},
		searchGoods() {
			this.mescroll.resetUpScroll();
		},
		linkSkip(item) {
			let data = {};
			if (item) {
				data.goods_id = item.goods_id;
				item.is_off = 0;
			}
			this.$util.redirectTo('/pages/goods/edit/index', data);
		},
		goOutput(item) {
			let data = {};
			if (item) {
				data.goods_id = item.goods_id;
				item.is_off = 0;
			}
			this.$util.redirectTo('/pages/goods/output', data);
		},
		uTag(val, currType, formitem) {
			if (currType == 'goods_class') {
				this.formData[formitem] = this.goodsClass[val].type;
			} else {
				this.formData[formitem] = this.goodsCondition[currType][val].type;
			}
			this.goodsConditionCurr[currType] = val;
		},
		//重置
		resetData() {
			this.formData.search_text = '';
			this.formData.promotion_type = '';
			this.formData.start_sale = '';
			this.formData.end_sale = '';
			this.formData.goods_class = '';
			this.goodsConditionCurr.goods_promotion_type = 0;
			this.goodsConditionCurr.goods_class = 0;
			this.$forceUpdate();
		},
		//数据提交
		screenData() {
			if (this.formData.start_sale && this.formData.end_sale && this.formData.start_sale > this.formData.end_sale) {
				this.$util.showToast({
					title: "最低销量不能大于最高销量"
				})
				return;
			}
			let data = this.formData;
			this.showScreen = false;
			this.$refs.mescroll.refresh();
		},
		imgError(index) {
			this.dataList[index].goods_image = this.$util.getDefaultImage().default_goods_img;
			this.$forceUpdate();
		},
	}
};
