<template>
	<view class="container" :class="{ 'safe-area': isIphoneX }">
		<view class="goods-edit-wrap">
			<view class="item-wrap padding" v-if="goodsData.goods_id == 0">
				<view class="form-title color-title">商品类型</view>
				<view class="goods-type">
					<view :class="{ 'selected color-base-text color-base-border': goodsData.goods_class == 1 }" @click="goodsData.goods_class = 1">
						<text>实物商品</text>
						<text class="iconfont iconduigou1"></text>
					</view>
					<view :class="{ 'selected color-base-text color-base-border': goodsData.goods_class == 2 }" @click="goodsData.goods_class = 2">
						<text>虚拟商品</text>
						<text class="iconfont iconduigou1"></text>
					</view>
				</view>
			</view>

			<view class="form-title">基础信息</view>
			<view class="item-wrap">
				<view class="form-wrap">
					<view class="label">
						<text class="required color-base-text">*</text>
						<text>商品名称</text>
					</view>
					<input class="uni-input" v-model="goodsData.goods_name" placeholder="请输入商品名称" maxlength="100" />
				</view>
				<view class="form-wrap">
					<text class="label">促销语</text>
					<input class="uni-input" v-model="goodsData.introduction" placeholder="请输入促销语" maxlength="100" />
				</view>
				<view class="form-wrap more-wrap" @click="openGoodsCategoryPop()">
					<view class="label">
						<text class="required color-base-text">*</text>
						<text>商品分类</text>
					</view>
					<text class="selected" :class="{ have: goodsData.category_name }">{{ goodsData.category_name ? goodsData.category_name : '请选择' }}</text>
					<text class="iconfont iconright"></text>
				</view>
				<view class="form-wrap goods-img" :style="{ height: goodsImgHeight + 'px' }">
					<view class="label">
						<text class="required color-base-text">*</text>
						<text>商品图片</text>
					</view>
					<view class="img-list">
						<shmily-drag-image
							ref="goodsShmilyDragImg"
							:list.sync="goodsData.goods_image"
							:imageWidth="170"
							:imageHeight="170"
							:number="10"
							uploadMethod="album"
							:openSelectMode="true"
							:isAWait="isAWait"
							@callback="refreshGoodsImgHeight"
						></shmily-drag-image>
						<view class="tips">建议尺寸：800*800，长按图片可拖拽排序，最多上传10张</view>
					</view>
				</view>
				<view class="form-wrap">
					<text class="label">关键词</text>
					<input class="uni-input" v-model="goodsData.keywords" placeholder="商品关键词用于SEO搜索" maxlength="100" />
				</view>
				<view class="form-wrap more-wrap">
					<text class="label">商品品牌</text>
					<picker class="selected" @change="bindBrandPickerChange" :value="brandPicker.index" :range="brandPicker.arr">
						<view class="uni-input" :class="{ 'color-tip': !goodsData.brand_name }">{{ goodsData.brand_name ? goodsData.brand_name : '请选择商品品牌' }}</view>
					</picker>
					<text class="iconfont iconright"></text>
				</view>
				<view class="form-wrap price" v-if="goodsData.goods_class == 2">
					<view class="label">
						<text class="required color-base-text">*</text>
						<text>有效期</text>
					</view>
					<input class="uni-input" v-model="goodsData.virtual_indate" type="number" placeholder="0" />
					<text class="unit">天</text>
				</view>
			</view>
			<view class="form-title">
				<text>店内分类</text>
				<text @click="addShopCategory" class="color-base-text">添加</text>
			</view>
			<view class="item-wrap">
				<view class="form-wrap more-wrap" v-for="(item, index) in shopCategoryNumber" :key="index" @click="openShopCategoryPopup(index)">
					<text class="action iconfont iconjian" @click.stop="deleteShopCategory(index)"></text>
					<text class="label">店内分类</text>
					<text class="selected" :class="{ have: shopCategoryData['store_' + index] && shopCategoryData['store_' + index].category_name }">
						{{ shopCategoryData['store_' + index] && shopCategoryData['store_' + index].category_name ? shopCategoryData['store_' + index].category_name : '请选择' }}
					</text>
					<text class="iconfont iconright"></text>
				</view>
			</view>
			<view class="form-title">规格、价格及库存</view>
			<view class="item-wrap">
				<view class="form-wrap more-wrap" @click="openGoodsSpec()">
					<text class="label">规格类型</text>
					<text class="selected color-title">{{ goodsData.goods_spec_format.length ? '多规格' : '单规格' }}</text>
					<text class="iconfont iconright"></text>
				</view>
				<view class="form-wrap more-wrap" v-if="goodsData.goods_spec_format.length" @click="openGoodsSpecEdit()">
					<text class="label">规格详情</text>
					<text class="selected color-title">价格、库存</text>
					<text class="iconfont iconright"></text>
				</view>
				<template v-else>
					<view class="form-wrap price">
						<view class="label">
							<text class="required color-base-text">*</text>
							<text>销售价</text>
						</view>
						<input class="uni-input" v-model="goodsData.price" type="digit" placeholder="0.00" />
						<text class="unit">元</text>
					</view>
					<view class="form-wrap price">
						<text class="label">划线价</text>
						<input class="uni-input" v-model="goodsData.market_price" type="digit" placeholder="0.00" />
						<text class="unit">元</text>
					</view>
					<view class="form-wrap price">
						<text class="label">成本价</text>
						<input class="uni-input" v-model="goodsData.cost_price" type="digit" placeholder="0.00" />
						<text class="unit">元</text>
					</view>
					<view class="form-wrap price">
						<view class="label">
							<text class="required color-base-text">*</text>
							<text>库存</text>
						</view>
						<input class="uni-input" v-model="goodsData.goods_stock" type="number" placeholder="0" />
						<text class="unit">件</text>
					</view>
					<view class="form-wrap price">
						<text class="label">库存预警</text>
						<input class="uni-input" v-model="goodsData.goods_stock_alarm" type="number" placeholder="0" />
						<text class="unit">件</text>
					</view>
					<view class="form-wrap price" v-if="goodsData.goods_class == 1">
						<text class="label">重量</text>
						<input class="uni-input" v-model="goodsData.weight" type="digit" placeholder="0.00" />
						<text class="unit">kg</text>
					</view>
					<view class="form-wrap price" v-if="goodsData.goods_class == 1">
						<text class="label">体积</text>
						<input class="uni-input" v-model="goodsData.volume" type="digit" placeholder="0.00" />
						<text class="unit">m³</text>
					</view>
					<view class="form-wrap price">
						<text class="label">商品编码</text>
						<input class="uni-input" v-model="goodsData.sku_no" placeholder="请输入商品编码" />
					</view>
				</template>
			</view>

			<view class="form-title">{{ goodsData.goods_class == 1 ? '配送及其他信息' : '其他信息' }}</view>
			<view class="item-wrap">
				<view class="form-wrap more-wrap" @click="openExpressFreight()" v-if="goodsData.goods_class == 1">
					<text class="label">快递运费</text>
					<text class="selected color-title">{{ goodsData.template_name ? goodsData.template_name : '免邮' }}</text>
					<text class="iconfont iconright"></text>
				</view>
				<view class="form-wrap price">
					<text class="label">限购数量</text>
					<input class="uni-input" type="number" v-model="goodsData.max_buy" placeholder="0" />
					<text class="unit">件</text>
				</view>
				<view class="form-wrap price">
					<text class="label">起售数量</text>
					<input class="uni-input" type="number" v-model="goodsData.min_buy" placeholder="0" />
					<text class="unit">件</text>
				</view>
				<view class="form-wrap price">
					<text class="label">单位</text>
					<input class="uni-input" v-model="goodsData.unit" placeholder="请输入单位" />
				</view>
				<!-- <view class="form-wrap price">
						<text class="label">排序</text>
						<input class="uni-input" type="number" v-model="goodsData.sort" placeholder="0" />
					</view> -->
				<view class="form-wrap more-wrap" @click="openGoodsState()">
					<text class="label">状态</text>
					<text class="selected color-title">{{ goodsData.goods_state == 1 ? '立刻上架' : '放入仓库' }}</text>
					<text class="iconfont iconright"></text>
				</view>
			</view>

			<view class="form-title">商品详情</view>
			<view class="item-wrap">
				<view class="form-wrap more-wrap" @click="openGoodsContent()">
					<text class="label">商品详情</text>
					<text class="selected have">查看</text>
					<text class="iconfont iconright"></text>
				</view>
			</view>

			<view class="form-title">商品参数</view>
			<view class="item-wrap">
				<view class="form-wrap more-wrap" @click="openAttr()">
					<text class="label">商品参数</text>
					<text class="selected have">查看</text>
					<text class="iconfont iconright"></text>
				</view>
			</view>
		</view>

		<!-- 选择商品分类 -->
		<uni-popup ref="categoryPopup" type="bottom">
			<view class="popup category" @touchmove.prevent.stop>
				<view class="popup-header">
					<text class="tit">选择商品分类</text>
					<text class="iconfont iconclose" @click="closeGoodsCategoryPop()"></text>
				</view>
				<view class="popup-body" :class="{ 'safe-area': isIphoneX }">
					<view class="nav color-base-text">
						<text
							:class="{ 'selected color-base-text': item }"
							v-if="currentLevel >= index + 1"
							v-for="(item, index) in categoryName"
							:key="index"
							@click="changeShow(index + 1)"
						>
							{{ item ? item : '请选择' }}
						</text>
					</view>
					<scroll-view scroll-y="true" class="category">
						<!-- 一级分类 -->
						<view v-if="showFisrt" class="item" v-for="(item, index) in categoryList" :key="index" @click="selectCategory(item)">
							<text :class="{ 'color-base-text': categoryId[0] == item.category_id }">{{ item.category_name }}</text>
							<text v-show="categoryId[0] == item.category_id" class="iconfont iconqueding_queren color-base-text"></text>
						</view>
						<!-- 二级分类 -->
						<view v-if="showSecond" class="item" v-for="(item, index) in secondCategory" :key="index" @click="selectCategory(item)">
							<text :class="{ 'color-base-text': categoryId[1] == item.category_id }">{{ item.category_name }}</text>
							<text v-show="categoryId[1] == item.category_id" class="iconfont iconqueding_queren color-base-text"></text>
						</view>
						<!-- 三级分类 -->
						<view v-if="showThird" class="item" v-for="(item, index) in thirdCategory" :key="index" @click="selectCategory(item)">
							<text :class="{ 'color-base-text': categoryId[2] == item.category_id }">{{ item.category_name }}</text>
							<text v-show="categoryId[2] == item.category_id" class="iconfont iconqueding_queren color-base-text"></text>
						</view>
					</scroll-view>
				</view>
			</view>
		</uni-popup>

		<!-- 选择店内分类 -->
		<uni-popup ref="storePopup" type="bottom">
			<view class="popup category" @touchmove.prevent.stop>
				<view class="popup-header">
					<text class="tit">选择店内分类</text>
					<text class="iconfont iconclose" @click="closeShopCategoryPopup()"></text>
				</view>
				<view class="popup-body" :class="{ 'safe-area': isIphoneX }">
					<view class="nav color-base-text"><text>请选择</text></view>
					<scroll-view scroll-y="true" class="category">
						<block v-for="(item, index) in shopCategoryList" :key="index">
							<view v-if="showFisrt" class="item" @click.stop="selectShopCategory(item)">
								<text
									:class="{
										'color-base-text':
											shopCategoryData['store_' + shopCategoryIndex] && shopCategoryData['store_' + shopCategoryIndex].category_id == item.category_id
									}"
								>
									{{ item.category_name }}
								</text>
								<text
									v-show="shopCategoryData['store_' + shopCategoryIndex] && shopCategoryData['store_' + shopCategoryIndex].category_id == item.category_id"
									class="iconfont iconqueding_queren color-base-text"
								></text>
							</view>
							<block v-if="item.child_list">
								<view
									v-for="(childItem, childIndex) in item.child_list"
									:key="childItem.category_id"
									class="child-item"
									@click.stop="selectShopCategory(childItem)"
								>
									<text
										:class="{
											'color-base-text':
												shopCategoryData['store_' + shopCategoryIndex] &&
												shopCategoryData['store_' + shopCategoryIndex].category_id == childItem.category_id
										}"
									>
										{{ childItem.category_name }}
									</text>
									<text
										v-show="
											shopCategoryData['store_' + shopCategoryIndex] && shopCategoryData['store_' + shopCategoryIndex].category_id == childItem.category_id
										"
										class="iconfont iconqueding_queren color-base-text"
									></text>
								</view>
							</block>
						</block>
					</scroll-view>
				</view>
			</view>
		</uni-popup>

		<view class="footer-wrap" :class="{ 'safe-area': isIphoneX }"><button type="primary" @click="save()">保存</button></view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import shmilyDragImage from '@/components/shmily-drag-image/shmily-drag-image.vue';
import edit from '../js/edit.js';
export default {
	components: {
		shmilyDragImage
	},
	mixins: [edit]
};
</script>

<style lang="scss">
@import '../css/edit.scss';
</style>
<style scoped>
.img-list >>> .con .area {
	/* height: 170rpx; */
}
</style>
