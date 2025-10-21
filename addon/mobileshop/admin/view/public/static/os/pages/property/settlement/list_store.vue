<template>
	<view class="withdrawal">
		<mescroll-uni ref="mescroll" @getData="getList" :fixed="!1">
			<block slot="list">
				<view class="operate_tip">
					<view @click="tipShow = !tipShow">
						操作提示
						<text class="iconfont iconiconangledown"></text>
					</view>
					<view class="operate_content" v-if="tipShow">
						<view class="color-tip font-size-tag margin-top">
							<text class="margin-right">●</text>
							<view class="color-tip font-size-tag">页面展示商家所有的门店进行的结算操作</view>
						</view>
						<view class="color-tip font-size-tag margin-top">
							<text class="margin-right">●</text>
							<view class="color-tip font-size-tag">商家可在此查看结算详情以及结算操作</view>
						</view>
					</view>
				</view>
				<view class="search">
					<view class="search_input">
						<view class="search_btn color-tip" @click="search"><text class="iconfont iconsousuo"></text></view>
						<view class="date">
							<picker mode="date" @change="bindStartDateChange" class="margin-right">
								<view class="uni-input font-size-tag">{{ dateObj.startDate ? dateObj.startDate : '开始时间' }}</view>
							</picker>
							<text class="margin-right">-</text>
							<picker mode="date" @change="bindEndDateChange">
								<view class="uni-input font-size-tag">{{ dateObj.endDate ? dateObj.endDate : '结束时间' }}</view>
							</picker>
							<text class="clear iconfont iconqingkong1" @click="clearTime"></text>
						</view>
					</view>
				</view>
				<view class="withdrawal_item">
					<block v-if="dashboard_list.length > 0">
						<view class="withdrawal_content" :class="{ 'margin-top': index > 0 }" v-for="(item, index) in dashboard_list" :key="index" >
							<view class="withdrawal_list" @click="toDetail(item.id)">
								<view class="withdrawal_list_title">
									<view class="tip color-tip">{{ item.settlement_no }}</view>
									<view class="color-base-text">查看</view>
									<!-- <view class="color-tip"><text class="iconfont iconreview"></text></view> -->
								</view>
								<view class="withdrawal_list_info">
									<view class="withdrawal_list_base">
										<view class="tip">门店结算</view>
										<view>￥{{ item.order_money }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">订单总额</view>
										<view>￥{{ item.order_money * 1 + item.offline_order_money * 1 - item.refund_shop_money * 1 - item.offline_refund_money * 1 }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">线上结算金额</view>
										<view class="color-base-text">
											￥{{
												(
													parseFloat(item.shop_money) -
													parseFloat(item.refund_shop_money) -
													parseFloat(item.commission) +
													parseFloat(item.platform_coupon_money) -
													parseFloat(item.refund_platform_coupon_money)
												).toFixed(2)
											}}
										</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">线下结算金额</view>
										<view class="color-base-text">
											￥{{
												(
													parseFloat(item.offline_order_money) -
													parseFloat(item.offline_refund_money) +
													parseFloat(item.offline_platform_coupon_money) -
													parseFloat(item.offline_refund_platform_coupon_money)
												).toFixed(2)
											}}
										</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">结算开始时间</view>
										<view>{{ $util.timeStampTurnTime(item.start_time) }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">结算结束时间</view>
										<view>{{ $util.timeStampTurnTime(item.end_time) }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">是否结算</view>
										<view>{{ item.is_settlement ? '已结算' : '未结算' }}</view>
									</view>
								</view>
								<view class="operate color-line-border">
									<button type="primary" size="mini" plain="true" v-if="item.is_settlement == 0" @click.stop="settle_accounts(item.id)">结算</button>
								</view>
							</view>
						</view>
					</block>
					<ns-empty v-else text="暂无门店结算数据"></ns-empty>
				</view>
			</block>
		</mescroll-uni>
		<loading-cover ref="loadingCover"></loading-cover>
		<uni-popup ref="settleaccounts">
			<view class="pop-wrap" @touchmove.prevent.stop>
				<view class="title font-size-toolbar">结算备注
					<view class="close color-tip" @click="closePopup('settleaccounts')"><text class="iconfont iconclose"></text></view>
				</view>
				<view class="textarea color-line-border"><textarea placeholder="请输入结算备注" placeholder-class="font-size-base" v-model="remark"></textarea></view>
				<view class="action-btn">
					<view class="line" @click="closePopup('settleaccounts')">取消</view>
					<view class="color-line-border color-base-text" @click="goSettleAccounts">确定</view>
				</view>
				<!-- <view class="settleaccounts_btn">
					
					<view class="now_settle_btn color-base-bg color-base-border-top" @click="goSettleAccounts">结算</view>
					<view class="cancle_settle_btn color-line-border" @click="closePopup('settleaccounts')">取消</view>
				</view> -->
			</view>
		</uni-popup>
	</view>
</template>

<script>
export default {
	data() {
		return {
			id: 0,
			tipShow: true,
			base_info: {
				shop_info: {
					shop_open_fee: 0
				}
			},
			dashboard_list: [],
			dateObj: {
				startDate: '',
				endDate: ''
			},
			settleCurr: 0,
			remark: ''
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/property/settlement/list_store')) return;
	},
	onLoad(option) {
		if (option.id) {
			this.id = option.id;
		}
	},
	methods: {
		toDetail(val){
			this.$util.redirectTo('/pages/property/settlement/detail_store', { id: val})
		},
		bindStartDateChange(e) {
			this.dateObj.startDate = e.detail.value;
		},
		bindEndDateChange(e) {
			this.dateObj.endDate = e.detail.value;
		},
		//清空时间
		clearTime(){
			this.dateObj.startDate = '';
			this.dateObj.endDate = '';
		},
		search() {
			this.$refs.mescroll.refresh();
		},
		//结算
		settle_accounts(e) {
			this.settleCurr = e;
			this.$refs.settleaccounts.open();
		},
		//结算
		goSettleAccounts() {
			var data = {
				settlement_id: this.settleCurr,
				remark: this.remark
			};
			if (!this.remark) {
				this.$util.showToast({
					title: '请输入结算备注'
				});
				return;
			}
			this.$api.sendRequest({
				url: '/store/shopapi/settlement/settlement',
				data: data,
				success: res => {
					var msg = res.message;
					if (res.code == 0 && res.data) {
						msg = '结算成功';
						this.closePopup('settleaccounts');
						this.$refs.mescroll.refresh();
					}
					this.$util.showToast({
						title: msg
					});
				}
			});
		},
		closePopup(e) {
			this.$refs[e].close();
		},
		getList(mescroll) {
			var data = {
				page: mescroll.num,
				page_size: mescroll.size,
				id: this.id
			};
			if (this.dateObj.startDate) {
				data.start_time = this.$util.timeTurnTimeStamp(this.dateObj.startDate);
			}
			if (this.dateObj.endDate) {
				data.end_time = this.$util.timeTurnTimeStamp(this.dateObj.endDate);
			}
			if(data.start_time && data.end_time && data.end_time < data.start_time){
				this.$util.showToast({
					title: '开始时间不能大于结束时间'
				});
				this.dateObj.endDate = '';
				return false;
			}
			
			this.$api.sendRequest({
				url: '/store/shopapi/settlement/index',
				data: data,
				success: res => {
					let newArr = [];
					let msg = res.message;
					if (res.code == 0 && res.data) {
						if (res.data.page_count == 0) {
							this.emptyShow = true;
						}
						newArr = res.data.list;
					} else {
						this.$util.showToast({
							title: msg
						});
					}
					mescroll.endSuccess(newArr.length);
					//设置列表数据
					if (mescroll.num == 1) this.dashboard_list = []; //如果是第一页需手动制空列表
					this.dashboard_list = this.dashboard_list.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		}
	}
};
</script>

<style lang="scss">
@import '../css/common.scss';
.input_tip {
	width: 80vw;
	.title {
		padding: $padding 30rpx;
	}
	
	.settleaccounts_btn {
		display: flex;
		justify-content: space-between;

		> view {
			flex: 1;
			text-align: center;
			padding: $padding;
			border-top: 1px solid;
			&.now_settle_btn {
				color: #fff;
			}
		}
	}
}
.pop-wrap {
	width: 80vw;
	.title {
		padding: $padding 30rpx;
		text-align: center;
		position: relative;
		.close {
			position: absolute;
			right: 30rpx;
			top: 20rpx;
			height: 60rpx;
			width: 60rpx;
		}
	}
	.textarea {
		margin: 0 $margin-both $margin-updown;
		// border-top: 1px solid;
		textarea {
			width: 100%;
			padding: $padding 0;
		}
	}
	.flex {
		display: flex;
		justify-content: space-between;
		margin: 0 $margin-both;
		padding: 30rpx 0;
		align-items: center;
		border-bottom: 1px solid $color-line;
		&.last_child {
			border-bottom: 0;
		}
		.flex_right {
			flex: 1;
			text-align: right;
		}
	}
	.action-btn {
		display: flex;
		justify-content: space-between;
		border-top: 1px solid $color-line;

		> view {
			flex: 1;
			text-align: center;
			padding: $padding;
			&.line {
				border-right: 1px solid $color-line;
			}
		}
	}
}

</style>
