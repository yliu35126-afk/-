<template>
	<view class="authentication-info">
		<view class="company-info info-chunk">
			<view class="apply-input-wrap">
				<text>店铺名称</text>
				<input type="text" class="input-content" :disabled="infoData.site_id ? true : false" v-model="infoData.shop_name" placeholder="请输入店铺名称" />
			</view>
			<view class="apply-input-wrap more-wrap" v-if="is_city == 1">
				<text>城市分站</text>
				<picker mode="selector" :value="selectWebCity.key" :range="webCityArr.value" @change="webCityChange">
					<text class="input-content" :class="{ 'color-tip': !selectWebCity.value }">{{ selectWebCity.value ? selectWebCity.value : '请选择城市分站' }}</text>
					<text class="iconfont iconright"></text>
				</picker>
			</view>
			<view class="apply-input-wrap">
				<text>开店套餐</text>
				<input type="text" class="input-content" disabled="false" placeholder="请输入开店套餐" v-model="infoData.group_name" />
			</view>
			<view class="apply-input-wrap more-wrap">
				<text>主营行业</text>
				<block v-if="infoData.site_id">
					<view class="input-content">{{ infoData.category_name }}</view>
				</block>
				<block v-else>
					<picker mode="selector" :value="selectCategory.key" :range="categoryArr.value" @change="categoryChange">
						<text class="input-content" :class="{ 'color-tip': !selectCategory.value }">{{ selectCategory.value ? selectCategory.value : '请选择主营行业' }}</text>
						<text class="iconfont iconright"></text>
					</picker>
				</block>
			</view>
			<view class="apply-input-wrap more-wrap">
				<text>入驻时长</text>
				<picker class="" mode="selector" :value="selectApplyYear.key" :range="applyYearArr" @change="applyYearChange">
					<text class="uni-input" :class="{ 'color-tip': !selectApplyYear.value }">{{ selectApplyYear.value ? selectApplyYear.value : '请选择入驻时长' }}</text>
					<text class="iconfont iconright"></text>
				</picker>
			</view>
			<view class="apply-input-wrap">
				<text>保证金</text>
				<input type="text" class="input-content" placeholder="0.00" disabled="false" v-model="applyMoney.paying_deposit" />
				<text class="unit">元</text>
			</view>
			<view class="apply-input-wrap">
				<text>服务费</text>
				<input type="text" class="input-content" placeholder="0.00" disabled="false" v-model="applyMoney.paying_apply" />
				<text class="unit">元</text>
			</view>
			<view class="apply-input-wrap">
				<text>总计</text>
				<input type="text" class="input-content" placeholder="0.00" disabled="false" v-model="applyMoney.paying_amount" />
				<text class="unit">元</text>
			</view>
		</view>

		<view class="set-next">
			<button type="default" @click="toPrevious()">上一步</button>
			<button type="primary" @click="save()">提交</button>
		</view>

		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import validate from '@/common/js/validate.js';
