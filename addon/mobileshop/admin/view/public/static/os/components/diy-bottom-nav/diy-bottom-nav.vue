<template>
	<view>
		<view class="u-tabbar-inner">
			<view class="tabbar-item" v-for="(item, index) in list" :key="index" @click="redirectTo(item.link)">
				<image :src="index == linkIndex ? item.selectedIconPath : item.iconPath" mode="scaleToFill"></image>
				<text class="item-desc" :class="{ 'color-base-text': index == linkIndex }">{{ item.text }}</text>
			</view>
		</view>
		
		<!-- 解决fixed定位后导航栏塌陷的问题 -->
		<view class="un-tabbar-flex"></view>
	</view>
</template>

<script>
export default {
	name: 'diy-bottom-nav',
	props:{
		linkIndex: { // 当前tab的下标
			type: Number,
			default () {
				return 0
			}
		}
	},
	data() {
		return {
			currentRoute: '', //当前页面路径
			jumpFlag: true,
			list: [
				{
					link: '/pages/order/list',
					selectedIconPath: '/static/images/tabbar/order_selected.png',
					iconPath: '/static/images/tabbar/order.png',
					text: '订单'
				},
				{
					link: '/pages/goods/list',
					selectedIconPath: '/static/images/tabbar/goods_selected.png',
					iconPath: '/static/images/tabbar/goods.png',
					text: '商品'
				},
				{
					link: '/pages/index/index',
					selectedIconPath: '/static/images/tabbar/shop_selected.png',
					iconPath: '/static/images/tabbar/shop.png',
					text: '店铺'
				},
				{
					link: '/pages/member/list',
					selectedIconPath: '/static/images/tabbar/member_selected.png',
					iconPath: '/static/images/tabbar/member.png',
					text: '会员'
				},
				{
					link: '/pages/my/index',
					selectedIconPath: '/static/images/tabbar/my_selected.png',
					iconPath: '/static/images/tabbar/my.png',
					text: '我的'
				}
			]
		};
	},
	methods: {
		redirectTo(link) {
			if (!this.jumpFlag) return;
			this.jumpFlag = false;
			setTimeout(() => {
				this.jumpFlag = true;
			}, 300);
			this.$util.redirectTo(link);
		}
	}
};
</script>

<style lang="scss">
.u-tabbar-inner {
	position: fixed;
	left: 0;
	bottom: 0;
	width: 100%;
	min-height: 50px;
	z-index: 998;
	display: flex;
	background-color: #fff;
	box-sizing: border-box;
	padding-bottom: constant(safe-area-inset-bottom);
	padding-bottom: env(safe-area-inset-bottom);
	.tabbar-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		width: 150rpx;
		image {
			margin-top: 7px; 
			width: 24px;
			height: 24px;
		}
		.item-desc {
			font-size: 10px;
			color: #333;
		}
	}
	.un-tabbar-flex {
		height: 50px;
		background-color: transparent;
		padding-bottom: 0;
		padding-bottom: constant(safe-area-inset-bottom);
		padding-bottom: env(safe-area-inset-bottom);
	}
}
.un-tabbar-flex {
	width: 100%;
	height: 50px;
	background-color: transparent;
	padding-bottom: 0;
	padding-bottom: constant(safe-area-inset-bottom);
	padding-bottom: env(safe-area-inset-bottom);
}
</style>
