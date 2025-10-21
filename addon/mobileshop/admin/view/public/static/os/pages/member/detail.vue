<template>
	<view class="member">
		<view class="member_info">
			<view class="account_base">
				<image :src="memberData.headimg ? $util.img(memberData.headimg) : $util.img($util.getDefaultImage().default_headimg)" @error="imgError('', 1)" class="head"></image>
				<view>
					<view class="nickname">{{ memberData.nickname }}</view>
					<view class="color-sub font-size-tag">手机号：{{ memberData.mobile ? memberData.mobile : '--' }}</view>
					<view class="color-sub font-size-tag">邮箱：{{ memberData.email ? memberData.email : '--' }}</view>
				</view>
			</view>
			<view class="account-about">
				<view class="color-sub">关注状态：{{ memberData.is_subscribe == 1 ? '已关注' : '未关注' }}</view>
				<view class="color-sub">关注时间：{{ $util.timeStampTurnTime(memberData.subscribe_time, 1) }}</view>
			</view>
		</view>
		<text class="order-title color-base-bg-before">交易信息</text>
		<mescroll-uni @getData="getOrderData" refs="mescroll" top="370" :size="10">
			<block slot="list">
				<view class="order-list">
					<view class="item-inner" v-for="(item, index) in dataList" :key="index">
						<view class="order-other-info">
							<text class="color-tip">订单号：{{ item.order_no }}</text>
							<text>{{ item.order_status_name }}</text>
						</view>
						<view class="item-wrap" @click="linkSkip(item)" v-for="(goodsItem, goodsIndex) in item.order_goods" :key="goodsIndex">
							<image
								class="item-img"
								:src="goodsItem.sku_image == '' ? $util.img($util.getDefaultImage().default_headimg) : $util.img(goodsItem.sku_image, { size: 'mid' })"
								@error="imgError(index)"
							></image>
							<view class="item-desc">
								<view class="item-name uni-line-hide">{{ goodsItem.sku_name }}</view>
								<view class="item-price-inner">
									<view>
										<view class="goods-class uni-using-hide" v-if="goodsItem.sku_spec_format">
											<block v-for="(x, i) in JSON.parse(goodsItem.sku_spec_format)" :key="i">
												{{ x.spec_value_name }} {{ i < goodsItem.sku_spec_format.length - 1 ? '; ' : '' }}
											</block>
										</view>
										<view class="goods-class">{{ goodsItem.goods_class_name }}</view>
									</view>
									<view class="item-price-wrap">
										<text class="item-price">￥{{ goodsItem.cost_price }}</text>
										<text class="item-number">x{{ goodsItem.num }}</text>
									</view>
								</view>
							</view>
						</view>
						<text class="place-time color-tip">下单时间：{{ $util.timeStampTurnTime(item.create_time) }}</text>
					</view>
				</view>
				<ns-empty v-if="!dataList.length" text="暂无订单数据"></ns-empty>
			</block>
		</mescroll-uni>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			memberId: 0,
			dataList: [],
			memberData: {}
		};
	},
	onLoad(option) {
		option.member_id ? (this.memberId = option.member_id) : this.$util.redirectTo('/pages/member/list', {}, 'redirectTo');
		if (!this.$util.checkToken('/pages/member/detail?member_id=' + this.memberId)) return;
	},
	onShow() {
		this.getMeberData();
	},
	methods: {
		imgError(index, type) {
			if (!type) {
				this.dataList[index].sku_image = this.$util.getDefaultImage().default_goods_img;
			} else {
				this.memberData.headimg = this.$util.getDefaultImage().default_headimg;
			}
			this.$forceUpdate();
		},
		getOrderData(mescroll) {
			let data = {
				page_size: mescroll.size,
				page: mescroll.num,
				member_id: this.memberId
			};
			this.mescroll = mescroll;
			this.$api.sendRequest({
				url: '/shopapi/member/orderList',
				data: data,
				success: res => {
					let newArr = [];
					let msg = res.message;
					if (res.code == 0 && res.data) {
						newArr = res.data.list;
					} else {
						this.$util.showToast({ title: msg });
					}
					mescroll.endSuccess(newArr.length);
					//设置列表数据
					if (mescroll.num == 1) this.dataList = []; //如果是第一页需手动制空列表
					this.dataList = this.dataList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		getMeberData() {
			let data = {
				member_id: this.memberId
			};
			this.$api.sendRequest({
				url: '/shopapi/member/detail',
				data: data,
				success: res => {
					if (res.code == 0 && res.data) {
						this.memberData = res.data;
					} else {
						this.$util.showToast({ title: res.message });
					}
				}
			});
		}
	}
};
</script>

<style lang="scss">
page {
	overflow: hidden;
}
.member_info {
	background-color: #fff;
	margin: $margin-both;
	border-radius: $border-radius;
	.account_base {
		display: flex;
		padding: $padding 30rpx 10rpx;
		.head {
			height: 106rpx;
			width: 106rpx;
			margin-right: $margin-both;
		}
		.nickname {
			font-weight: 500;
			font-size: $font-size-toolbar;
		}
	}
	.account-about {
		padding: 0 30rpx 30rpx;
		display: flex;
		justify-content: space-between;
		align-items: center;
		> view {
			flex: 1;
		}
		.num,
		.tip {
			color: #fff;
			text-align: center;
		}
	}
}
.order-title,
.member-title {
	position: relative;
	padding-left: 20rpx;
	color: $color-title;
	font-weight: bold;
	font-size: $font-size-toolbar;
	margin: 10rpx 30rpx 0;
	&::after {
		content: '';
		position: absolute;
		left: 0;
		top: 50%;
		height: 28rpx;
		width: 6rpx;
		transform: translateY(-50%);
	}
}
.order-list {
	padding: 10rpx 30rpx 0;
	.item-inner {
		position: relative;
		margin-top: 20rpx;
		background-color: #fff;
		padding: 30rpx 20rpx;
		.order-other-info {
			display: flex;
			justify-content: space-between;
			font-size: $font-size-tag;
		}
		.item-wrap {
			display: flex;
			padding: 20rpx;
			.item-img {
				margin-right: 20rpx;
				width: 180rpx;
				height: 180rpx;
				border-radius: 10rpx;
			}
			.item-desc {
				display: flex;
				flex-direction: column;
				flex: 1;
				color: $color-title;
				.item-name {
					margin-bottom: 12rpx;
					line-height: 1.4;
				}
			}
			.item-price-inner {
				display: flex;
				justify-content: space-between;
				.goods-class {
					width: 300rpx;
					font-size: $font-size-activity-tag;
					color: $color-tip;
				}
				.item-price-wrap {
					display: flex;
					flex-direction: column;
					align-items: flex-end;
					font-weight: initial;
				}
				.item-number {
					font-size: $font-size-tag;
					color: $color-tip;
				}
			}
		}
		.place-time {
			font-size: $font-size-tag;
		}
	}
}
</style>
