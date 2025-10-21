<template>
	<view class="member" @click="shwoOperation">
		<view class="search-inner">
			<view class="search-wrap">
				<text class="search-input-icon iconfont iconsousuo" @click.stop="searchMember()"></text>
				<input class="uni-input font-size-tag" maxlength="50" v-model="searchMemberName" placeholder="请输入会员昵称 / 手机号 / 邮箱" @confirm="searchMember()" />
			</view>
		</view>
		<mescroll-uni class="list-wrap" @getData="getListData" top="160" refs="mescroll" :size="10">
			<block slot="list">
				<view class="item-inner" v-for="(item, index) in dataList" :key="index">
					<view class="item-wrap" @click.stop="shwoOperation(item)">
						<image
							class="item-img"
							:src="item.headimg == '' ? $util.img($util.getDefaultImage().default_headimg) : $util.img(item.headimg)"
							@error="imgError(index)"
						></image>
						<view class="item-desc">
							<view class="item-num-wrap">
								<text class="item-name">{{ item.nickname }}</text>
								<view v-if="item.mobile" class="mobile-wrap">
									<text class="iconfont iconshouji1"></text>
									{{ item.mobile }}
								</view>
							</view>
							<view class="item-price font-size-tag" v-if="item.email">邮箱：{{ item.email }}</view>
							<view class="item-operation">
								<text class="item-price">
									店铺关注：
									<text :class="item.is_subscribe ? 'color-base-text' : 'color-tip'">{{ item.is_subscribe ? '已关注' : '未关注' }}</text>
								</text>
								<text class="iconshenglve iconfont"></text>
							</view>
						</view>
					</view>
					<view class="operation" @click.stop="showHide(item)" v-if="item.is_off">
						<view class="operation-item" @click.stop="linkSkip(item)">
							<image :src="$util.img('/upload/uniapp/shop_uniapp/member/member_01.png')" mode=""></image>
							<text>查看详情</text>
						</view>
						<view class="operation-item" @click.stop="linkSkip(item, 'coupon')">
							<image :src="$util.img('/upload/uniapp/shop_uniapp/member/member_02.png')" mode=""></image>
							<text>发放优惠券</text>
						</view>
					</view>
				</view>
				<ns-empty v-if="!dataList.length" text="暂无会员数据"></ns-empty>
			</block>
		</mescroll-uni>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			searchMemberName: '',
			dataList: []
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/member/list')) return;
		this.$store.dispatch('getShopInfo');
	},
	methods: {
		showHide(val) {
			val.is_off = !val.is_off;
		},
		shwoOperation(item='') {
			let stop = false;
			this.dataList.forEach(v => {
				if (v.is_off == 1) {
					stop = true;
				}
				v.is_off = 0;
			});

			if (!stop && item != '') item.is_off = 1;
		},
		getListData(mescroll) {
			let data = {
				page_size: mescroll.size,
				page: mescroll.num,
				search_text: this.searchMemberName
			};
			this.mescroll = mescroll;
			this.$api.sendRequest({
				url: '/shopapi/member/lists',
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
					newArr.forEach(v => {
						v.is_off = 0;
					});
					this.dataList = this.dataList.concat(newArr); //追加新数据
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		searchMember() {
			this.mescroll.resetUpScroll();
		},
		linkSkip(item, type) {
			item.is_off = 0;
			if (type) this.$util.redirectTo('/pages/member/coupon', { member_id: item.member_id });
			else this.$util.redirectTo('/pages/member/detail', { member_id: item.member_id });
		},
		imgError(index) {
			this.dataList[index].headimg = this.$util.getDefaultImage().default_headimg;
			this.$forceUpdate();
		}
	}
};
</script>
<style lang="scss">
page {
	overflow: hidden;
}
.search-inner {
	padding: 30rpx;
	background-color: #fff;
	.search-wrap {
		display: flex;
		align-items: center;
		padding: 0 30rpx;
		height: 70rpx;
		background-color: $color-bg;
		border-radius: 100rpx;
		.search-input-icon {
			margin-right: 20rpx;
			color: $color-tip;
		}
		input {
			flex: 1;
		}
	}
}
.item-inner {
	position: relative;
	margin: 0 30rpx 20rpx;
	background-color: #fff;
	border-radius: $border-radius;
	.item-wrap {
		display: flex;
		align-items: center;
		padding: 30rpx;
		.item-img {
			margin-right: 20rpx;
			width: 120rpx;
			height: 120rpx;
			border-radius: 50%;
		}
		.item-desc {
			flex: 1;
			.item-num-wrap {
				display: flex;
				align-items: center;
				color: $color-title;
				margin-bottom: 6rpx;
				.item-name {
					max-width: 190rpx;
					overflow: hidden;
					text-overflow: ellipsis;
					white-space: nowrap;
				}
				.mobile-wrap {
					display: flex;
					align-items: center;
					margin-left: 30rpx;
					.iconfont {
						font-size: 34rpx;
						color: $color-title;
					}
				}
			}
			.item-operation {
				display: flex;
				align-items: center;
				justify-content: space-between;
				line-height: 1;
				.item-price {
					font-size: $font-size-tag;
				}
				.iconshenglve {
					font-size: 48rpx;
					color: $color-tip;
				}
			}
		}
	}
	.operation {
		overflow: hidden;
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: rgba(0, 0, 0, 0.6);
		display: flex;
		justify-content: space-around;
		align-items: center;
		border-radius: 10rpx;
		.operation-item {
			display: flex;
			flex-direction: column;
			align-items: center;
			image {
				width: 64rpx;
				height: 64rpx;
			}
			text {
				margin-top: 20rpx;
				font-size: $font-size-tag;
				line-height: 1;
				color: #fff;
			}
		}
	}
}
</style>
