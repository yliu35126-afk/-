<template>
	<view class="authentication-info">
		<view class="item-wrap">
			<view class="form-wrap">
				<text class="label">已选开店套餐</text>
				<text class="value color-base-text">{{ shopPackage.group_name }}</text>
			</view>
		</view>
		<view class="item-wrap type">
			<view class="form-title color-title">申请类型</view>
			<view class="type-wrap">
				<view :class="{ 'selected color-base-text color-base-border': infoData.cert_type == 2 }" @click="infoData.cert_type = 2">
					<text>公司</text>
					<text class="iconfont iconduigou1 color-base-text"></text>
				</view>
				<view :class="{ 'selected color-base-text color-base-border': infoData.cert_type == 1 }" @click="infoData.cert_type = 1">
					<text>个人</text>
					<text class="iconfont iconduigou1 color-base-text"></text>
				</view>
			</view>
		</view>

		<view class="item-wrap" v-if="infoData.cert_type == 2">
			<view class="form-wrap">
				<text class="label">公司名称</text>
				<input type="text" class="uni-input" placeholder="请输入公司名称" v-model="infoData.company_name" />
			</view>
			<view class="form-wrap more-wrap">
				<text class="label">联系地址</text>
				<pick-regions class="selected" :default-regions="defaultRegions" @getRegions="handleGetRegions">
					<view class="uni-input" :class="{ 'color-tip': !infoData.company_region }">{{ infoData.company_region ? infoData.company_region : '请选择省市区县' }}</view>
				</pick-regions>
			</view>
			<view class="form-wrap">
				<text class="label">公司详细地址</text>
				<input type="text" class="uni-input" placeholder="请输入详细地址" v-model="infoData.company_address" />
			</view>
			<view class="form-wrap">
				<text class="label">统一社会信用码</text>
				<input type="text" class="uni-input" placeholder="请输入统一社会信用码" v-model="infoData.business_licence_number" />
			</view>
			<view class="form-wrap input-img">
				<text class="label">营业执照电子版</text>
				<view class="img-wrap" @click="uplodImg('business')">
					<text class="iconfont iconadd1" v-if="!infoData.business_licence_number_electronic"></text>
					<image v-else :src="$util.img(infoData.business_licence_number_electronic)" mode="aspectFit"></image>
				</view>
			</view>
			<view class="form-wrap">
				<text class="label">法定经营范围</text>
				<input type="text" class="uni-input" placeholder="请输入法定经营范围" v-model="infoData.business_sphere" />
			</view>
		</view>

		<view class="item-wrap">
			<view class="form-wrap">
				<text class="label" v-if="infoData.cert_type == 2">法人姓名</text>
				<text class="label" v-else>联系人姓名</text>
				<input type="text" class="uni-input" placeholder="请输入姓名" v-model="infoData.contacts_name" />
			</view>
			<view class="form-wrap">
				<text class="label" v-if="infoData.cert_type == 2">法人手机</text>
				<text class="label" v-else>联系人手机</text>
				<input type="text" class="uni-input" maxlength="11" placeholder="请输入手机号" v-model="infoData.contacts_mobile" />
			</view>
			<view class="form-wrap">
				<text class="label" v-if="infoData.cert_type == 2">法人身份证</text>
				<text class="label" v-else>联系人身份证</text>
				<input type="text" class="uni-input" maxlength="18" placeholder="请输入身份证号" v-model="infoData.contacts_card_no" />
			</view>
			<view class="form-wrap input-img">
				<text class="label" v-if="infoData.cert_type == 2">法人身份证正面</text>
				<text class="label" v-else>身份证正面</text>
				<view class="img-wrap" @click="uplodImg('front')">
					<text class="iconfont iconadd1" v-if="!infoData.contacts_card_electronic_2"></text>
					<image v-else :src="$util.img(infoData.contacts_card_electronic_2)" mode="aspectFit"></image>
				</view>
			</view>
			<view class="form-wrap input-img">
				<text class="label" v-if="infoData.cert_type == 2">法人身份证反面</text>
				<text class="label" v-else>身份证反面</text>
				<view class="img-wrap" @click="uplodImg('reverse')">
					<text class="iconfont iconadd1" v-if="!infoData.contacts_card_electronic_3"></text>
					<image v-else :src="$util.img(infoData.contacts_card_electronic_3)" mode="aspectFit"></image>
				</view>
			</view>
		</view>

		<view class="set-next">
			<button type="default" @click="toPrevious()">上一步</button>
			<button type="primary" @click="toNext()">下一步</button>
		</view>

		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import validate from '@/common/js/validate.js';
