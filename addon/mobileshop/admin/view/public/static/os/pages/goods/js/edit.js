export default {
	data() {
		return {
			repeatFlag: false,
			isIphoneX: false,
			goodsImgHeight: 165, // 商品图片高度
			isAWait: false,
			albumPage: 'goodsEdit',

			// 商品品牌
			brandList: [],
			brandPicker: {
				index: 0,
				arr: [],
			},

			//店内分类
			shopCategoryNumber: 1,
			shopCategoryData: {
				store_0: {}
			},
			shopCategoryIndex: '',
			shopCategoryList: [],

			categoryList: [],
			secondCategory: [],
			thirdCategory: [],
			categoryId: [0, 0, 0],
			categoryName: ['', '', ''],
			currentLevel: 1,
			lastLevel: 1,
			showFisrt: true,
			showSecond: false,
			showThird: false,

			goodsData: {
				goods_id: 0,
				sku_id: 0,
				goods_class: 1, // 商品类型:1 实物商品 ，2 虚拟商品
				goods_name: '',
				introduction: '',
				category_id: 0,
				category_name: '',
				goods_image: [],
				keywords: '',
				brand_id: 0,
				brand_name: '',
				virtual_indate: '', // 有效期/天【虚拟商品用】
				goods_spec_format: [], // 商品规格
				goods_sku_data: [], // 规格列表
				spec_type_status: 0,
				goods_shop_category_ids: '',

				// 单规格数据
				price: '',
				market_price: '',
				cost_price: '',
				weight: '',
				volume: '',
				sku_no: '',

				goods_stock: '',
				goods_stock_alarm: '',
				goods_content: '',

				// 快递运费
				is_free_shipping: 1, // 是否免邮
				shipping_template: 0, // 指定运费模板id
				template_name: '', // 运费模板名称

				max_buy: '',
				min_buy: '',
				unit: '',
				goods_state: 1,

				// 商品参数
				goods_attr_class: 0,
				goods_attr_name: '',
				goods_attr_format: []
			},

		};
	},
	async onLoad(option) {
		this.goodsData.goods_id = option.goods_id || 0;

		if (!this.$util.checkToken('/pages/goods/edit/index?goods_id=' + this.goodsData.goods_id)) return;

		this.clearStoreage();

		this.getBrandList();
		this.getCategoryTree();
		this.getStoreTree();
		if (this.goodsData.goods_id) {
			this.isAWait = true;
			uni.setNavigationBarTitle({
				title: '编辑商品'
			});
			await this.editGetGoodsInfo();
		} else {
			this.isAWait = false;
			uni.setNavigationBarTitle({
				title: '添加商品'
			});
		}
	},
	onShow() {
		this.isIphoneX = this.$util.uniappIsIPhoneX();
		this.refreshData();
	},
	methods: {
		// 获取编辑商品数据
		async editGetGoodsInfo() {
			var res = await this.$api.sendRequest({
				url: '/shopapi/goods/editGetGoodsInfo',
				data: {
					goods_id: this.goodsData.goods_id
				},
				async: false
			});
			if (res.code == 0 && res.data) {
				var data = res.data;
				//店内分类
				var shopCategoryIds = data.goods_shop_category_ids;
				if (shopCategoryIds) {
					var shopCategoryIdsArr = shopCategoryIds.split(",");
					for (var i = 0; i < shopCategoryIdsArr.length; i++) {
						outer: for (var key in this.shopCategoryList) {
							if (this.shopCategoryList[key].category_id == shopCategoryIdsArr[i]) {
								this.shopCategoryData['store_' + i] = {};
								this.shopCategoryData['store_' + i].category_id = this.shopCategoryList[key].category_id;
								this.shopCategoryData['store_' + i].category_name = this.shopCategoryList[key].category_name;
								this.shopCategoryData['store_' + i].level = this.shopCategoryList[key].level;
								break;
							} else if (this.shopCategoryList[key].child_list) {
								var childData = this.shopCategoryList[key].child_list;
								for (var childKey in childData) {
									if (childData[childKey].category_id == shopCategoryIdsArr[i]) {
										this.shopCategoryData['store_' + i] = {};
										this.shopCategoryData['store_' + i].category_id = childData[childKey].category_id;
										this.shopCategoryData['store_' + i].category_name = childData[childKey].category_name;
										this.shopCategoryData['store_' + i].level = childData[childKey].level;
										break outer;
									}
								}
							}
						}
					}
					this.shopCategoryNumber = shopCategoryIdsArr.length;
				}

				data.category_id = data.goods_category[0].id;
				data.category_name = data.goods_category[0].category_name.replace(/\//g, " / ");

				if (typeof data.category_id == 'string') {
					this.categoryId = data.category_id.split(",");
					this.categoryName = data.category_name.split(" / ");
				} else {
					this.categoryId = data.category_id;
					this.categoryName = data.category_name;
				}

				delete data.category_json;
				delete data.goods_category;

				data.goods_image = data.goods_image.split(",");

				data.goods_sku_data.forEach((item) => {
					if (item.sku_spec_format) item.sku_spec_format = JSON.parse(item.sku_spec_format);
				});

				if (data.goods_spec_format) {
					uni.setStorageSync("editGoodsSpecFormat", data.goods_spec_format);
					uni.setStorageSync("editGoodsSkuData", JSON.stringify(data.goods_sku_data));
					data.goods_spec_format = JSON.parse(data.goods_spec_format);
				} else {
					data.sku_id = data.goods_sku_data[0].sku_id;
					data.price = data.goods_sku_data[0].price;
					data.market_price = data.goods_sku_data[0].market_price;
					data.cost_price = data.goods_sku_data[0].cost_price;
					data.weight = data.goods_sku_data[0].weight;
					data.volume = data.goods_sku_data[0].volume;
					data.sku_no = data.goods_sku_data[0].sku_no;
				}

				if (data.goods_class == 1) {
					// 实物商品
					delete data.virtual_indate;
					uni.setStorageSync("editGoodsShippingTemplateId", data.shipping_template);
					uni.setStorageSync("editGoodsShippingTemplateName", data.template_name ? data.template_name : '');
				} else {
					// 虚拟商品
					delete data.shipping_template;
					delete data.is_free_shipping;
				}

				// 商品参数
				if (data.goods_attr_format) {
					uni.setStorageSync("editGoodsAttrClass", data.goods_attr_class);
					uni.setStorageSync("editGoodsAttrName", data.goods_attr_name);
					uni.setStorageSync("editGoodsAttrFormat", data.goods_attr_format);
					data.goods_attr_format = JSON.parse(data.goods_attr_format);
				}

				uni.setStorageSync("editGoodsState", data.goods_state);
				uni.setStorageSync("editGoodsContent", data.goods_content);

				this.goodsData = data;

				this.$forceUpdate();
			} else {
				this.$util.showToast({
					title: '商品不存在',
				});
				setTimeout(() => {
					this.$util.redirectTo('/pages/goods/list', {}, 'redirectTo');
				}, 1000);
			}
		},
		// 选择商品分类
		openGoodsCategoryPop() {
			this.$refs.categoryPopup.open();
		},
		closeGoodsCategoryPop() {
			this.$refs.categoryPopup.close();
		},
		// 编辑规格类型
		openGoodsSpec() {
			this.$util.redirectTo('/pages/goods/edit/spec');
		},
		// 编辑多规格
		openGoodsSpecEdit() {
			this.$util.redirectTo('/pages/goods/edit/spec_edit', {
				goods_class: this.goodsData.goods_class
			});
		},
		// 编辑商品状态
		openGoodsState() {
			this.$util.redirectTo('/pages/goods/edit/state', {
				goods_state: this.goodsData.goods_state
			});
		},
		// 编辑快递运费
		openExpressFreight() {
			this.$util.redirectTo('/pages/goods/edit/express_freight', {
				template_id: this.goodsData.shipping_template
			});
		},
		// 编辑商品详情
		openGoodsContent() {
			this.$util.redirectTo('/pages/goods/edit/content');
		},
		// 编辑商品参数
		openAttr() {
			this.$util.redirectTo('/pages/goods/edit/attr');
		},
		/**
		 * 刷新商品图片高度
		 * @param {Object} data
		 */
		refreshGoodsImgHeight(data) {
			if (data.height == '') return;
			var height = parseFloat(data.height.replace('px', ''));
			this.goodsImgHeight = height + 80;
			this.$forceUpdate();
			if (data.isLoad && this.$refs.loadingCover) {
				// 数据渲染留点时间
				setTimeout(() => {
					this.$refs.loadingCover.hide();
				}, 100);
			}
		},
		// 获取商品品牌列表
		getBrandList() {
			this.$api.sendRequest({
				url: '/shopapi/goodsbrand/getBrandList',
				success: res => {
					this.brandList = res.data;
					this.brandList.forEach((item, key) => {
						this.brandPicker.arr.push(item.brand_name);
						if (this.goodsData.brand_id == item.brand_id) {
							this.brandPicker.index = key;
						}
					});
					if (this.goodsData.brand_id) this.goodsData.brand_name = this.brandList[this.brandPicker.index].brand_name;
				}
			});
		},
		// 获取店内你分类树状结构
		getStoreTree() {
			this.$api.sendRequest({
				url: '/shopapi/goods/getShopCategoryTree',
				success: res => {
					if (res.data) {
						this.shopCategoryList = res.data;
					}
				}
			});
		},
		// 获取商品分类树状结构
		getCategoryTree() {
			this.$api.sendRequest({
				url: '/shopapi/goods/getCategoryTree',
				success: res => {
					if (res.data) {
						this.categoryList = res.data;
						this.categoryList.forEach((item, index) => {
							item.selected = this.categoryId.indexOf(item.category_id.toString()) != -1;
							if (item.selected) {
								this.secondCategory = item.child_list;
								this.currentLevel = 1;
							}
							if (item.child_list) {
								if (item.selected) this.lastLevel = 2;
								item.child_list.forEach(secondItem => {
									secondItem.selected = this.categoryId.indexOf(secondItem.category_id.toString()) != -1;
									if (secondItem.selected) {
										this.thirdCategory = secondItem.child_list;
										this.currentLevel = 2;
									}
									if (secondItem.child_list) {
										if (secondItem.selected) this.lastLevel = 3;
										secondItem.child_list.forEach(thirdItem => {
											thirdItem.selected = this.categoryId.indexOf(thirdItem.category_id.toString()) != -1;
											if (thirdItem.selected) this.currentLevel = 3;
										});
									}
								});
							}
						});
						this.changeShow(this.lastLevel);
						if (this.goodsData.goods_id == 0 && this.$refs.loadingCover) {
							if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						}
					}
				}
			});
		},
		changeShow(index) {
			if (index == 1) {
				this.showFisrt = true;
				this.showSecond = false;
				this.showThird = false;
			} else if (index == 2) {
				this.showFisrt = false;
				this.showSecond = true;
				this.showThird = false;
			} else if (index == 3) {
				this.showFisrt = false;
				this.showSecond = false;
				this.showThird = true;
			}
			this.currentLevel = index;
			this.$forceUpdate();
		},
		selectCategory(category) {
			this.currentLevel = category.level;

			// 如果当前选中跟上次不一样,则 要清空下级数据
			if (category.level == 1 && this.categoryId[0] > 0 && this.categoryId[0] != category.captcha_id) {
				this.categoryId[1] = 0;
				this.categoryName[1] = '';
				this.categoryId[2] = 0;
				this.categoryName[2] = '';
			} else if (category.level == 2 && this.categoryId[1] > 0 && this.categoryId[1] != category.captcha_id) {
				this.categoryId[2] = 0;
				this.categoryName[2] = '';
			}

			this.categoryId[category.level - 1] = category.category_id;
			this.categoryName[category.level - 1] = category.category_name;
			if (category.level == 1) {
				if (category.child_list) {
					this.secondCategory = category.child_list;
				} else {
					this.categoryId[1] = 0;
					this.categoryName[1] = '';
					this.categoryId[2] = 0;
					this.categoryName[2] = '';
				}
			} else if (category.level == 2) {
				if (category.child_list) {
					this.thirdCategory = category.child_list;
				} else {
					this.categoryId[2] = 0;
					this.categoryName[2] = '';
				}
			}

			this.lastLevel = 1;
			this.categoryList.forEach((item, index) => {
				item.selected = this.categoryId[0] == item.category_id;
				if (item.child_list) {
					if (item.selected) this.lastLevel = 2;
					item.child_list.forEach((secondItem, secondIndex) => {
						secondItem.selected = this.categoryId[1] == secondItem.category_id;
						if (secondItem.child_list) {
							if (secondItem.selected) this.lastLevel = 3;
						}
					});
				}
			});

			this.changeShow(this.lastLevel);

			this.goodsData.category_id = [];
			this.goodsData.category_name = [];

			for (var i = 0; i < this.categoryId.length; i++) {
				if (this.categoryId[i]) this.goodsData.category_id.push(this.categoryId[i]);
			}
			for (var i = 0; i < this.categoryName.length; i++) {
				if (this.categoryName[i]) this.goodsData.category_name.push(this.categoryName[i]);
			}

			this.goodsData.category_id = this.goodsData.category_id.toString();
			this.goodsData.category_name = this.goodsData.category_name.join(" / ");

			if (
				(this.lastLevel == 3 && this.categoryId[2]) ||
				(this.lastLevel == 2 && this.categoryId[1]) ||
				(this.lastLevel == 1 && this.categoryId[0])
			) {
				this.closeGoodsCategoryPop();
			}

			this.$forceUpdate();
		},
		// 绑定商品品牌切换事件
		bindBrandPickerChange(e) {
			if (this.brandList.length == 0) return;
			this.brandPicker.index = e.target.value;
			this.goodsData.brand_id = this.brandList[this.brandPicker.index].brand_id;
			this.goodsData.brand_name = this.brandList[this.brandPicker.index].brand_name;
		},
		addShopCategory() {
			this.shopCategoryData['store_' + this.shopCategoryNumber] = {};
			++this.shopCategoryNumber;
		},
		deleteShopCategory(index) {
			delete this.shopCategoryData['store_' + index];
			--this.shopCategoryNumber;

			//重置数据	
			let i = 0;
			let obj = {};
			for (let key in this.shopCategoryData) {
				obj['store_' + i] = this.shopCategoryData[key];
				i++;
			}
			this.shopCategoryData = {};
			this.shopCategoryData = Object.assign(this.shopCategoryData, obj);
		},
		openShopCategoryPopup(index) {
			this.shopCategoryIndex = index;
			this.$refs.storePopup.open();
		},
		selectShopCategory(data) {
			for (let key in this.shopCategoryData) {
				if (this.shopCategoryData[key].level == data.level && this.shopCategoryData[key].category_id == data.category_id) {
					this.$util.showToast({
						title: "请勿重复选择"
					});
					return false;
				}
			}

			this.shopCategoryData['store_' + this.shopCategoryIndex].level = data.level;
			this.shopCategoryData['store_' + this.shopCategoryIndex].category_name = data.category_name;
			this.shopCategoryData['store_' + this.shopCategoryIndex].category_id = data.category_id;
			this.$forceUpdate();
			this.closeShopCategoryPopup();
		},
		closeShopCategoryPopup() {
			this.$refs.storePopup.close();
		},
		// 刷新数据
		refreshData() {

			var selectedAlbumImg = uni.getStorageSync('selectedAlbumImg');
			if (selectedAlbumImg) {
				uni.setStorageSync('selectedAlbumImgTemp', selectedAlbumImg);
				selectedAlbumImg = JSON.parse(selectedAlbumImg);
				this.goodsData.goods_image = selectedAlbumImg.list.split(",");
				this.$refs.goodsShmilyDragImg.refresh(() => {
					uni.removeStorageSync("selectedAlbumImg");
				});
			}

			// 规格项
			this.goodsData.goods_spec_format = uni.getStorageSync('editGoodsSpecFormat') ? JSON.parse(uni.getStorageSync(
				'editGoodsSpecFormat')) : [];

			// 多规格数据
			this.goodsData.goods_sku_data = uni.getStorageSync('editGoodsSkuData') ? JSON.parse(uni.getStorageSync(
				'editGoodsSkuData')) : [];
			if (this.goodsData.goods_sku_data.length > 0) {
				this.goodsData.goods_stock = 0;
				this.goodsData.goods_stock_alarm = 0;
				this.goodsData.goods_sku_data.forEach((item) => {
					if (item.stock) this.goodsData.goods_stock += parseInt(item.stock);
					if (item.stock_alarm) this.goodsData.goods_stock_alarm += parseInt(item.stock_alarm);
				});
			}

			// 快递运费
			this.goodsData.shipping_template = uni.getStorageSync('editGoodsShippingTemplateId') || 0;
			this.goodsData.is_free_shipping = this.goodsData.shipping_template > 0 ? 0 : 1;
			this.goodsData.template_name = uni.getStorageSync('editGoodsShippingTemplateName') || '';

			if (uni.getStorageSync('editGoodsState') !== undefined && uni.getStorageSync('editGoodsState') !== '') {
				this.goodsData.goods_state = uni.getStorageSync('editGoodsState');
			}

			if (uni.getStorageSync('editGoodsContent') != undefined && uni.getStorageSync('editGoodsContent') != '') {
				this.goodsData.goods_content = uni.getStorageSync('editGoodsContent');
			}

			// 商品参数
			this.goodsData.goods_attr_class = uni.getStorageSync('editGoodsAttrClass') || 0;
			this.goodsData.goods_attr_name = uni.getStorageSync('editGoodsAttrName') || '';
			this.goodsData.goods_attr_format = uni.getStorageSync('editGoodsAttrFormat') ? JSON.parse(uni.getStorageSync(
				'editGoodsAttrFormat')) : [];
			this.$forceUpdate();
		},
		// 验证
		verify() {

			if (this.goodsData.goods_name.length == 0) {
				this.$util.showToast({
					title: '请输入商品名称'
				});
				return false;
			}
			if (this.goodsData.goods_name.length > 100) {
				this.$util.showToast({
					title: '商品名称不能超过100个字符'
				});
				return false;
			}

			if (this.goodsData.introduction.length > 100) {
				this.$util.showToast({
					title: '促销语不能超过100个字符'
				});
				return false;
			}

			if (this.categoryId[0] == 0) {
				this.$util.showToast({
					title: `请选择一级商品分类`
				});
				return false;
			} else if (this.lastLevel == 2 && this.categoryId[1] == 0) {
				this.$util.showToast({
					title: `请选择二级商品分类`
				});
				return false;
			} else if (this.lastLevel == 3 && this.categoryId[2] == 0) {
				this.$util.showToast({
					title: `请选择三级商品分类`
				});
				return false;
			}

			if (this.goodsData.goods_image.length == 0) {
				this.$util.showToast({
					title: '请上传商品图片'
				});
				return false;
			}

			if (this.goodsData.goods_class == 2) {
				if (this.goodsData.virtual_indate.length == 0) {
					this.$util.showToast({
						title: '请输入有效期'
					});
					return false;
				}
				if (isNaN(this.goodsData.virtual_indate) || !this.$util.data().regExp.number.test(this.goodsData.virtual_indate)) {
					this.$util.showToast({
						title: '[有效期]格式输入错误'
					});
					return false;
				}
			}

			// 单规格
			if (this.goodsData.goods_spec_format.length == 0) {
				if (this.goodsData.price.length == 0) {
					this.$util.showToast({
						title: '请输入销售价'
					});
					return false;
				}
				if (isNaN(this.goodsData.price) || !this.$util.data().regExp.digit.test(this.goodsData.price)) {
					this.$util.showToast({
						title: '[销售价]格式输入错误'
					});
					return false;
				}

				if (this.goodsData.market_price.length > 0 && (isNaN(this.goodsData.market_price) || !this.$util.data().regExp.digit
						.test(this.goodsData
							.market_price))) {
					this.$util.showToast({
						title: '[划线价]格式输入错误'
					});
					return false;
				}

				if (this.goodsData.cost_price.length > 0 && (isNaN(this.goodsData.cost_price) || !this.$util.data().regExp.digit.test(
						this.goodsData
						.cost_price))) {
					this.$util.showToast({
						title: '[成本价]格式输入错误'
					});
					return false;
				}

				if (this.goodsData.goods_class == 1 && this.goodsData.weight.length > 0 && (isNaN(this.goodsData.weight) || !this.$util
						.data().regExp
						.digit
						.test(this.goodsData
							.weight))) {
					this.$util.showToast({
						title: '[重量(kg)]格式输入错误'
					});
					return false;
				}

				if (this.goodsData.goods_class == 1 && this.goodsData.volume.length > 0 && (isNaN(this.goodsData.volume) || !this.$util
						.data().regExp
						.digit
						.test(this.goodsData
							.volume))) {
					this.$util.showToast({
						title: '[体积(m³)]格式输入错误'
					});
					return false;
				}

			} else {
				// 多规格
				if (this.goodsData.goods_sku_data.length == 0) {
					this.$util.showToast({
						title: '请编辑规格信息'
					});
					return false;
				}

				var flag = false;
				for (var i = 0; i < this.goodsData.goods_sku_data.length; i++) {
					if (this.goodsData.goods_sku_data[i].price == '') {
						flag = true;
						break;
					}
				}
				if (flag) {
					this.$util.showToast({
						title: '请编辑规格信息'
					});
					return false;
				}
			}

			// 总库存
			if (this.goodsData.goods_stock.length == 0) {
				this.$util.showToast({
					title: '请输入库存'
				});
				return false;
			}

			if (isNaN(this.goodsData.goods_stock) || !this.$util.data().regExp.number.test(this.goodsData.goods_stock)) {
				this.$util.showToast({
					title: '[库存]格式输入错误'
				});
				return false;
			}

			if (this.goodsData.goods_stock_alarm.length > 0) {
				if (isNaN(this.goodsData.goods_stock_alarm) || !this.$util.data().regExp.number.test(this.goodsData.goods_stock_alarm)) {
					this.$util.showToast({
						title: '[库存预警]格式输入错误'
					});
					return false;
				}
				if (parseInt(this.goodsData.goods_stock_alarm) != 0 || parseInt(this.goodsData.goods_stock_alarm) == parseInt(this.goodsData
						.goods_stock)) {
					this.$util.showToast({
						title: '[库存预警]不能等于库存数量'
					});
					return false;
				}
				if (parseInt(this.goodsData.goods_stock_alarm) > parseInt(this.goodsData.goods_stock)) {
					this.$util.showToast({
						title: '[库存预警]不能超过库存数量'
					});
					return false;
				}
			}

			if (this.goodsData.goods_class == 1 && this.goodsData.is_free_shipping == 0 && this.goodsData.shipping_template ==
				'') {
				this.$util.showToast({
					title: '请选择运费模板'
				});
				return false;
			}

			if (this.goodsData.goods_content.length == 0) {
				this.$util.showToast({
					title: '请填写商品详情'
				});
				return false;
			}

			if (this.goodsData.max_buy.length > 0) {
				if (isNaN(this.goodsData.max_buy) || !this.$util.data().regExp.number.test(this.goodsData.max_buy)) {
					this.$util.showToast({
						title: '[限购]格式输入错误'
					});
					return false;
				}
				if (this.goodsData.max_buy < 0) {
					this.$util.showToast({
						title: '限购数量不能小于'
					});
					return false;
				}
			}

			if (this.goodsData.min_buy.length > 0) {
				if (isNaN(this.goodsData.min_buy) || !this.$util.data().regExp.number.test(this.goodsData.min_buy)) {
					this.$util.showToast({
						title: '[起售]格式输入错误'
					});
					return false;
				}
				if (this.goodsData.min_buy < 0) {
					this.$util.showToast({
						title: '起售数量不能小于'
					});
					return false;
				}

				if (this.goodsData.max_buy > 0 && parseInt(this.goodsData.min_buy) > parseInt(this.goodsData.max_buy)) {
					this.$util.showToast({
						title: '起售数量不能大于限购数量'
					});
					return false;
				}
			}

			return true;

		},
		// 删除本地缓存
		clearStoreage() {

			// 临时选择的商品图片
			uni.removeStorageSync("selectedAlbumImg");
			uni.removeStorageSync("selectedAlbumImgTemp");

			// 商品规格
			uni.removeStorageSync("editGoodsSpecFormat");
			uni.removeStorageSync("editGoodsSkuData");

			// 物流公司
			uni.removeStorageSync("editGoodsShippingTemplateId");
			uni.removeStorageSync("editGoodsShippingTemplateName");

			// 商品状态
			uni.removeStorageSync("editGoodsState");

			// 商品详情
			uni.removeStorageSync("editGoodsContent");

			// 商品参数
			uni.removeStorageSync("editGoodsAttrClass");
			uni.removeStorageSync("editGoodsAttrName");
			uni.removeStorageSync("editGoodsAttrFormat");
		},
		save() {
			if (!this.verify()) return;

			var goods_shop_category_ids = [];

			for (var key in this.shopCategoryData) {
				if (this.shopCategoryData[key].category_id) {
					goods_shop_category_ids.push(this.shopCategoryData[key].category_id);
				}
			}

			// 清空规格的图片
			for (var i = 0; i < this.goodsData.goods_sku_data.length; i++) {
				if (this.goodsData.goods_sku_data[i].sku_images.length == 0) this.goodsData.goods_sku_data[i].sku_image = '';
			}

			var data = JSON.parse(JSON.stringify(this.goodsData));
			delete data.category_name;

			if (data.goods_spec_format.length == 0) {

				// 单规格数据
				var singData = {
					sku_id: (data.goods_id ? data.sku_id : 0),
					sku_name: data.goods_name,
					spec_name: '',
					sku_no: data.sku_no,
					sku_spec_format: '',
					price: data.price,
					market_price: data.market_price,
					cost_price: data.cost_price,
					stock: data.goods_stock,
					stock_alarm: data.goods_stock_alarm,
					weight: data.weight,
					volume: this.goodsData.volume,
					sku_image: data.goods_image[0],
					sku_images: data.goods_image.toString()
				}

				var singleSkuData = JSON.stringify([singData]);
			}

			data.goods_image = data.goods_image.toString();

			// 商品规格json格式
			data.goods_spec_format = data.goods_spec_format.length > 0 ? JSON.stringify(data.goods_spec_format) : '';

			// SKU商品数据
			data.goods_sku_data = data.goods_sku_data.length > 0 ? JSON.stringify(data.goods_sku_data) : singleSkuData;

			// 商品参数json格式
			data.goods_attr_format = data.goods_attr_format.length > 0 ? JSON.stringify(data.goods_attr_format) : '';

			data.spec_type_status = data.goods_spec_format.length > 0 ? 1 : 0;

			data.goods_shop_category_ids = goods_shop_category_ids.toString();

			var method = 'goods';
			var url = '';
			if (data.goods_class == 2) {
				method = 'virtualgoods';
			}

			url = `/shopapi/${method}/addGoods`;
			if (data.goods_id) url = `/shopapi/${method}/editGoods`;

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
						this.clearStoreage();
						setTimeout(() => {
							this.$util.redirectTo('/pages/goods/list', {}, 'tabbar');
						}, 1000);
					} else {
						this.repeatFlag = false;
					}
				}
			});
		}
	}
};
