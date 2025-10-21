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
				<view class="shop_base_info">
					<view class="shop_img">
						<image :src="shop_info.logo ? $util.img(shop_info.logo) : $util.img($util.getDefaultImage().default_headimg)" @error="imgError()" mode="aspectFit"></image>
					</view>
					<view class="shop_info">
						<view class="shop_title">
							{{ shop_info.site_name }}
							<text class="color-base-border color-base-text">{{ shop_info.group_name }}</text>
							<!-- <text @click="toCert">{{shop_info.cert_id == 0?'未认证':'已认证'}}</text> -->
						</view>
						<view class="shop_other_info">
							<text class="margin-right">主营行业：{{ shop_info.category_name }}</text>
							<text>到期时间：{{ shop_info.expire_time ? $util.timeStampTurnTime(shop_info.expire_time) : '永久有效' }}</text>
						</view>
					</view>
				</view>

				<view class="withdrawal_item">
					<block v-if="dashboard_list.length > 0">
						<view class="withdrawal_content" :class="{ 'margin-top': index > 0 }" v-for="(item, index) in dashboard_list" :key="index">
							<view class="withdrawal_list" @click="toDetail(item.id)">
								<view class="withdrawal_list_title">
									<view class="tip color-tip">{{ item.reopen_no }}</view>
									<view class="color-base-text">查看</view>
								</view>
								<view class="withdrawal_list_info">
									<view class="withdrawal_list_base">
										<view class="tip">续签时长（年）</view>
										<view class="color-base-text">{{ item.apply_year }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">缴费金额（元）</view>
										<view>{{ item.paying_amount }}</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">付款凭证</view>
										<view>
											<image
												class="img"
												:src="$util.img(item.paying_money_certificate)"
												mode="aspectFit"
												@click="previewMedia($util.img(item.paying_money_certificate))"
											/>
										</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">状态</view>
										<view>
											<block v-if="item.apply_state == 1">待审核</block>
											<block v-else-if="item.apply_state == -1">审核失败</block>
											<block v-else-if="item.apply_state == 2">审核成功</block>
										</view>
									</view>
									<view class="withdrawal_list_base">
										<view class="tip">时间</view>
										<view>{{ item.create_time ? $util.timeStampTurnTime(item.create_time) : '--' }}</view>
									</view>
								</view>
								<view class="operate color-line-border">
									<block v-if="item.apply_state == 1 || item.apply_state == 2">
										<button type="primary" size="mini" plain="true" @click.stop="delReopen(item.id, index)">删除</button>
									</block>
									<block v-else-if="item.apply_state == -1">
										<button type="primary" size="mini" plain="true" @click.stop="delReopen(item.id, index)">删除</button>
										<button type="primary" size="mini" plain="true" @click.stop="editReopen(item.id)">编辑</button>
									</block>
								</view>
							</view>
						</view>
					</block>
					<ns-empty v-else text="暂无续签数据"></ns-empty>
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
			dateObj: {
				startDate: '',
				endDate: ''
			},
			tipShow: true,
			base_info: {},
			shop_info: {},
			dashboard_list: []
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/property/reopen/list')) return;
		this.shop_info = uni.getStorageSync('shop_info') ? JSON.parse(uni.getStorageSync('shop_info')) : {};
		this.getBaseInfo();
	},
	methods: {
		//图片预览
		previewMedia(val) {
			var paths = [val];
			uni.previewImage({
				current: 0,
				urls: paths
			});
		},
		toDetail(val){
			this.$util.redirectTo('/pages/property/reopen/detail', { id: val })
		},
		editReopen(id) {
			this.$util.redirectTo('/pages/renew/apply', {
				id: id,
				back: '/pages/property/reopen/list'
			});
		},
		getBaseInfo() {
			this.$api.sendRequest({
				url: '/shopapi/shopwithdraw/info',
				success: res => {
					if (res.code >= 0) {
						this.base_info = res.data;
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
					uni.stopPullDownRefresh();
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
				url: '/shopapi/account/reopenList',
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
		},
		delReopen(id, index) {
			uni.showModal({
				title: '提示',
				content: '确定要删除续签申请记录吗？',
				success: res => {
					if (res.confirm) {
						this.$api.sendRequest({
							url: '/shopapi/shopreopen/deleteReopen',
							data: { id },
							success: res => {
								this.$util.showToast({
									title: res.message
								});
								this.dashboard_list.splice(index, 1);
							}
						});
					}
				}
			});
		},
		imgError(index) {
			this.shop_info.logo = this.$util.getDefaultImage().default_headimg;
			this.$forceUpdate();
		}
	}
};
</script>

<style lang="scss">
@import '../css/common.scss';

.withdrawal {
	padding: 30rpx 0;

	.shop_base_info {
		display: flex;
		align-items: center;
		margin: 0 $margin-both;
		padding: 30rpx $padding;
		border-radius: $border-radius;

		.shop_img {
			width: 80rpx;
			height: 80rpx;
			min-width: 80rpx;
			margin-right: $margin-updown;

			image {
				width: 100%;
				height: 100%;
			}
		}

		.shop_info {
			flex: 1;

			.shop_title {
				font-size: $font-size-toolbar;
				font-weight: 600;

				text {
					font-size: $font-size-goods-tag;
					margin-left: $margin-updown;
					border: 1px solid;
					border-radius: 2rpx;
					padding: 0 13rpx;
					line-height: 1;
					font-weight: 400;
					vertical-align: middle;
				}
			}

			.shop_other_info {
				margin-top: 10rpx;
				font-size: $font-size-tag;
			}
		}

		.weixincode {
			height: 40rpx;
			width: 40rpx;
			margin-left: $margin-updown;

			image {
				height: 100%;
				width: 100%;
			}
		}
	}

	.withdrawal_item {
		margin: 0 $margin-both;

		.withdrawal_title {
			font-size: $font-size-toolbar;
			font-weight: bold;

			.line {
				display: inline-block;
				height: 28rpx;
				width: 4rpx;
				border-radius: 4rpx;
			}
		}

		.withdrawal_content {
			background-color: #fff;
			border-radius: $border-radius;

			// padding:$padding 30rpx;
			&.margin-top {
				margin-top: $margin-both !important;
			}

			.flex_two {
				display: flex;
				flex-wrap: wrap;

				.flex_two-item {
					padding: 40rpx 30rpx;
					width: calc(50% - 60rpx - 1px);
					border-bottom: 1px solid $color-line;

					&:nth-child(2n + 1) {
						border-right: 1px solid $color-line;
					}

					&.border_none {
						border-bottom: 0;
					}

					.tip {
						font-size: $font-size-tag;
					}

					.num {
						font-size: 36rpx;
						overflow: hidden;
						text-overflow: ellipsis;
					}
				}
			}

			.withdrawal_list {
				.withdrawal_list_title {
					padding: 30rpx;
					border-bottom: 1px solid $color-line;
					display: flex;
					justify-content: space-between;
					align-items: center;
				}

				.withdrawal_list_info {
					padding: 30rpx;

					.withdrawal_list_base {
						display: flex;
						// justify-content: space-between;
						.tip {
							width: 260rpx;
						}

						.img {
							width: 80rpx;
							height: 80rpx;
						}
					}

					.mark {
						word-wrap: break-word;
						word-break: break-all;
						fotn-size: $font-size-tag;
					}
				}

				.operate {
					display: flex;
					justify-content: flex-end;
					padding: $padding 30rpx;
					border-top: 1px solid;

					> view {
						padding: 0 10rpx;
						height: 50rpx;
						line-height: 50rpx;
						text-align: center;
						border: 1px solid;
						margin-left: $margin-updown;
						border-radius: $border-radius;
						font-size: $font-size-tag;
					}
				}
			}
		}
	}
}
</style>
