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
							<view class="color-tip font-size-tag">平台设置店铺结算周期后，系统会按照设置定期进行结算</view>
						</view>
						<view class="color-tip font-size-tag margin-top">
							<text class="margin-right">●</text>
							<view class="color-tip font-size-tag">结算周期分为按天、按周、按月。按天是指每天0点开始，按周是指每周一0点开始，按月是指每月一号0点开始</view>
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
						<view class="withdrawal_content" :class="{ 'margin-top': index > 0 }" v-for="(item, index) in dashboard_list" :key="index">
							<view class="withdrawal_list" @click="toDetail(item.id)">
								<view class="withdrawal_list_title">
									<view class="tip color-tip">{{ item.settlement_no }}</view>
									<view class="color-base-text">查看</view>
									<!-- <view class="color-tip"><text class="iconfont iconreview"></text></view> -->
								</view>
								<view class="withdrawal_list_info">
									<view class="withdrawal_list_base">
										<view class="tip">订单总额</view>
										<view>￥{{ item.order_money }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">店铺金额</view>
										<view>￥{{ item.shop_moneys }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">平台抽成</view>
										<view>￥{{ item.platform_money }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">佣金金额</view>
										<view>{{ item.commission }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">账期开始时间</view>
										<view>{{ $util.timeStampTurnTime(item.period_start_time) }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">账期结束时间</view>
										<view>{{ $util.timeStampTurnTime(item.period_end_time) }}</view>
									</view>
								</view>
							</view>
						</view>
					</block>
					<ns-empty v-else text="暂无店铺结算数据"></ns-empty>
				</view>
			</block>
		</mescroll-uni>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			tipShow: true,
			dateObj: {
				startDate: '',
				endDate: ''
			},
			base_info: {
				shop_info: {
					shop_open_fee: 0
				}
			},
			dashboard_list: []
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/property/settlement/list')) return;
		this.getBaseInfo();
	},
	methods: {
		toDetail(val){
			this.$util.redirectTo('/pages/property/settlement/detail', { id: val })
		},
		//监听
		bindTypeChange(e) {
			(this.dateObj = {
				startDate: '',
				endDate: ''
			}),
				(this.selectTypeCurr = e.target.value);
			this.$refs.mescroll.refresh();
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
		getBaseInfo() {
			this.$api.sendRequest({
				url: '/shopapi/account/index',
				success: res => {
					if (res.code >= 0) {
						this.base_info = res.data;
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		getList(mescroll) {
			var data = {
				page: mescroll.num,
				page_size: mescroll.size
			};
			if (this.dateObj.startDate) {
				data.start_time = this.$util.timeTurnTimeStamp(this.dateObj.startDate);
			}
			if (this.dateObj.endDate) {
				data.end_time = this.$util.timeTurnTimeStamp(this.dateObj.endDate);
			}
			this.$api.sendRequest({
				url: '/shopapi/settlement/lists',
				data: data,
				success: res => {
					let newArr = [];
					let msg = res.message;
					if (res.code == 0 && res.data) {
						if (res.data.page_count == 0) {
							this.emptyShow = true;
						}
						newArr = res.data.list;
						for (var index in newArr) {
							var shop_price =
								parseFloat(newArr[index].shop_money) -
								parseFloat(newArr[index].refund_shop_money) -
								parseFloat(newArr[index].commission) +
								parseFloat(newArr[index].platform_coupon_money) -
								parseFloat(newArr[index].refund_platform_coupon_money);
							var shop_moneys = 0;
							if (shop_price) {
								shop_moneys = shop_price.toFixed(2);
							} else {
								shop_moneys = shop_moneys.toFixed(2);
							}
							newArr[index].shop_moneys = shop_moneys;
						}
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
</style>