export default {
	data() {
		return {
			shopInfo: {
				site_id: '',
				website_id: '',
				category_name: '',
				category_id: '',
				site_name: ''
			},
			webCityArr: {
				key: [],
				value: []
			},
			selectWebCity: {
				key: '',
				value: ''
			},
			categoryArr: {
				key: [],
				name: [],
				value: []
			},
			selectCategory: {
				key: '',
				value: ''
			},
			applyYearArr: ['1年', '2年', '3年', '4年', '5年'],
			selectApplyYear: {
				key: '',
				value: ''
			},
			applyMoney: {
				paying_deposit: '',
				paying_apply: '',
				paying_amount: ''
			},
			shopExist: false,
			infoData: {
				shop_name: '',
				website_id: '', //城市分站
				group_name: '',
				group_id: '',
				category_id: '',
				category_name: '',
				apply_year: ''
			},
			initShopName: '',
			is_city: 0
		};
	},
	async onShow() {
		let groupInfo = uni.getStorageSync('shopPackage') ? JSON.parse(uni.getStorageSync('shopPackage')) : null;
		if (groupInfo) {
			this.infoData.group_id = groupInfo.group_id;
			this.infoData.group_name = groupInfo.group_name;
		}
		await this.initData();
		this.getShopInfo();
	},
	methods: {
		async initData() {
			var res = await this.$api.sendRequest({
				url: '/shopapi/apply/index',
				async: false
			});
			let data = res.data;
			if (res.code == 0 && data) {
				this.is_city = data.is_city;
				if (this.is_city == 1) {
					this.webCityArr = {
						key: [],
						value: []
					};
					this.categoryArr = {
						key: [],
						name: [],
						value: []
					};
					//城市分站
					for (let key in data.web_city) {
						this.webCityArr.key.push(data.web_city[key].site_id);
						this.webCityArr.value.push(data.web_city[key].site_area_name);
					}
				}
				//开店套餐
				for (let key in data.group_info) {
					if (this.infoData.group_id && data.group_info[key].group_id == this.infoData.group_id) {
						this.infoData.group_name = data.group_info[key].group_name;
					}
				}
				//主营行业
				for (let key in data.shop_category) {
					this.categoryArr.key.push(data.shop_category[key].category_id);
					this.categoryArr.name.push(data.shop_category[key].category_name);
					let val = data.shop_category[key].category_name + '(保证金' + data.shop_category[key].baozheng_money + ')';
					this.categoryArr.value.push(val);
				}

				if (data.shop_apply_info) {
					let initData = data.shop_apply_info;

					this.initShopName = initData.shop_name;
					this.infoData.shop_name = initData.shop_name;

					this.infoData.group_name = initData.group_name;
					this.infoData.group_id = initData.group_id;

					//主营行业
					this.infoData.category_id = initData.category_id;
					this.infoData.category_name = initData.category_name;
					let categoryIndex = this.categoryArr.key.indexOf(initData.category_id);
					this.selectCategory.key = categoryIndex;
					this.selectCategory.value = this.categoryArr.value[categoryIndex];

					//城市分站
					this.infoData.website_id = initData.website_id;
					let websiteIndex = this.webCityArr.key.indexOf(initData.website_id);
					this.selectWebCity.key = websiteIndex;
					this.selectWebCity.value = this.webCityArr.value[websiteIndex];

					//入驻年限
					this.infoData.apply_year = initData.apply_year;
					this.selectApplyYear.value = this.applyYearArr[initData.apply_year - 1];
					this.selectApplyYear.key = initData.apply_year - 1;

					//计算费用
					this.getApplyMoney();
				}
			}
		},
		getShopInfo() {
			var data = uni.getStorageSync('shop_info') ? JSON.parse(uni.getStorageSync('shop_info')) : {};
			if (data) {
				this.infoData.site_id = data.site_id;

				this.infoData.category_name = data.category_name;
				this.infoData.category_id = data.category_id;

				this.infoData.shop_name = data.site_name;
				this.initShopName = data.site_name;

				let index = this.webCityArr.key.indexOf(data.website_id);
				this.selectWebCity.key = index;
				this.selectWebCity.value = this.webCityArr.value[index];
				this.infoData.website_id = data.website_id;
			}
			if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
		},
		webCityChange(e) {
			if (this.webCityArr.value.length == 0) return;
			let index = e.detail.value;
			this.selectWebCity.key = index;
			this.selectWebCity.value = this.webCityArr.value[index];
			this.infoData.website_id = this.webCityArr.key[index];
		},
		categoryChange(e) {
			if (this.categoryArr.value.length == 0) return;
			let index = e.detail.value;
			this.selectCategory.key = index;
			this.selectCategory.value = this.categoryArr.value[index];
			this.infoData.category_id = this.categoryArr.key[index];
			this.infoData.category_name = this.categoryArr.name[index];

			if (this.infoData.apply_year && this.infoData.category_id && this.infoData.group_id) {
				this.getApplyMoney();
			}
		},
		applyYearChange(e) {
			if (this.applyYearArr.length == 0) return;
			let index = parseInt(e.detail.value);
			this.selectApplyYear.key = index;
			this.selectApplyYear.value = this.applyYearArr[index];
			this.infoData.apply_year = index + 1;

			if (this.infoData.apply_year && this.infoData.category_id && this.infoData.group_id) {
				this.getApplyMoney();
			}
		},
		getApplyMoney() {
			this.$api.sendRequest({
				url: '/shopapi/apply/getApplyMoney',
				data: {
					apply_year: this.infoData.apply_year,
					category_id: this.infoData.category_id,
					group_id: this.infoData.group_id
				},
				success: res => {
					if (res.code == 0 && res.data) {
						let data = res.data;
						this.applyMoney = {
							paying_deposit: '',
							paying_apply: '',
							paying_amount: ''
						};
						this.applyMoney.paying_deposit = data.paying_deposit;
						this.applyMoney.paying_apply = data.paying_apply;
						this.applyMoney.paying_amount = data.paying_amount;
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		async shopNameExist() {
			let res = await this.$api.sendRequest({
				url: '/shopapi/apply/shopNameExist',
				data: {
					shop_name: this.infoData.shop_name
				},
				async: false
			});
			if (res.code == 0 || this.initShopName == this.infoData.shop_name) {
				this.shopExist = true;
			} else {
				this.$util.showToast({
					title: res.message
				});
				this.shopExist = false;
			}
		},
		vertify() {
			let rule = [];
			rule = [
				{ name: 'shop_name', checkType: 'required', errorMsg: '请输入店铺名称' },
				{ name: 'group_name', checkType: 'required', errorMsg: '请输入开店套餐' },
				{ name: 'category_name', checkType: 'required', errorMsg: '请选择主营行业' },
				{ name: 'apply_year', checkType: 'required', errorMsg: '请选择入驻时间' }
			];
			if (this.is_city == 1 && this.infoData.website_id !== 0) {
				rule.push({ name: 'website_id', checkType: 'required', errorMsg: '请选择城市分站' });
			}

			let checkRes = validate.check(this.infoData, rule);
			if (checkRes) {
				return true;
			} else {
				this.$util.showToast({ title: validate.error });
				return false;
			}
		},
		toPrevious() {
			this.$util.redirectTo('/pages/apply/banInfo');
		},
		async save() {
			if (!this.vertify()) return false;
			await this.shopNameExist();
			if (this.shopExist) {
				let openInfoObj = uni.getStorageSync('openInfo') ? JSON.parse(uni.getStorageSync('openInfo')) : null;
				let bankInfoObj = uni.getStorageSync('bankInfo') ? JSON.parse(uni.getStorageSync('bankInfo')) : null;
				let obj = this.infoData;
				if (openInfoObj) obj = Object.assign(this.infoData, openInfoObj);
				if (bankInfoObj) obj = Object.assign(this.infoData, bankInfoObj);
				this.$api.sendRequest({
					url: '/shopapi/apply/apply',
					data: obj,
					success: res => {
						if (res.code == 0) {
							uni.removeStorageSync('shopPackage');
							uni.removeStorageSync('openInfo');
							uni.removeStorageSync('bankInfo');
							this.$util.redirectTo('/pages/apply/audit',{},'reLaunch');
						} else {
							this.$util.showToast({
								title: res.message
							});
						}
					}
				});
			}
		}
	}
};
</script>

<style lang="scss">
.authentication-info {
	overflow: hidden;
}
.info-chunk {
	padding: 0 30rpx;
	margin-top: 20rpx;
	background-color: #fff;
}
.company-info {
	.apply-input-wrap {
		display: flex;
		align-items: center;
		justify-content: space-between;
		min-height: 100rpx;
		border-top: 2rpx solid $color-line;
		&:first-of-type {
			border: none;
		}
		&.more-wrap {
			.selected {
				vertical-align: middle;
				display: inline-block;
				flex: 1;
				text-align: right;
				color: $color-tip;
				overflow: hidden;
				white-space: pre;
				text-overflow: ellipsis;
				&.have {
					color: $color-title;
				}
			}
			.iconfont {
				color: $color-tip;
				margin-left: 20rpx;
			}
		}
		.input-content {
			text-align: right;
			font-size: $font-size-base;
			flex: 1;
		}
		.unit {
			margin-left: 20rpx;
			width: 40rpx;
		}
		.input-img {
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 40rpx 0;
			width: 300rpx;
			height: 140rpx;
			border: 2rpx dashed $color-disabled;
			.iconfont {
				font-size: 68rpx;
				color: $color-disabled;
			}
			image {
				width: 100%;
				height: 100%;
			}
		}
	}
}
.set-next {
	display: flex;
	align-items: center;
	justify-content: space-around;
	margin-top: 80rpx;
	margin-bottom: 80rpx;
	button {
		flex: 1;
	}
}
</style>