import pickRegions from '@/components/pick-regions/pick-regions.vue';
export default {
	data() {
		return {
			infoData: {
				cert_type: 2, //申请类型
				company_name: '', //公司名称
				company_region: '', //公司联系地址
				company_province_id: '', //省
				company_city_id: '', //市
				company_district_id: '', //县
				company_address: '', //公司详细地址
				company_full_address: '', //完整地址
				business_licence_number: '', //统一社会信用代码
				business_licence_number_electronic: '', //营业执照电子版
				business_sphere: '', //法定经营范围
				contacts_name: '', //联系人姓名
				contacts_mobile: '', //联系人手机
				contacts_card_no: '', //联系人身份证
				contacts_card_electronic_2: '', //身份证正面
				contacts_card_electronic_3: '' //身份证反面
			},
			defaultRegions: [], //省市区选择器
			shopPackage: {
				group_name: ''
			}
		};
	},
	components: {
		pickRegions
	},
	onShow() {
		if (uni.getStorageSync('shopPackage')) this.shopPackage = JSON.parse(uni.getStorageSync('shopPackage'));
		this.initData();
	},
	methods: {
		initData() {
			this.$api.sendRequest({
				url: '/shopapi/apply/index',
				success: res => {
					let data = res.data;
					if (res.code == 0 && data && data.shop_apply_info) {
						let initData = data.shop_apply_info;
						this.infoData.cert_type = initData.cert_type;
						this.infoData.company_name = initData.company_name;

						this.defaultRegions = [];
						this.defaultRegions.push(initData.company_province_id);
						this.defaultRegions.push(initData.company_city_id);
						this.defaultRegions.push(initData.company_district_id);

						this.infoData.company_address = initData.company_address;
						this.infoData.business_licence_number = initData.business_licence_number;
						this.infoData.business_licence_number_electronic = initData.business_licence_number_electronic;
						this.infoData.business_sphere = initData.business_sphere;
						this.infoData.contacts_name = initData.contacts_name;
						this.infoData.contacts_mobile = initData.contacts_mobile;
						this.infoData.contacts_card_no = initData.contacts_card_no;
						this.infoData.contacts_card_electronic_2 = initData.contacts_card_electronic_2;
						this.infoData.contacts_card_electronic_3 = initData.contacts_card_electronic_3;
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		// 获取选择的地区
		handleGetRegions(regions) {
			this.infoData.company_region = '';
			this.infoData.company_region += regions[0] != undefined ? regions[0].label : '';
			this.infoData.company_region += regions[1] != undefined ? '-' + regions[1].label : '';
			this.infoData.company_region += regions[2] != undefined ? '-' + regions[2].label : '';

			this.infoData.company_province_id = regions[0] != undefined ? regions[0].value : '';
			this.infoData.company_city_id = regions[1] != undefined ? regions[1].value : '';
			this.infoData.company_district_id = regions[2] != undefined ? regions[2].value : '';
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
						if (type == 'business') this.infoData.business_licence_number_electronic = res[0];
						else if (type == 'front') this.infoData.contacts_card_electronic_2 = res[0];
						else if (type == 'reverse') this.infoData.contacts_card_electronic_3 = res[0];
					}
				}
			);
		},
		//公司表单验证
		vertify() {
			let rule = [
				{ name: 'company_name', checkType: 'required', errorMsg: '请输入公司名称' },
				{ name: 'company_region', checkType: 'required', errorMsg: '请选择联系地址' },
				{ name: 'company_address', checkType: 'required', errorMsg: '请输入公司详细地址' },
				{ name: 'business_licence_number', checkType: 'required', errorMsg: '请输入统一社会信用代码' },
				{ name: 'business_licence_number_electronic', checkType: 'required', errorMsg: '请上传营业执照' },
				{ name: 'business_sphere', checkType: 'required', errorMsg: '请输入法定经营范围' },
				{ name: 'contacts_name', checkType: 'required', errorMsg: '请输入姓名' },
				{ name: 'contacts_mobile', checkType: 'required', errorMsg: '请输入手机号' },
				{ name: 'contacts_mobile', checkType: 'phoneno', errorMsg: '手机号格式不正确,请重新输入' },
				{ name: 'contacts_card_no', checkType: 'required', errorMsg: '请输入身份证号' },
				{ name: 'contacts_card_no', checkType: 'lengthMin', checkRule: 18, errorMsg: '身份证号不能少于18位' },
				{ name: 'contacts_card_electronic_2', checkType: 'required', errorMsg: '请上传身份证正面' },
				{ name: 'contacts_card_electronic_3', checkType: 'required', errorMsg: '请上传身份证反面' }
			];
			let checkRes = validate.check(this.infoData, rule);
			if (checkRes) {
				return true;
			} else {
				this.$util.showToast({ title: validate.error });
				return false;
			}
		},
		//个人表单验证
		vertify2() {
			let rule = [
				{ name: 'contacts_name', checkType: 'required', errorMsg: '请输入姓名' },
				{ name: 'contacts_mobile', checkType: 'required', errorMsg: '请输入手机号' },
				{ name: 'contacts_mobile', checkType: 'phoneno', errorMsg: '手机号格式不正确,请重新输入' },
				{ name: 'contacts_card_no', checkType: 'required', errorMsg: '请输入身份证号' },
				{ name: 'contacts_card_no', checkType: 'lengthMin', checkRule: 18, errorMsg: '身份证号不能少于18位' },
				{ name: 'contacts_card_electronic_2', checkType: 'required', errorMsg: '请上传身份证正面' },
				{ name: 'contacts_card_electronic_3', checkType: 'required', errorMsg: '请上传身份证反面' }
			];

			let checkRes = validate.check(this.infoData, rule);
			if (checkRes) {
				return true;
			} else {
				this.$util.showToast({ title: validate.error });
				return false;
			}
		},
		toPrevious() {
			this.$util.redirectTo('/pages/apply/shopset');
		},
		toNext() {
			if (this.infoData.cert_type == 2 && this.vertify()) {
				this.infoData.company_full_address = this.infoData.company_region + this.infoData.company_address;
				uni.setStorage({
					key: 'openInfo',
					data: JSON.stringify(this.infoData)
				});
				this.$util.redirectTo('/pages/apply/bankInfo');
			} else if (this.infoData.cert_type == 1 && this.vertify2()) {
				let obj = {};
				obj.cert_type = this.infoData.cert_type;
				obj.contacts_name = this.infoData.contacts_name;
				obj.contacts_mobile = this.infoData.contacts_mobile;
				obj.contacts_card_no = this.infoData.contacts_card_no;
				obj.contacts_card_electronic_2 = this.infoData.contacts_card_electronic_2;
				obj.contacts_card_electronic_3 = this.infoData.contacts_card_electronic_3;
				uni.setStorage({
					key: 'openInfo',
					data: JSON.stringify(obj)
				});
				this.$util.redirectTo('/pages/apply/bankInfo');
			}
		}
	}
};
</script>

<style lang="scss">
.authentication-info {
	overflow: hidden;
}
.item-wrap {
	margin-top: 20rpx;
	background-color: #fff;
	&.type {
		padding: 20rpx 30rpx;
	}
	.form-wrap {
		display: flex;
		align-items: center;
		margin: 0 $margin-both;
		border-bottom: 1px solid $color-line;
		height: 100rpx;
		line-height: 100rpx;
		&:last-child {
			border-bottom: none;
		}
		.label {
			margin-right: $margin-both;
			vertical-align: middle;
		}
		input {
			vertical-align: middle;
			display: inline-block;
			flex: 1;
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
		.value {
			font-weight: bold;
			vertical-align: middle;
			display: inline-block;
			flex: 1;
			text-align: right;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: pre;
		}
		&.input-img {
			height: 200rpx;
			line-height: 200rpx;
			display: block;
			.img-wrap {
				float: right;
				display: inline-block;
				margin-top: 30rpx;
				width: 300rpx;
				height: 140rpx;
				line-height: 140rpx;
				text-align: center;
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

	.type-wrap {
		display: flex;
		// align-items: center;
		// justify-content: space-between;
		margin: 20rpx 30rpx;
		view {
			flex: 1;
			position: relative;
			margin-right: 40rpx;
			height: 80rpx;
			line-height: 80rpx;
			text-align: center;
			color: $color-tip;
			border: 2rpx solid $color-disabled;
			&:last-child {
				margin-right: 0;
			}
			.iconfont {
				position: absolute;
				right: -22rpx;
				bottom: -22rpx;
				font-size: 80rpx;
				display: none;
			}
			&.selected {
				.iconfont {
					display: block;
				}
			}
		}
	}
}
.form-title {
	margin-bottom: $margin-updown;
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
