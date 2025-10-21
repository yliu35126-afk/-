<template>
	<view class="empty" :class="{ fixed: fixed }">
		<view class="empty_img"><image :src="$util.img('upload/uniapp/common-empty.png')" mode="aspectFit"></image></view>
		<view class="color-tip margin-top margin-bottom">{{ text }}</view>
		<button type="primary" size="mini" class="button" @click="goIndex()" v-if="emptyBtn.show">{{ emptyBtn.text }}</button>
	</view>
</template>

<script>
export default {
	data() {
		return {};
	},
	props: {
		text: {
			type: String,
			default: '暂无数据'
		},
		emptyBtn: {
			type: Object,
			default: () => {
				return {
					url: '',
					show: false,
					text: ''
				};
			}
		},
		fixed: {
			type: Boolean,
			default: false
		}
	},
	methods: {
		goIndex() {
			if (this.emptyBtn.url) {
				this.$util.redirectTo(this.emptyBtn.url, {}, 'redirectTo');
			}
		}
	}
};
</script>

<style lang="scss">
.empty {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: $padding;
	box-sizing: border-box;
	justify-content: center;
	.empty_img {
		width: 63%;
		height: 450rpx;

		image {
			width: 100%;
			height: 100%;
			padding-bottom: $padding;
		}
	}
	button {
		min-width: 300rpx;
		margin-top: 100rpx;
		height: 70rpx;
		line-height: 70rpx !important;
		font-size: $font-size-base;
	}
}
.fixed {
	position: fixed;
	left: 0;
	top: 20vh;
}
</style>
