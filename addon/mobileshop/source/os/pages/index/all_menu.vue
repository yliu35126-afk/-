<template>
	<view>
		<view class="menu_item">
			<view class="menu_title">
				<text class="line color-base-bg margin-right"></text>
				店铺经营
			</view>
			<view class="menu_content">
				<uni-grid :column="4" :showBorder="!1">
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/goods/edit/index')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/manage_good_send.png')" mode="aspectFit" />
							<view class="text">商品发布</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/goods/list')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/manage_good.png')" mode="aspectFit" />
							<view class="text">商品管理</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/order/list')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/manage_order.png')" mode="aspectFit" />
							<view class="text">订单管理</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/member/list')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/member_card.png')" mode="aspectFit" />
							<view class="text">会员管理</view>
						</view>
					</uni-grid-item>
				</uni-grid>
			</view>
		</view>
		<view class="menu_item">
			<view class="menu_title">
				<text class="line color-base-bg margin-right"></text>
				财务管理
			</view>
			<view class="menu_content">
				<uni-grid :column="4" :showBorder="!1">
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/property/dashboard/index')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/finance_survey.png')" mode="aspectFit" />
							<view class="text">资产概况</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/property/withdraw/list')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/tixian.png')" mode="aspectFit" />
							<view class="text">提现记录</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/property/fee')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/ruzhu.png')" mode="aspectFit" />
							<view class="text">入驻费用</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/property/settlement/list')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/shop_jiesuan.png')" mode="aspectFit" />
							<view class="text">店铺结算</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/property/settlement/list_store')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/store_jiesuan.png')" mode="aspectFit" />
							<view class="text">门店结算</view>
						</view>
					</uni-grid-item>
				</uni-grid>
			</view>
		</view>
		<view class="menu_item">
			<view class="menu_title">
				<text class="line color-base-bg margin-right"></text>
				统计分析
			</view>
			<view class="menu_content">
				<uni-grid :column="4" :showBorder="!1">
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/statistics/transaction')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/tongji_jiaoyi.png')" mode="aspectFit" />
							<view class="text">交易统计</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/statistics/shop')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/tongji_shop.png')" mode="aspectFit" />
							<view class="text">店铺统计</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/statistics/goods')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/tongji_good.png')" mode="aspectFit" />
							<view class="text">商品统计</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/statistics/visit')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/tongji_member.png')" mode="aspectFit" />
							<view class="text">访问统计</view>
						</view>
					</uni-grid-item>
				</uni-grid>
			</view>
		</view>
		<view class="menu_item">
			<view class="menu_title">
				<text class="line color-base-bg margin-right"></text>
				店铺设置
			</view>
			<view class="menu_content">
				<uni-grid :column="4" :showBorder="!1">
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/my/shop/config')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/set_shop.png')" mode="aspectFit" />
							<view class="text">店铺信息</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/my/user/user')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/set_member.png')" mode="aspectFit" />
							<view class="text">用户管理</view>
						</view>
					</uni-grid-item>
					<!-- <uni-grid-item>
						<view @click="$util.redirectTo('/pages/my/statistics')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/set_jiaoyi.png')" mode="aspectFit" />
							<view class="text">交易设置</view>
						</view>
					</uni-grid-item> -->
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/my/shop/contact')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/set_address.png')" mode="aspectFit" />
							<view class="text">联系地址</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/notice/list')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/admin_notice.png')" mode="aspectFit" />
							<view class="text">平台公告</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/verify/index')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/verify.png')" mode="aspectFit" />
							<view class="text">核销台</view>
						</view>
					</uni-grid-item>
					<uni-grid-item>
						<view @click="$util.redirectTo('/pages/verify/user')" class="grid_item">
							<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/verify_peo.png')" mode="aspectFit" />
							<view class="text">核销人员</view>
						</view>
					</uni-grid-item>
				</uni-grid>
			</view>
		</view>
		<diy-bottom-nav :link-index='2'></diy-bottom-nav>
	</view>
</template>

<script>
import uniGrid from '@/components/uni-grid/uni-grid.vue';
import uniGridItem from '@/components/uni-grid-item/uni-grid-item.vue';
import diyBottomNav from '@/components/diy-bottom-nav/diy-bottom-nav.vue';
import uCharts from '@/components/u-charts/u-charts.vue';
export default {
	data() {
		return {};
	},
	components: {
		uniGrid,
		uniGridItem,
		uCharts,
		diyBottomNav
	},
	methods: {}
};
</script>

<style lang="scss">
page{
	overflow: auto !important;
}
.menu_item {
	background-color: #fff;
	padding: 25rpx $margin-both 0;
	.menu_title {
		font-size: $font-size-toolbar;
		font-weight: bold;
		margin-bottom: 10rpx;
		.line {
			display: inline-block;
			height: 28rpx;
			width: 4rpx;
			border-radius: 4rpx;
		}
	}
	.menu_content {
		.grid_item {
			text-align: center;
			.image {
				width: 50rpx;
				height: 50rpx;
				min-height: 50rpx;
			}

			.text {
				margin-top: 16rpx;
				color: $color-title;
			}
		}
	}
}
</style>
