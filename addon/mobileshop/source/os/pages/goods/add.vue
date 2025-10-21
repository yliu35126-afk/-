<template>
	<view>
		<!-- <scroll-view scroll-y="true" class="goods-scroll"> -->
		<view class="goods-edit-wrap">
			<view class="goods-item">
				<view class="title">商品类型</view>
				<view class="goods-type">
					<view :class="{ 'selected color-base-text color-base-border': goodsClass == 1 }" @click="goodsClass = 1">
						<text>实物商品</text>
						<text class="iconfont iconduigou1"></text>
					</view>
					<view :class="{ 'selected color-base-text color-base-border': goodsClass == 2 }" @click="goodsClass = 2">
						<text>虚拟商品</text>
						<text class="iconfont iconduigou1"></text>
					</view>
				</view>
			</view>

			<view class="form-main">
				<view class="form-title">基础信息</view>
				<view class="goods-item">
					<view class="form-wrap">
						<text class="label">商品名称</text>
						<input class="uni-input" v-model="goodsData.goods_name" placeholder="请输入商品名称" maxlength="100" />
					</view>
					<view class="form-wrap">
						<text class="label">促销语</text>
						<input class="uni-input" v-model="goodsData.introduction" placeholder="请输入促销语" maxlength="100" />
					</view>
					<view class="form-wrap more-wrap" @click="openGoodsCategory()">
						<text class="label">商品分类</text>
						<text class="selected">请选择</text>
						<text class="iconfont iconright"></text>
					</view>
					<view class="form-wrap goods-img" :style="{ height: goodsImgHeight + 'px' }">
						<text class="label">商品图片</text>
						<view class="img-list">
							<shmily-drag-image :list.sync="goodsData.goods_image" :imageWidth="170" :number="10" :callback="refreshGoodsImgHeight()"></shmily-drag-image>
							<view class="tips">建议尺寸：800*800，长按图片可拖拽排序，最多上传10张</view>
						</view>
					</view>
				</view>
			</view>

			<view class="form-main">
				<view class="form-title">规格、价格及库存</view>
				<view class="goods-item">
					<view class="form-wrap more-wrap" @click="openGoodsSpec()">
						<text class="label">规格类型</text>
						<text class="selected color-title">单规格</text>
						<text class="iconfont iconright"></text>
					</view>
					<view class="form-wrap more-wrap" @click="openGoodsSpecEdit()">
						<text class="label">规格详情</text>
						<text class="selected">价格、库存</text>
						<text class="iconfont iconright"></text>
					</view>
					<view class="form-wrap price">
						<text class="label">销售价</text>
						<input class="uni-input" v-model="goodsData.price" placeholder="0.00" />
						<text class="unit">元</text>
					</view>
					<view class="form-wrap price">
						<text class="label">划线价</text>
						<input class="uni-input" v-model="goodsData.market_price" placeholder="0.00" />
						<text class="unit">元</text>
					</view>
					<view class="form-wrap price">
						<text class="label">成本价</text>
						<input class="uni-input" v-model="goodsData.cost_price" placeholder="0.00" />
						<text class="unit">元</text>
					</view>
					<view class="form-wrap price">
						<text class="label">库存</text>
						<input class="uni-input" v-model="goodsData.goods_stock" placeholder="0" />
						<text class="unit">件</text>
					</view>
					<view class="form-wrap price">
						<text class="label">库存预警</text>
						<input class="uni-input" v-model="goodsData.goods_stock_alarm" placeholder="0" />
						<text class="unit">件</text>
					</view>
					<view class="form-wrap price">
						<text class="label">重量</text>
						<input class="uni-input" v-model="goodsData.weight" placeholder="0.00" />
						<text class="unit">kg</text>
					</view>
					<view class="form-wrap price">
						<text class="label">体积</text>
						<input class="uni-input" v-model="goodsData.volume" placeholder="0.00" />
						<text class="unit">m³</text>
					</view>
					<view class="form-wrap price">
						<text class="label">商品编码</text>
						<input class="uni-input" v-model="goodsData.sku_no" placeholder="请输入商品编码" />
					</view>
				</view>
			</view>

			<view class="form-main">
				<view class="form-title">配送及其他信息</view>
				<view class="goods-item">
					<view class="form-wrap more-wrap" @click="openExpressFreight()">
						<text class="label">快递运费</text>
						<text class="selected">免邮</text>
						<text class="iconfont iconright"></text>
					</view>
					<!-- <view class="form-wrap more-wrap">
						<text class="label">运费模板</text>
						<text class="selected">请选择运费模板</text>
						<text class="iconfont iconright"></text>
					</view> -->
					<view class="form-wrap price">
						<text class="label">限购数量</text>
						<input class="uni-input" placeholder="0" />
						<text class="unit">件</text>
					</view>
					<view class="form-wrap price">
						<text class="label">起售数量</text>
						<input class="uni-input" placeholder="0" />
						<text class="unit">件</text>
					</view>
					<view class="form-wrap price">
						<text class="label">单位</text>
						<input class="uni-input" placeholder="请输入单位" />
					</view>
					<view class="form-wrap price">
						<text class="label">排序</text>
						<input class="uni-input" placeholder="0" />
					</view>
					<view class="form-wrap more-wrap" @click="openGoodsState()">
						<text class="label">状态</text>
						<text class="selected">立刻上架</text>
						<text class="iconfont iconright"></text>
					</view>
				</view>
			</view>

			<view class="form-main">
				<view class="form-title">商品详情</view>
				<view class="goods-item">
					<view class="form-wrap more-wrap">
						<text class="label">商品详情</text>
						<text class="selected">查看</text>
						<text class="iconfont iconright"></text>
					</view>
				</view>
			</view>

			<view class="form-main">
				<view class="form-title">商品参数</view>
				<view class="goods-item">
					<view class="form-wrap more-wrap" @click="openAttr()">
						<text class="label">商品参数</text>
						<text class="selected">查看</text>
						<text class="iconfont iconright"></text>
					</view>
				</view>
			</view>
			<!-- <loading-cover ref="loadingCover"></loading-cover> -->
		</view>
		<!-- </scroll-view> -->

		<view class="footer-wrap"><button type="primary">保存</button></view>
	</view>
</template>

<script>
import shmilyDragImage from '@/components/shmily-drag-image/shmily-drag-image.vue';
import nsSwitch from '@/components/ns-switch/ns-switch.vue';
import edit from './js/edit.js';
export default {
	components: {
		shmilyDragImage,
		nsSwitch
	},
	mixins: [edit]
};
</script>

<style lang="scss">
@import './css/edit.scss';
</style>
