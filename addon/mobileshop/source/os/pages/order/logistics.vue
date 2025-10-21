<template>
	<view class="container">
		<scroll-view class="order-nav" :scroll-x="true" :show-scrollbar="false">
			<view v-for="(packageItem, packageIndex) in packageList" :key="packageIndex" class="uni-tab-item" @click="ontabtap(packageIndex)">
				<text class="uni-tab-item-title" :class="packageIndex == currIndex ? 'active color-base-border  color-base-text' : ''">{{ packageItem.package_name }}</text>
			</view>
		</scroll-view>
		<view v-for="(packageItem, packageIndex) in packageList" :key="packageIndex" class="swiper-item" v-show="packageIndex == currIndex">
			<view class="container">
				<view class="goods-wrap">
					<view class="body">
						<view class="goods" v-for="(goodsItem, goodsIndex) in packageItem.goods_list" :key="goodsIndex">
							<view class="goods-img">
								<image :src="$util.img(goodsItem.sku_image, { size: 'mid' })" @error="imgError(packageIndex, goodsIndex)" mode="aspectFill"></image>
							</view>
							<view class="goods-info">
								<view class="goods-name">{{ goodsItem.sku_name }}</view>
								<view class="goods-sub-section">
									<text class="iconfont iconclose"></text>
									<text>{{ goodsItem.num }}</text>
								</view>
							</view>
						</view>
					</view>
				</view>

				<view class="express-company-wrap">
					<view class="company-logo"><image :src="$util.img(packageItem.express_company_image)"></image></view>
					<view class="info">
						<view>发货时间： {{ $util.timeStampTurnTime(packageItem.delivery_time) }}</view>
						<view class="company">承运公司： {{ packageItem.express_company_name }}</view>
						<view class="delivery-no">
							<text>运单号：{{ packageItem.delivery_no }}</text>
							<text class="copy color-base-text" @click="$util.copy(packageItem.delivery_no)">复制</text>
						</view>
					</view>
				</view>

				<view class="track-wrap">
					<block v-if="packageItem.trace.success && packageItem.trace.list.length != 0">
						<view
							class="track-item"
							v-for="(traceItem, traceIndex) in packageItem.trace.list"
							:data-theme="themeStyle"
							:class="traceIndex == 0 ? 'active' : ''"
							:key="traceIndex"
						>
							<view class="dot" :class="traceIndex == 0 ? 'color-base-bg' : ''"></view>
							<view class="msg">
								<view class="text" :class="traceIndex == 0 ? 'color-base-text' : ''">{{ traceItem.remark }}</view>
								<view class="time" :class="traceIndex == 0 ? 'color-base-text' : ''">{{ traceItem.datetime }}</view>
							</view>
						</view>
					</block>
					<block v-else-if="packageItem.trace.success && packageItem.trace.list.length == 0">
						<view class="fail-wrap font-size-base">{{ packageItem.trace.reason }}</view>
					</block>
					<block v-else>
						<view class="fail-wrap font-size-base">{{ packageItem.trace.reason }}</view>
					</block>
				</view>
				<view class="footer-wrap" v-if="orderStatus == 1 || orderStatus == 3"><button type="primary" @click="editLogistics(packageItem)">修改物流</button></view>
			</view>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			orderId: '',
			orderStatus: 0,
			packageList: [],
			currIndex: 0,
			status: 0
		};
	},
	onLoad(option) {
		if (option.order_id) this.orderId = option.order_id;
	},
	onShow() {
		// 判断登录
		if (!this.$util.checkToken('/pages/order/logistics?order_id=' + this.orderId)) return;
		this.getPackageInfo();
	},
	methods: {
		ontabtap(e) {
			this.currIndex = e;
		},
		getPackageInfo() {
			this.$api.sendRequest({
				url: '/shopapi/order/package',
				data: {
					order_id: this.orderId
				},
				success: res => {
					if (res.code >= 0) {
						var data = res.data;
						this.orderStatus = data.order_status;
						this.packageList = data.package;
						this.packageList.forEach(item => {
							if (item.trace.list) {
								item.trace.list = item.trace.list.reverse();
							}
							item.status = this.status++;
						});
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					} else {
						this.$util.showToast({
							title: '未获取到订单信息！'
						});
						setTimeout(() => {
							uni.navigateBack({
								delta: 1
							});
						}, 1000);
					}
				}
			});
		},
		imgError(packageIndex, goodsIndex) {
			this.packageList[packageIndex].goods_list[goodsIndex].sku_image = this.$util.getDefaultImage().default_goods_img;
			this.$forceUpdate();
		},
		editLogistics(item) {
			uni.setStorageSync('editLogistics', JSON.stringify(item));
			this.$util.redirectTo('/pages/order/edit_logistics');
		}
	}
};
</script>

<style lang="scss">
@import './css/logistics.scss';
/deep/.uni-scroll-view ::-webkit-scrollbar {
	/* 隐藏滚动条，但依旧具备可以滚动的功能 */
	display: none;
	width: 0;
	height: 0;
	color: transparent;
	background: transparent;
}
/deep/::-webkit-scrollbar {
	display: none;
	width: 0;
	height: 0;
	color: transparent;
	background: transparent;
}
</style>
