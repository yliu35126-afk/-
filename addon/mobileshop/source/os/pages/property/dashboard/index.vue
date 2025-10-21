<template>
	<view class="withdrawal">
		<mescroll-uni ref="mescroll" @getData="getList" :fixed="!1">
			<block slot="list">
				<view class="withdrawal_item margin-top">
					<view class="withdrawal_title">
						<text class="line color-base-bg margin-right"></text>
						<text>账户概况</text>
					</view>
					<view class="withdrawal_content">
						<view class="flex_two">
							<view class="flex_two-item">
								<view class="tip">店铺总收入(元)</view>
								<view class="num">{{ base_info.total }}</view>
							</view>
							<view class="flex_two-item">
								<view class="tip">
									可用余额(元)
									<text class="margin-left font-size-tag color-base-text" @click="$util.redirectTo('/pages/property/withdraw/index')">提现</text>
								</view>
								<view class="num">{{ base_info.account }}</view>
							</view>
							<view class="flex_two-item">
								<view class="tip">
									待结算(元)
									<text class="margin-left font-size-tag color-base-text" @click="$util.redirectTo('/pages/property/dashboard/orderlist')">查看明细</text>
								</view>
								<view class="num">{{ base_info.order_apply }}</view>
							</view>
							<view class="flex_two-item">
								<view class="tip">入驻费用(元)</view>
								<view class="num">{{ base_info.shop_info.shop_open_fee }}</view>
							</view>
							<view class="flex_two-item ">
								<view class="tip">店铺保证金(元)</view>
								<view class="num">{{ base_info.shop_info.shop_baozhrmb }}</view>
							</view>
							<view class="flex_two-item">
								<view class="tip">
									已提现(元)
									<text class="margin-left font-size-tag color-base-text" @click="$util.redirectTo('/pages/property/withdraw/list')">提现记录</text>
								</view>
								<view class="num">{{ base_info.account_withdraw }}</view>
							</view>
							<view class="flex_two-item border_none">
								<view class="tip">提现中(元)</view>
								<view class="num">{{ base_info.account_withdraw_apply }}</view>
							</view>
						</view>
					</view>
				</view>
				<view class="search">
					<view class="search_select">
						<picker @change="bindTypeChange" :value="selectTypeCurr" :range="selectType" range-key="type_name">
							<view class="uni-input font-size-tag">{{ selectType[selectTypeCurr].type_name }}</view>
						</picker>
						<text class="iconfont iconiconangledown"></text>
					</view>
					
					<view class="search_input margin-left">
						<view class="search_btn color-tip" @click="search"><text class="iconfont iconsousuo"></text></view>
						<view class="date">
							<picker mode="date" @change="bindStartDateChange" class="start">
								<view class="uni-input font-size-tag">{{ dateObj.startDate ? dateObj.startDate : '开始时间' }}</view>
							</picker>
							<text class="">-</text>
							<picker mode="date" @change="bindEndDateChange" class="end">
								<view class="uni-input font-size-tag">{{ dateObj.endDate ? dateObj.endDate : '结束时间' }}</view>
							</picker>
							<text class="clear iconfont iconqingkong1" @click="clearTime"></text>
						</view>
					</view>
				</view>

				<view class="withdrawal_item">
					<block v-if="dashboard_list.length > 0">
						<view class="withdrawal_content" :class="{ 'margin-top': index > 0 }" v-for="(item, index) in dashboard_list" :key="index">
							<view class="withdrawal_list">
								<view class="withdrawal_list_title">
									<view class="tip color-tip">{{ item.account_no }}</view>
								</view>
								<view class="withdrawal_list_info">
									<view class="withdrawal_list_base">
										<view class="tip">收支来源</view>
										<view>{{ item.type_name }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">金额（元）</view>
										<view :class="{ red: item.account_data > 0, green: item.account_data < 0 }">
											{{ item.account_data > 0 ? '+' : '' }}{{ item.account_data }}
										</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">收支类型</view>
										<view>{{ item.account_data >= 0 ? '收入' : '支出' }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">时间</view>
										<view>{{ $util.timeStampTurnTime(item.create_time) }}</view>
									</view>
									<view class="margin-top color-tip mark">说明：{{ item.remark }}</view>
								</view>
							</view>
						</view>
					</block>
					<ns-empty v-else text="暂无账户数据"></ns-empty>
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
			selectType: [
				{
					type: 0,
					type_name: '全部'
				},
				{
					type: 1,
					type_name: '收入'
				},
				{
					type: 2,
					type_name: '支出'
				}
			],
			isFiexd: {
				fiexd: false,
				top: ''
			},
			dateObj: {
				startDate: '',
				endDate: ''
			},
			selectTypeCurr: 0,
			base_info: {
				shop_info: {
					shop_open_fee: 0
				}
			},
			dashboard_list: []
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/property/dashboard/index')) return;
		this.getBaseInfo();
	},
	methods: {
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
				page_size: mescroll.size,
				type: this.selectTypeCurr
			};
			if (this.dateObj.startDate) {
				data.start_time = this.$util.timeTurnTimeStamp(this.dateObj.startDate);
			}
			if (this.dateObj.endDate) {
				data.end_time = this.$util.timeTurnTimeStamp(this.dateObj.endDate);
			}
			if(data.start_time && data.end_time && data.start_time > data.end_time){
				this.$util.showToast({
					title: '开始时间不能大于结束时间'
				});
				this.dateObj.endDate = '';
				return false;
			}
			this.$api.sendRequest({
				url: '/shopapi/account/dashboard',
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
</style>
