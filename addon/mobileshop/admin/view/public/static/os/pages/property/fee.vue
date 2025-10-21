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
							<view class="color-tip font-size-tag">店铺到期30日内可以申请续签</view>
						</view>
						<view class="color-tip font-size-tag margin-top">
							<text class="margin-right">●</text>
							<view class="color-tip font-size-tag">请认准官方收款账户，支付凭据上传之后请联系官方客服人员</view>
						</view>
					</view>
				</view>
				<view class="withdrawal_item margin-top">
					<view class="withdrawal_title">
						<text class="line color-base-bg margin-right"></text>
						入驻费用
					</view>
					<view class="withdrawal_content">
						<view class="flex_two">
							<view class="flex_two-item">
								<view class="tip">入驻费用(元)</view>
								<view class="num">{{ base_info.shop_open_fee }}</view>
							</view>
							<view class="flex_two-item">
								<view class="tip">保证金(元)</view>
								<view class="num">{{ base_info.shop_baozhrmb }}</view>
							</view>
							<view class="flex_one-item .border_none">
								<view class="tip">
									到期时间
									<block v-if="base_info.is_reopen == 2">
										<text class="margin-left font-size-tag color-base-text" @click="$util.redirectTo('/pages/apply/shopset')" v-if="is_reopen == 1">
											申请续签
										</text>
										<text class="margin-left font-size-tag color-base-text" @click="$util.redirectTo('/pages/apply/payinfo')" v-else>申请续签</text>
									</block>
									<text class="margin-left font-size-tag color-base-text" @click="$util.redirectTo('/pages/property/reopen/list')">续签记录</text>
								</view>
								<view class="num">{{ base_info.expire_time }}</view>
							</view>
						</view>
					</view>
				</view>

				<view class="withdrawal_item">
					<block v-if="dashboard_list.length > 0">
						<view class="withdrawal_content margin-top" v-for="(item, index) in dashboard_list" :key="index">
							<view class="withdrawal_list">
								<view class="withdrawal_list_title">
									<view class="tip color-tip">{{ item.account_no }}</view>
								</view>
								<view class="withdrawal_list_info">
									<view class="withdrawal_list_base">
										<view class="tip">缴费金额（元）</view>
										<view>{{ item.money }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">费用类型</view>
										<view>{{ item.type_name }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">创建时间</view>
										<view>{{ $util.timeStampTurnTime(item.create_time) }}</view>
									</view>
								</view>
							</view>
						</view>
					</block>
					<ns-empty v-else text="暂无入驻费用数据"></ns-empty>
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
			base_info: {},
			is_reopen: '',
			dashboard_list: [],
			tipShow: true
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/property/fee')) return;
		this.getBaseInfo();
	},
	methods: {
		getBaseInfo() {
			this.$api.sendRequest({
				url: '/shopapi/account/feeStat',
				success: res => {
					if (res.code >= 0) {
						this.base_info = res.data.apply_data;
						this.is_reopen = res.data.is_reopen;
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
			this.$api.sendRequest({
				url: '/shopapi/account/fee',
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
@import './css/common.scss';
</style>
