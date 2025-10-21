<template>
	<view class="withdrawal iphone-safe-area">
		<view class="dl">
			<view class="dt">店铺名称</view>
			<view class="dd">{{ base_info.site_name }}</view>
		</view>
		<view class="dl">
			<view class="dt">联系人</view>
			<view class="dd">{{ base_info.name }}</view>
		</view>
		<view class="dl">
			<view class="dt">联系电话</view>
			<view class="dd">{{ base_info.mobile }}</view>
		</view>
		<view class="dl">
			<view class="dt">账户类型</view>
			<view class="dd">
				<block v-if="base_info.bank_type == 1">银行</block>
				<block v-else-if="base_info.bank_type == 3">微信</block>
				<block v-else>支付宝</block>
			</view>
		</view>
		<block v-if="base_info.bank_type == 1">
			<view class="dl">
				<view class="dt">账户名称</view>
				<view class="dd">{{ base_info.settlement_bank_name }}</view>
			</view>
			<view class="dl">
				<view class="dt">提现账号</view>
				<view class="dd">{{ base_info.settlement_bank_account_number }}</view>
			</view>
			<view class="dl">
				<view class="dt">开户名</view>
				<view class="dd">{{ base_info.settlement_bank_account_name }}</view>
			</view>
		</block>
		<block v-if="base_info.bank_type == 3">
			<view class="dl">
				<view class="dt">微信昵称</view>
				<view class="dd">{{ base_info.settlement_bank_address }}</view>
			</view>
			<view class="dl">
				<view class="dt">微信号</view>
				<view class="dd">{{ base_info.settlement_bank_name }}</view>
			</view>
		</block>
		<block v-else>
			<view class="dl">
				<view class="dt">支付宝用户名</view>
				<view class="dd">{{ base_info.settlement_bank_account_name }}</view>
			</view>
			<view class="dl">
				<view class="dt">支付宝账号</view>
				<view class="dd">{{ base_info.settlement_bank_account_number }}</view>
			</view>
		</block>
		<view class="dl">
			<view class="dt">提现金额</view>
			<view class="dd">{{ base_info.money }}元</view>
		</view>
		<view class="dl">
			<view class="dt">状态</view>
			<view class="dd">
				<block v-if="base_info.status == 0">
					待审核
				</block>
				<block v-else-if="base_info.status == 1">
					待转账
				</block>
				<block v-else-if="base_info.status == 2">
					已转账
				</block>
				<block v-else-if="base_info.status == -1">
					已拒绝
				</block>
			</view>
		</view>
		<view class="dl">
			<view class="dt">申请时间</view>
			<view class="dd">{{ $util.timeStampTurnTime(base_info.apply_time) }}</view>
		</view>
		<block v-if="base_info.status == 2">
			<view class="dl">
				<view class="dt">转账时间</view>
				<view class="dd">{{ $util.timeStampTurnTime(base_info.payment_time) }}</view>
			</view>
			<view class="dl">
				<view class="dt">转账凭证</view>
				<view class="dd"><image class="img" :src="$util.img(base_info.paying_money_certificate)" @click="previewMedia()" /></view>
			</view>
			<view class="dl">
				<view class="dt">转账凭证说明</view>
				<view class="dd">{{ base_info.paying_money_certificate_explain ? base_info.paying_money_certificate_explain : '暂无' }}</view>
			</view>
		</block>
		<view class="dl">
			<view class="dt">是否结算周期</view>
			<view class="dd">{{ base_info.is_period == 0 ? '否' : '是' }}</view>
		</view>
		<view class="dl">
			<view class="dt">结算周期名称</view>
			<view class="dd">{{ base_info.period_name }}</view>
		</view>
		<view class="dl">
			<view class="dt">备注</view>
			<view class="dd">{{ base_info.memo }}</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			id: 0,
			base_info: {}
		};
	},
	onLoad(option) {
		if (option.id) {
			this.id = option.id;
			this.getBaseInfo();
		} else {
			this.$util.goBack('/pages/property/withdraw/list');
		}
	},
	onShow() {
		if (!this.$util.checkToken('/pages/property/withdraw/detail?id=' + this.id)) return;
	},
	methods: {
		getBaseInfo() {
			this.$api.sendRequest({
				url: '/shopapi/shopwithdraw/detail',
				data: {
					id: this.id
				},
				success: res => {
					if (res.code >= 0) {
						this.base_info = res.data;
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		previewMedia() {
			var paths = [this.base_info.paying_money_certificate];
			uni.previewImage({
				current: 0,
				urls: paths
			});
		}
	}
};
</script>

<style lang="scss">
.withdrawal {
	padding: $padding 0;
	border-radius: $border-radius;
	overflow: hidden;
	margin: 0 $margin-both;
	.dl {
		display: flex;
		justify-content: space-between;
		padding: 30rpx;
		border-bottom: 1px solid $color-line;
		background-color: #fff;
		&:last-child {
			border-bottom: 0 !important;
		}
		.dt{
			min-width: 200rpx;
		}
		.dd {
			flex:1;
			text-align: right;
			word-break:break-all;
			.img {
				height: 80rpx;
				width: 80rpx;
			}
		}
	}
}
</style>
