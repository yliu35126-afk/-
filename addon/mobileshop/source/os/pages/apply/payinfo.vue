<template>
	<view class="authentication-info">
		<view class="company-info info-chunk">
			<view class="apply-input-wrap">
				<text>店铺名称</text>
				<input type="text" class="input-content" placeholder="请输入店铺名称" disabled="false" v-model="infoData.shop_name" />
			</view>
			<view class="apply-input-wrap">
				<text>开店套餐</text>
				<input type="text" class="input-content" placeholder="请输入开店套餐" disabled="false" v-model="infoData.group_name" />
			</view>
			<view class="apply-input-wrap">
				<text>主营行业</text>
				<input type="text" class="input-content" placeholder="请输入主营行业" disabled="false" v-model="selectCategory" />
			</view>
			<view class="apply-input-wrap">
				<text>入驻时长</text>
				<input type="text" class="input-content" placeholder="请输入入驻时长" disabled="false" v-model="selectApplyYear.value" />
			</view>
			<view class="apply-input-wrap">
				<text>保证金</text>
				<input type="text" class="input-content" placeholder="0.00 元" disabled="false" v-model="applyMoney.paying_deposit" />
			</view>
			<view class="apply-input-wrap">
				<text>服务费</text>
				<input type="text" class="input-content" placeholder="0.00 元" disabled="false" v-model="applyMoney.paying_apply" />
			</view>
			<view class="apply-input-wrap">
				<text>总计</text>
				<input type="text" class="input-content" placeholder="0.00 元" disabled="false" v-model="applyMoney.paying_amount" />
			</view>
		</view>

		<view class="company-info info-chunk">
			<view class="apply-input-wrap">
				<text>付款凭证</text>
				<view class="input-img" @click="uplodImg()">
					<text class="iconfont iconadd1" v-if="!payData.paying_money_certificate"></text>
					<image v-else :src="$util.img(payData.paying_money_certificate)" mode="aspectFit"></image>
				</view>
			</view>
			<view class="apply-input-wrap">
				<text>付款凭证说明</text>
				<input type="text" class="input-content" placeholder="请输入付款凭证说明" v-model="payData.paying_money_certificate_explain" />
			</view>
		</view>
		<view class="company-info info-chunk">
			<view class="shroff-account">
				<text class="account-title">收款账户信息</text>
				<view class="account-content">
					<view>
						<text>银行开户名：</text>
						<text>{{ infoData.bank_account_name }}</text>
					</view>
					<view>
						<text>银行账户：</text>
						<text>{{ infoData.bank_account_no }}</text>
					</view>
					<view>
						<text>开户名称：</text>
						<text>{{ infoData.bank_name }}</text>
					</view>
					<view>
						<text>开户所在地：</text>
						<text>{{ infoData.bank_address }}</text>
					</view>
				</view>
			</view>
		</view>
		<button type="primary" @click="save">提交</button>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import validate from '@/common/js/validate.js';
