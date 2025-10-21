<template>
	<view>
		<view class="item-wrap">
			<view class="form-wrap more-wrap" @click="change(1)">
				<text class="label">立刻上架</text>
				<view class="iconfont" :class="goodsState == 1 ? 'iconyuan_checked color-base-text' : 'iconyuan_checkbox'"></view>
			</view>
			<view class="form-wrap more-wrap" @click="change(0)">
				<text class="label">放入仓库</text>
				<view class="iconfont" :class="goodsState == 0 ? 'iconyuan_checked color-base-text' : 'iconyuan_checkbox'"></view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			goodsState: 1
		};
	},
	onLoad(option) {
		this.goodsState = option.goods_state || 1;
	},
	onShow() {},
	methods: {
		change(state) {
			this.goodsState = state;
			uni.setStorageSync('editGoodsState', this.goodsState);
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style lang="scss">
@import '../css/edit.scss';
.item-wrap {
	// &:first-child {
	// 	margin-top: 0;
	// }
	.form-wrap {
		.iconfont {
			vertical-align: middle;
			display: inline-block;
			flex: 1;
			text-align: right;
			color: $color-tip;
			font-size: 40rpx;
		}
	}
}
</style>
