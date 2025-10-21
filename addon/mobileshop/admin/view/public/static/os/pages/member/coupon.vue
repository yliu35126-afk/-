<template>
	<view class="coupon">
		<mescroll-uni @getData="getCouponData" refs="mescroll" :size="10">
			<block slot="list">
				<block v-if="couponData.length">
					<view
						class="coupon-item"
						v-for="(item, index) in couponData"
						:key="index"
						:style="{ backgroundImage: 'url(' + $util.img('upload/uniapp/shop_uniapp/member/coupon_bg.png') + ')' }"
						@click="isChecked(item)"
					>
						<view class="coupon-title">
							<text class="coupon-money uni-using-hide">
								<text>￥</text>
								{{ parseInt(item.money) }}
							</text>
							<text class="uni-using-hide">满{{ parseInt(item.at_least) }}可用</text>
						</view>
						<view class="coupon-content">
							<text class="coupon-name uni-line-hide">{{ item.coupon_name }}</text>
							<text class="coupon-time">{{ item.expireTime }}</text>
						</view>
						<view class="checkbox-wrap"><text class="checkbox iconfont" :class="item.checked ? 'iconfuxuankuang1 color-base-text' : 'iconfuxuankuang2'"></text></view>
					</view>
				</block>
				<ns-empty v-else-if="!couponData.length && emptyShow"></ns-empty>
			</block>
		</mescroll-uni>
		<view class="footer-wrap" v-if="couponData.length"><button type="primary" @click="save()"  :disabled="!Boolean(couponArr.length)">发放优惠券</button></view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			emptyShow: false,
			memberId: 0,
			couponData: [],
			couponArr: [],
			repeatFlag: false
		};
	},
	onLoad(option) {
		option.member_id ? (this.memberId = option.member_id) : this.$util.redirectTo('/pages/member/list', {}, 'redirectTo');
		if (!this.$util.checkToken('/pages/member/detail?member_id=' + this.memberId)) return;
	},
	methods: {
		isChecked(item) {
			item.checked = !item.checked;
			if (item.checked) this.couponArr.push(item.coupon_type_id);
			else this.couponArr.splice(this.couponArr.indexOf(item.coupon_type_id), 1);
		},
		getCouponData(mescroll) {
			let data = {
				page_size: mescroll.size,
				page: mescroll.num,
				member_id: this.memberId,
				status: 1
			};
			this.mescroll = mescroll;
			this.$api.sendRequest({
				url: '/coupon/shopapi/coupon/lists',
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
						this.$set(v, 'checked', false);
						if (v.validity_type == 0) v.expireTime = '有效期：' + this.$util.timeStampTurnTime(v.end_time);
						else if (v.validity_type == 1) v.expireTime = '领取之日起' + v.fixed_term + '天有效';
					});
					this.couponData = this.couponData.concat(newArr); //追加新数据
					this.emptyShow = true;
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		save() {
			if (this.repeatFlag) return;
			this.repeatFlag = true;

			let parent = this.couponArr.toString();
			this.$api.sendRequest({
				url: '/coupon/shopapi/coupon/send',
				data: {
					member_id: this.memberId,
					parent: parent
				},
				success: res => {
					this.repeatFlag = false;
					if (res.code == 0) {
						this.$util.showToast({
							title: '优惠券发放成功'
						});
						setTimeout(() => {
							this.$util.redirectTo('/pages/member/list');
						}, 1000);
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		}
	}
};
</script>

<style lang="scss">
.coupon {
	overflow: hidden;
}
.disabled {
	background-color: #ccc;
}
.coupon-item {
	display: flex;
	margin: 30rpx 30rpx 0;
	height: 170rpx;
	background-size: contain;
	background-repeat: no-repeat;
	.coupon-title {
		padding-left: 10rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-direction: column;
		width: 198rpx;
		color: #fff;
		box-sizing: border-box;
	}
	.coupon-content {
		display: flex;
		flex-direction: column;
		padding: 0 30rpx;
		width: 356rpx;
		.coupon-name {
			margin-top: 10rpx;
			line-height: 1.5;
			height: 86rpx;
		}
		.coupon-time {
			margin-top: 10rpx;
			border-top: 2rpx dashed $color-line;
			padding-top: 6rpx;
			font-size: $font-size-tag;
		}
	}
	.coupon-money {
		text-align: center;
		font-size: 50rpx;
		text {
			font-size: 30rpx;
		}
	}
	.checkbox {
		color: $color-tip;
		margin-right: 10rpx;
	}
}
.footer-wrap {
	position: fixed;
	width: 100%;
	bottom: 0;
	padding: 40rpx 0;
	z-index: 10;
	/* #ifdef MP */
	padding-bottom: 40rpx;
	padding-bottom: 40rpx;
	/* #endif */
	/* #ifndef MP */
	padding-bottom: calc(constant(safe-area-inset-bottom) + 100rpx);
	padding-bottom: calc(env(safe-area-inset-bottom) + 100rpx);
	/* #endif */
}
</style>
