<template>
	<view class="fastinfo">
		<view class="item-wrap">
			<view class="label">
				<text class="required color-base-text">*</text>
				<text>店铺名称</text>
			</view>
			<input type="text" class="uni-input" placeholder="请输入店铺名称" v-model="formData.shopName" @blur="blurShopName" />
		</view>
		<view class="item-wrap more-wrap" v-if="is_city == 1">
			<view class="label">
				<text class="required color-base-text">*</text>
				<text>城市分站</text>
			</view>
			<picker class="selected" :range="cityList" @change="cityChange">
				<view class="uni-input" :class="{ 'color-tip': !formData.cityName }">{{ formData.cityName ? formData.cityName : '请选择城市分站' }}</view>
			</picker>
			<text class="iconfont iconright"></text>
		</view>
		<view class="item-wrap more-wrap">
			<view class="label">
				<text class="required color-base-text">*</text>
				<text>主营行业</text>
			</view>
			<picker class="selected" :range="industryList" @change="industryChange">
				<view class="uni-input" :class="{ 'color-tip': !formData.industryName }">{{ formData.industryName ? formData.industryName : '请选择主营行业' }}</view>
			</picker>
			<text class="iconfont iconright"></text>
		</view>
		<view class="tips color-base-text" v-if="is_city == 1">注意：注册后店铺名称、城市分站不可修改。</view>
		<view class="tips color-base-text" v-else>注意：注册后店铺名称不可修改。</view>
		<button type="primary" @click="toIn()">快速入驻</button>
	</view>
</template>

<script>
import validate from '@/common/js/validate.js';
export default {
	data() {
		return {
			formData: {
				shopName: '', //店铺名称
				industryName: '', //主营行业名称
				industryNum: '', //主营行业ID
				cityName: '', //城市分站名称
				cityNum: '' //城市分站ID
			},
			industryList: [], //主营行业数组
			industryNumList: [], //主营行业ID数组
			cityList: [], //城市分站数组
			cityNumList: [], //城市分站ID数组
			isShop: false //店铺名称是否存在
		};
	},
	onShow() {
		this.initData();
	},
	methods: {
		initData() {
			this.$api.sendRequest({
				url: '/shopapi/apply/index',
				success: res => {
					var data = res.data;
					if (res.code == 0) {
						this.is_city = data.is_city;
						if (this.is_city == 1) {
							//城市分站
							for (var key in data.web_city) {
								this.cityList.push(data.web_city[key].site_area_name);
								this.cityNumList.push(data.web_city[key].site_id);
							}
						}
						//所属行业
						for (var key in data.shop_category) {
							this.industryList.push(data.shop_category[key].category_name);
							this.industryNumList.push(data.shop_category[key].category_id);
						}
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		//店铺名称失去焦点
		blurShopName(e) {
			this.$api.sendRequest({
				url: '/shopapi/apply/shopNameExist',
				data: {
					shop_name: e.detail.value
				},
				success: res => {
					if (res.code !== 0) {
						this.$util.showToast({
							title: res.message
						});
						this.isShop = !this.isShop;
					}
				}
			});
		},
		cityChange(e) {
			if (this.cityList.length == 0) return;
			this.formData.cityName = this.cityList[e.detail.value];
			this.formData.cityNum = this.cityNumList[e.detail.value];
		},
		industryChange(e) {
			if (this.industryList.length == 0) return;
			this.formData.industryName = this.industryList[e.detail.value];
			this.formData.industryNum = this.industryNumList[e.detail.value];
		},
		vertify() {
			let rule = [
				{
					name: 'shopName',
					checkType: 'required',
					errorMsg: '请输入店铺名称'
				},
				{
					name: 'industryName',
					checkType: 'required',
					errorMsg: '请选择主营行业'
				}
			];
			if (this.is_city == 1) {
				rule.push({
					name: 'cityName',
					checkType: 'required',
					errorMsg: '请选择城市分站'
				});
			}
			if (this.isShop != false) {
				this.$util.showToast({
					title: '店铺已存在'
				});
				return false;
			}

			let checkRes = validate.check(this.formData, rule);
			if (checkRes) {
				return true;
			} else {
				this.$util.showToast({
					title: validate.error
				});
				return false;
			}
		},
		toIn() {
			if (this.vertify()) {
				this.$api.sendRequest({
					url: '/shopapi/apply/experienceApply',
					data: {
						site_name: this.formData.shopName, //发送店铺名称
						category_id: this.formData.industryNum, //主营行业id
						category_name: this.formData.industryName, //主营行业名称
						website_id: this.formData.cityNum //城市分站id
					},
					success: res => {
						this.$util.showToast({
							title: res.message
						});
						if (res.code == 0) {
							this.$util.redirectTo('/pages/apply/successfully', {}, 'redirectTo');
						}
					}
				});
			}
		}
	}
};
</script>

<style lang="scss">
.fastinfo {
	overflow: hidden;
	width: 100%;
	height: 100%;

	.item-wrap {
		min-height: 100rpx;
		border-bottom: 2rpx solid $color-line;
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 30rpx 0;
		background-color: #fff;
		&:first-child {
			margin-top: 20rpx;
		}
		&:last-child {
			border: none;
		}
		.uni-input {
			text-align: right;
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
	}

	.tips {
		text-align: right;
		margin-top: 20rpx;
		font-size: $font-size-tag;
	}
	.required {
		margin-right: 6rpx;
	}

	button {
		margin-top: 40rpx;
	}
}
</style>
