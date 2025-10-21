<template>
	<view class="container">
		<block v-if="bankAccountInfo">
			<block v-if="bankAccountInfo.bank_type == 1">
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">提现账户类型</text>
						<view class="tx-bank">银行卡</view>
					</view>
				</view>
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">银行开户名</text>
						<view class="tx-bank">{{ bankAccountInfo.settlement_bank_account_name }}</view>
					</view>
				</view>
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">银行账号</text>
						<view class="tx-bank">{{ bankAccountInfo.settlement_bank_account_number }}</view>
					</view>
				</view>
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">开户银行支行名称</text>
						<view class="tx-bank">{{ bankAccountInfo.settlement_bank_name }}</view>
					</view>
				</view>
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">开户银行所在地</text>
						<view class="tx-bank">{{ bankAccountInfo.settlement_bank_address }}</view>
					</view>
				</view>
			</block>
			<block v-else-if="bankAccountInfo.bank_type == 3">
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">提现账户类型</text>
						<view class="tx-bank">微信</view>
					</view>
				</view>
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">微信昵称</text>
						<view class="tx-bank">{{ bankAccountInfo.settlement_bank_address }}</view>
					</view>
				</view>
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">微信号</text>
						<view class="tx-bank">{{ bankAccountInfo.settlement_bank_name }}</view>
					</view>
				</view>
			</block>
			<block v-else>
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">提现账户类型</text>
						<view class="tx-bank">支付宝</view>
					</view>
				</view>
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">支付宝用户名</text>
						<view class="tx-bank">{{ bankAccountInfo.settlement_bank_account_name }}</view>
					</view>
				</view>
				<view class="bank-account-wrap">
					<view class="tx-wrap">
						<text class="tx-to">支付宝账户</text>
						<view class="tx-bank">{{ bankAccountInfo.settlement_bank_account_number }}</view>
					</view>
				</view>
			</block>
		</block>
		<view class="empty-box"></view>
		<view class="withdraw-wrap">
			<view class="withdraw-wrap-title">提现金额：</view>
			<view class="money-wrap">
				<text class="unit">￥</text>
				<input type="digit" class="withdraw-money" v-model="withdrawMoney" />
				<view class="delete" @click="remove" v-if="withdrawMoney"><image :src="$util.img('upload/uniapp/member/apply_withdrawal/close.png')" mode="widthFix"></image></view>
			</view>
			<view class="bootom">
				<view>
					<text class="color-tip">可提现余额：</text>
					<text class="color-base-text">￥{{ baseInfo.account | moneyFormat }}</text>
					<!-- <text class="all-tx color-base-text" @click="allTx">全部提现</text> -->
				</view>
			</view>
			<view class="desc">
				<text>最低提现：</text>
				<text class="money color-base-text">￥{{ baseInfo.shop_withdraw_config.min_withdraw | moneyFormat }}</text>
				<text>最高提现：</text>
				<text class="money color-base-text">￥{{ baseInfo.shop_withdraw_config.max_withdraw | moneyFormat }}</text>
			</view>
		</view>

		<button type="primary" :disabled="withdrawMoney == '' || withdrawMoney == 0" @click="withdraw()">提现</button>

		<!-- <view class="recoend" @click="toWithdrawal">
			<view class="recoend-con">提现记录</view>
		</view> -->
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			baseInfo: {
				shop_withdraw_config: {
					min_withdraw: 0
				}
			},
			bankAccountInfo: {},
			withdrawMoney: '',
			repeatFlag: false
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/property/withdraw/index')) return;
		this.getWithdrawInfo();
	},
	methods: {
		withdraw() {
			if (!this.verify()) return;
			if (this.repeatFlag) return;
			this.repeatFlag = true;
			this.$api.sendRequest({
				url: '/shopapi/shopwithdraw/apply',
				data: {
					apply_money: this.withdrawMoney
				},
				success: res => {
					if (res.code >= 0) {
						this.$util.showToast({
							title: '申请成功，等待审核'
						});
						this.withdrawMoney = '';
						this.$util.redirectTo('/pages/property/withdraw/list',{},'redirectTo');
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
					this.getWithdrawInfo();
					this.repeatFlag = false;
				}
			});
		},
		/**
		 * 获取提现信息
		 */
		getWithdrawInfo() {
			this.$api.sendRequest({
				url: '/shopapi/account/index',
				success: res => {
					if (res.code >= 0 && res.data) {
						this.baseInfo = res.data;
						this.bankAccountInfo = res.data.shop_cert_info;
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		// 银行账号信息
		getBankAccountInfo() {
			this.$api.sendRequest({
				url: '/api/memberbankaccount/defaultinfo',
				success: res => {
					if (res.code >= 0 && res.data) {
						this.bankAccountInfo = res.data;
					}
				}
			});
		},
		// 删除提现金额
		remove() {
			this.withdrawMoney = '';
		},
		verify() {
			if (this.withdrawMoney == '' || this.withdrawMoney == 0 || isNaN(parseFloat(this.withdrawMoney))) {
				this.$util.showToast({
					title: '请输入提现金额'
				});
				return false;
			}
			if (parseFloat(this.withdrawMoney) > parseFloat(this.baseInfo.account)) {
				this.$util.showToast({
					title: '提现金额超出可提现金额'
				});
				return false;
			}
			if (parseFloat(this.withdrawMoney) < parseFloat(this.baseInfo.shop_withdraw_config.min_withdraw)) {
				this.$util.showToast({
					title: '提现金额小于最低提现金额'
				});
				return false;
			}
			if (parseFloat(this.withdrawMoney) > parseFloat(this.baseInfo.shop_withdraw_config.max_withdraw)) {
				this.$util.showToast({
					title: '提现金额不能高于最高提现金额'
				});
				return false;
			}
			return true;
		}
	},
	filters: {
		/**
		 * 金额格式化输出
		 * @param {Object} money
		 */
		moneyFormat(money) {
			return parseFloat(money).toFixed(2);
		}
	}
};
</script>

<style lang="scss">
.container {
	width: 100vw;
	height: 100vh;
	background: #fff;
}

.empty-box {
	height: 40rpx;
}

.bank-account-wrap {
	margin: 0 $margin-both;
	padding: 20rpx 30rpx;
	border-bottom: 1px solid #f7f7f7;
	position: relative;
	&:last-child {
		border-bottom: 0;
	}
	.tx-wrap {
		display: flex;
		justify-content: space-between;

		.tx-bank {
			flex: 1;
			margin-left: 10rpx;
			text-align: right;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}

		.tx-img {
			position: absolute;
			right: 100rpx;
			top: 50%;
			transform: translateY(-50%);
			width: 40rpx;
			height: 40rpx;

			image {
				width: 100%;
				height: 100%;
			}
		}
	}

	.iconfont {
		position: absolute;
		right: 40rpx;
		top: 50%;
		transform: translateY(-50%);
	}
}

.withdraw-wrap {
	margin: 0 20rpx;
	padding: 30rpx;
	border-radius: 16rpx;
	box-shadow: rgba(110, 110, 110, 0.09) 0 0 20rpx 0;

	.money-wrap {
		padding: 20rpx 0;
		border-bottom: 1px solid $color-line;
		display: flex;

		.unit {
			font-size: 60rpx;
			line-height: 1;
		}

		.withdraw-money {
			height: 60rpx;
			line-height: 1;
			min-height: 60rpx;
			padding-left: 20rpx;
			font-size: 60rpx;
			flex: 1;
			font-weight: bolder;
		}

		.delete {
			width: 40rpx;
			height: 40rpx;

			image {
				width: 100%;
				height: 100%;
			}
		}
	}

	.bootom {
		display: flex;
		padding-top: 20rpx;

		text {
			line-height: 1;
			flex: 2;
		}

		.all-tx {
			padding-left: 10rpx;
		}
	}
}
button {
	margin-top: 60rpx;
}
.recoend {
	margin-top: 40rpx;

	.recoend-con {
		text-align: center;
	}
}

.desc {
	font-size: $font-size-tag;
	color: $color-tip;
	.money:nth-of-type(2) {
		margin-right: 20rpx;
	}
}
</style>
