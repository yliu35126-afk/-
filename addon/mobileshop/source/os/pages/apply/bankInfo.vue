<template>
	<view class="authentication-info">
		<view class="company-info info-chunk">
			<view class="apply-input-wrap">
				<text>银行开户名</text>
				<input type="text" class="input-content" placeholder="请输入银行开户名" v-model="infoData.bank_account_name" />
			</view>
			<view class="apply-input-wrap">
				<text>开户银行账号</text>
				<input type="text" class="input-content" placeholder="请输入开户银行账号" v-model="infoData.bank_account_number" />
			</view>
			<view class="apply-input-wrap">
				<text>开户银行支行名称</text>
				<input type="text" class="input-content" placeholder="请输入开户银行支行名称" v-model="infoData.bank_name" />
			</view>
			<view class="apply-input-wrap">
				<text>开户银行所在地</text>
				<input type="text" class="input-content" placeholder="请输入开户银行所在地" v-model="infoData.bank_address" />
			</view>
			<view class="apply-input-wrap">
				<text>支行联行号</text>
				<input type="text" class="input-content" placeholder="请输入支行联行号" v-model="infoData.bank_code" />
			</view>
		</view>

		<view class="personage-info info-chunk">
			<view class="apply-input-wrap more-wrap">
				<text>结算类型</text>
				<picker class="selected" mode="selector" :value="selectBankType.id" :range="bankTypeArr.value" @change="bankTypeChange">
					<view class="uni-input" :class="{ 'color-tip': !selectBankType.value }">{{ selectBankType.value ? selectBankType.value : '请选择结算类型' }}</view>
				</picker>
				<text class="iconfont iconright"></text>
			</view>
			<block v-if="selectBankType.key == 'alipay'">
				<view class="apply-input-wrap">
					<text>用户真实姓名</text>
					<input type="text" class="input-content" placeholder="请输入用户真实姓名" v-model="infoData.settlement_bank_account_name" />
				</view>
				<view class="apply-input-wrap">
					<text>支付宝账号</text>
					<input type="text" class="input-content" placeholder="请输入支付宝账号" v-model="infoData.settlement_bank_account_number" />
				</view>
			</block>
			<block v-if="selectBankType.key == 'bank'">
				<view class="apply-input-wrap">
					<text>结算开户名</text>
					<input type="text" class="input-content" placeholder="请输入结算开户名" v-model="infoData.settlement_bank_account_name" />
				</view>
				<view class="apply-input-wrap">
					<text>结算银行账号</text>
					<input type="text" class="input-content" placeholder="请输入结算银行账号" v-model="infoData.settlement_bank_account_number" />
				</view>
				<view class="apply-input-wrap">
					<text>结算开户银行支行名称</text>
					<input type="text" class="input-content" placeholder="请输入结算开户银行支行名称" v-model="infoData.settlement_bank_name" />
				</view>
				<view class="apply-input-wrap">
					<text>结算开户银行所在地</text>
					<input type="text" class="input-content" placeholder="请输入结算开户银行所在地" v-model="infoData.settlement_bank_address" />
				</view>
			</block>
			<!-- <block v-if="selectBankType.key == 'wechatpay'">
				<view class="apply-input-wrap">
					<text>扫描绑定微信</text>
					<view class="input-img-wrap">
						<view class="input-img"><image :src="wxCode" mode="aspectFit"></image></view>
						<text class="anew-load" @click="getShopBindQrcode('refresh')">点击刷新</text>
					</view>
				</view>
				<view class="apply-input-wrap">
					<text>微信昵称</text>
					<input type="text" class="input-content" disabled="false" placeholder="请输入微信昵称" v-model="infoData.settlement_bank_address" />
				</view>
				<view class="apply-input-wrap">
					<text>用户真实姓名</text>
					<input type="text" class="input-content" placeholder="请输入用户真实姓名" v-model="infoData.settlement_bank_account_name" />
				</view>
				<view class="apply-input-wrap">
					<text>微信账号</text>
					<input type="text" class="input-content" placeholder="请输入微信账号" v-model="infoData.settlement_bank_name" />
				</view>
			</block> -->
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
export default {
	data() {
		return {
			bankTypeArr: {
				key: [],
				value: []
			},
			selectBankType: {
				key: 'bank',
				value: '银行卡',
				id: 0
			},
			wxCode: '',
			shopBindKey: '', //用于实时请求是否绑定微信
			colseTime: 'null',
			infoData: {
				bank_account_name: '', //银行开户名
				bank_account_number: '', //公司银行账号
				bank_name: '', //联系人姓名
				bank_address: '', //开户银行所在地
				bank_code: '', //支行联行号
				bank_type: 1, //结算账户类型
				settlement_bank_account_name: '',
				settlement_bank_account_number: '',
				settlement_bank_name: '', //结算开户银行支行名称
				settlement_bank_address: '' //结算开户银行所在地
			}
		};
	},
	onShow() {
		//获取结算类型
		this.getColseType();
		//获取店铺二维码
		this.getShopBindQrcode();
		//初始化数据
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
						this.infoData.bank_account_name = initData.bank_account_name;
						this.infoData.bank_account_number = initData.bank_account_number;
						this.infoData.bank_name = initData.bank_name;
						this.infoData.bank_address = initData.bank_address;
						this.infoData.bank_code = initData.bank_code;
						this.infoData.bank_type = initData.bank_type;
						this.infoData.settlement_bank_account_name = initData.settlement_bank_account_name;
						this.infoData.settlement_bank_account_number = initData.settlement_bank_account_number;
						this.infoData.settlement_bank_name = initData.settlement_bank_name;
						this.infoData.settlement_bank_address = initData.settlement_bank_address;

						this.selectBankType.id = initData.bank_type - 1;
						this.selectBankType.value = this.bankTypeArr.value[initData.bank_type - 1];
						this.selectBankType.key = this.bankTypeArr.key[initData.bank_type - 1];
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		getColseType() {
			this.$api.sendRequest({
				url: '/shopapi/apply/getTransferType',
				success: res => {
					this.bankTypeArr.key = [];
					this.bankTypeArr.value = [];
					for (let key in res) {
						if (res[key] == 'bank') {
							this.bankTypeArr.key.push(res[key]);
							this.bankTypeArr.value.push('银行卡');
						} else if (res[key] == 'alipay') {
							this.bankTypeArr.key.push(res[key]);
							this.bankTypeArr.value.push('支付宝');
						}
						//暂时隐藏，关于微信的代码先不删除
						//  else if (res[key] == 'wechatpay') {
						// 	this.bankTypeArr.key.push(res[key]);
						// 	this.bankTypeArr.value.push('微信');
						// }
					}
				}
			});
		},
		getShopBindQrcode(data = '') {
			this.$api.sendRequest({
				url: '/shopapi/apply/shopBindQrcode',
				success: res => {
					if (res.code == 0) {
						if (data && data == 'refresh') {
							this.colseTime = setInterval(() => {
								this.checkShopBind();
							}, 1000);
						}
						this.shopBindKey = res.data.key;
						this.wxCode = res.data.qrcode.replace(/[\r\n]/g, '');
					}
				}
			});
		},
		// checkShopBind() {
		// 	this.$api.sendRequest({
		// 		url: '/shopapi/apply/checkShopBind',
		// 		data: { key: this.shopBindKey },
		// 		success: res => {
		// 			if (res.code == 0) {
		// 				this.$util.showToast({
		// 					title: '绑定成功'
		// 				});
		// 				this.infoData.settlement_bank_account_number = res.data.openid;
		// 				this.infoData.settlement_bank_address = res.data.userinfo.nickName;
		// 				clearInterval(this.colseTime);
		// 				this.colseTime = 'null';
		// 			}
		// 		}
		// 	});
		// },
		bankTypeChange(e) {
			if (this.bankTypeArr.value.length == 0) return;
			this.selectBankType.id = e.detail.value;
			this.selectBankType.key = this.bankTypeArr.key[e.detail.value];
			this.selectBankType.value = this.bankTypeArr.value[e.detail.value];
			this.infoData.bank_type = parseInt(e.detail.value) + 1;

			//重置
			this.infoData.settlement_bank_account_name = '';
			this.infoData.settlement_bank_account_number = '';
			this.infoData.settlement_bank_name = '';
			this.infoData.settlement_bank_address = '';

			// if (this.selectBankType.key == 'wechatpay') {
			// 	this.colseTime = setInterval(() => {
			// 		this.checkShopBind();
			// 	}, 1000);
			// } else if (this.selectBankType.key != 'wechatpay' && this.colseTime != 'null') {
			// 	clearInterval(this.colseTime);
			// 	this.colseTime = 'null';
			// }
		},
		vertify() {
			let rule = [
				{ name: 'bank_account_name', checkType: 'required', errorMsg: '请输入银行开户名' },
				{ name: 'bank_account_number', checkType: 'required', errorMsg: '请输入开户银行账号' },
				{ name: 'bank_name', checkType: 'required', errorMsg: '请输入开户银行支行名称' },
				{ name: 'bank_address', checkType: 'required', errorMsg: '请输入开户银行所在地' },
				{ name: 'bank_code', checkType: 'required', errorMsg: '请输入支行联行号' },
				{ name: 'bank_address', checkType: 'required', errorMsg: '请输入开户银行所在地' },
				{ name: 'bank_name', checkType: 'required', errorMsg: '请输入开户银行支行名称' },
				{ name: 'bank_address', checkType: 'required', errorMsg: '请输入开户银行所在地' }
			];

			if (this.selectBankType.key == 'alipay') {
				this.infoData.settlement_bank_name = '';
				this.infoData.settlement_bank_address = '';
				rule.push({ name: 'settlement_bank_account_name', checkType: 'required', errorMsg: '请输入用户真实姓名' });
				rule.push({ name: 'settlement_bank_account_number', checkType: 'required', errorMsg: '请输入支付宝账号' });
			} else if (this.selectBankType.key == 'bank') {
				rule.push({ name: 'settlement_bank_account_name', checkType: 'required', errorMsg: '请输入结算开户名' });
				rule.push({ name: 'settlement_bank_account_number', checkType: 'required', errorMsg: '请输入结算银行账号' });
				rule.push({ name: 'settlement_bank_name', checkType: 'required', errorMsg: '请输入结算开户银行支行名称' });
				rule.push({ name: 'settlement_bank_address', checkType: 'required', errorMsg: '请输入结算开户银行所在地' });
			}
			//  else if (this.selectBankType.key == 'wechatpay') {
			// 	rule.push({ name: 'settlement_bank_account_name', checkType: 'required', errorMsg: '请输入用户真实姓名' });
			// 	rule.push({ name: 'settlement_bank_account_number', checkType: 'required', errorMsg: '请绑定微信' });
			// 	rule.push({ name: 'settlement_bank_name', checkType: 'required', errorMsg: '请输入微信账号' });
			// 	rule.push({ name: 'settlement_bank_address', checkType: 'required', errorMsg: '请输入微信昵称' });
			// }

			let checkRes = validate.check(this.infoData, rule);
			if (checkRes) {
				return true;
			} else {
				this.$util.showToast({ title: validate.error });
				return false;
			}
		},
		toPrevious() {
			this.$util.redirectTo('/pages/apply/openinfo');
		},
		toNext() {
			if (this.vertify()) {
				uni.setStorage({
					key: 'bankInfo',
					data: JSON.stringify(this.infoData)
				});
				this.$util.redirectTo('/pages/apply/storeInfo');
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
.company-info,
.personage-info {
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
		}
		.input-img-wrap {
			display: flex;
			flex-direction: column;
		}
		.input-img {
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 40rpx 0 10rpx;
			width: 200rpx;
			height: 200rpx;
			border: 2rpx dashed $color-disabled;
			.iconfont {
				font-size: 68rpx;
				color: $color-disabled;
			}
			image {
				width: 200rpx;
				height: 200rpx;
			}
		}
		.anew-load {
			text-align: center;
			margin-bottom: 30rpx;
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