export default {
	data() {
		return {
			categoryArr: {
				key: [],
				name: [],
				value: []
			},
			selectCategory: '',
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
			infoData: {
				shop_name: '',
				group_name: '',
				group_id: '',
				category_id: '',
				category_name: '',
				apply_year: ''
			},
			payData: {
				paying_money_certificate: '',
				paying_money_certificate_explain: ''
			}
		};
	},
	async onShow() {
		var shop_info = uni.getStorageSync('shop_info') ? JSON.parse(uni.getStorageSync('shop_info')) : null;
		if (shop_info) this.infoData.site_id = shop_info.site_id;
		this.initData();
	},
	methods: {
		initData() {
			this.$api.sendRequest({
				url: '/shopapi/apply/index',
				success: res => {
					let data = res.data;

					//结算银行信息
					if (res.code == 0 && data && data.receivable_config) {
						let receivableData = data.receivable_config.value;
						this.infoData.bank_account_name = receivableData.bank_account_name;
						this.infoData.bank_account_no = receivableData.bank_account_no;
						this.infoData.bank_address = receivableData.bank_address;
						this.infoData.bank_name = receivableData.bank_name;
					}

					this.categoryArr = {
						key: [],
						name: [],
						value: []
					};
					//主营行业
					for (let key in data.shop_category) {
						this.categoryArr.key.push(data.shop_category[key].category_id);
						this.categoryArr.name.push(data.shop_category[key].category_name);
						let val = data.shop_category[key].category_name + '(保证金' + data.shop_category[key].baozheng_money + ')';
						this.categoryArr.value.push(val);
					}

					if (res.code == 0 && data.shop_apply_info) {
						let initData = data.shop_apply_info;

						this.infoData.shop_name = initData.shop_name;

						this.infoData.group_name = initData.group_name;
						this.infoData.group_id = initData.group_id;

						//主营行业
						this.infoData.category_id = initData.category_id;
						this.infoData.category_name = initData.category_name;
						let categoryIndex = this.categoryArr.key.indexOf(initData.category_id);
						this.selectCategory = this.categoryArr.value[categoryIndex];

						//入驻年限
						this.infoData.apply_year = initData.apply_year;
						this.selectApplyYear.value = this.applyYearArr[initData.apply_year - 1];
						this.selectApplyYear.key = initData.apply_year - 1;

						//支付凭证
						if (initData.paying_money_certificate) {
							this.payData.paying_money_certificate = initData.paying_money_certificate;
							this.payData.paying_money_certificate_explain = initData.paying_money_certificate_explain;
						}

						//计算费用
						this.getApplyMoney();
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		applyYearChange(e) {
			let index = parseInt(e.detail.value);
			this.selectApplyYear.key = index;
			this.selectApplyYear.value = this.applyYearArr[index];
			this.infoData.apply_year = index + 1;

			if (this.infoData.apply_year && this.infoData.category_id && this.infoData.group_id) {
				this.getApplyMoney();
			}
		},
		getApplyMoney() {
			if (!this.infoData.apply_year || !this.infoData.category_id || !this.infoData.group_id) return false;
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
						this.applyMoney.paying_deposit = data.paying_deposit + ' 元';
						this.applyMoney.paying_apply = data.paying_apply + ' 元';
						this.applyMoney.paying_amount = data.paying_amount + ' 元';
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		uplodImg(type) {
			this.$util.upload(
				{
					number: 1,
					path: 'image'
				},
				res => {
					if (res) {
						this.$util.showToast({
							title: '上传成功'
						});
						this.payData.paying_money_certificate = res[0];
					}
				}
			);
		},
		vertify() {
			let rule = [{ name: 'paying_money_certificate', checkType: 'required', errorMsg: '请上传支付凭证' }];

			let checkRes = validate.check(this.payData, rule);
			if (checkRes) {
				return true;
			} else {
				this.$util.showToast({ title: validate.error });
				return false;
			}
		},
		toPrevious() {
			this.$util.redirectTo('/pages/apply/audit');
		},
		async save() {
			if (this.vertify()) {
				this.$api.sendRequest({
					url: '/shopapi/apply/editApply',
					data: this.payData,
					success: res => {
						if (res.code == 0) {
							this.$util.redirectTo('/pages/apply/audit',{},'redirectTo');
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
		.input-content {
			text-align: right;
			font-size: $font-size-base;
			flex:1;
			margin-left: 30rpx;
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
.shroff-account {
	padding-bottom: 38rpx;
	padding-top: 38rpx;
	background: #fff;
	border-radius: $border-radius;
	.account-title {
		display: block;
		padding: 20rpx 0 20rpx;
		font-size: $font-size-base;
		color: $color-title;
		text-align: center;
		border: 1px solid $color-line;
		border-bottom: 0;
	}
	.account-content {
		view {
			display: flex;
			border-top: 2rpx solid $color-line;
			border-right: 2rpx solid $color-line;
			&:last-of-type {
				border-bottom: 2rpx solid $color-line;
			}
		}
		text {
			padding: 20rpx;
			border-left: 2rpx solid $color-line;
			&:first-of-type {
				width: 180rpx;
			}
			&:last-of-type {
				// border-ri: 2rpx solid $color-line;
			}
		}
	}
}
button {
	margin-top: 40rpx;
	margin-bottom: 40rpx;
}
</style>
