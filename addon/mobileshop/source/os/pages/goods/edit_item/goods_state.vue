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
		<view class="footer-wrap"><button type="primary">保存</button></view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			goodsState: 1
		};
	},
	onLoad(data) {},
	onShow() {},
	methods: {
		change(state) {
			this.goodsState = state;
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style lang="scss">
.item-wrap {
	background: #fff;
	padding: $padding;
	margin-top: $margin-updown;
	.form-wrap {
		display: flex;
		align-items: center;
		margin: 0 $margin-both;
		border-bottom: 1px solid $color-line;
		height: 100rpx;
		line-height: 100rpx;
		&:last-child {
			border-bottom: none;
		}
		.label {
			vertical-align: middle;
		}
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
