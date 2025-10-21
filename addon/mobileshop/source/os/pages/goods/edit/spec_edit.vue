<template>
	<view class="container">
		<view class="sku-list">
			<view class="item-inner" v-for="(item, index) in goodsSkuData" :key="index">
				<view class="item-wrap" :class="{ 'item-shrink': item.shrink }">
					<view class="form-wrap">
						<text class="label">规格</text>
						<text class="spec-name">{{ item.spec_name }}</text>
					</view>
					<view class="form-wrap">
						<view class="label">
							<text class="required color-base-text">*</text>
							<text>销售价</text>
						</view>
						<input class="uni-input" v-model="item.price" type="digit" placeholder="0.00" />
						<text class="unit">元</text>
					</view>
					<view class="form-wrap">
						<view class="label">
							<text class="required color-base-text">*</text>
							<text>库存</text>
						</view>
						<input class="uni-input" v-model="item.stock" type="number" placeholder="0" />
						<text class="unit">件</text>
					</view>
					<view class="form-wrap goods-img" :style="{ height: skuImgHeight[index] + 'px' }">
						<text class="label">规格图片</text>
						<view class="img-list" :class="'list-' + index">
							<shmily-drag-image
								ref="skuShmilyDragImg"
								:list.sync="item.sku_images_arr"
								:imageWidth="150"
								:imageHeight="170"
								:number="10"
								:index="index"
								uploadMethod="album"
								:openSelectMode="true"
								:isAWait="item.sku_images_arr && item.sku_images_arr.length > 0"
								@callback="uploadImgCallback"
							></shmily-drag-image>
							<view class="tips">建议尺寸：800*800，长按图片可拖拽排序，最多上传10张</view>
						</view>
					</view>
					<view class="form-wrap">
						<text class="label">划线价</text>
						<input class="uni-input" v-model="item.market_price" type="digit" placeholder="0.00" />
						<text class="unit">元</text>
					</view>
					<view class="form-wrap">
						<text class="label">成本价</text>
						<input class="uni-input" v-model="item.cost_price" type="digit" placeholder="0.00" />
						<text class="unit">元</text>
					</view>
					<view class="form-wrap">
						<text class="label">库存预警</text>
						<input class="uni-input" v-model="item.stock_alarm" type="number" placeholder="0" />
						<text class="unit">件</text>
					</view>
					<view class="form-wrap" v-if="goodsClass == 1">
						<text class="label">重量</text>
						<input class="uni-input" v-model="item.weight" type="digit" placeholder="0.00" />
						<text class="unit">kg</text>
					</view>
					<view class="form-wrap" v-if="goodsClass == 1">
						<text class="label">体积</text>
						<input class="uni-input" v-model="item.volume" type="digit" placeholder="0.00" />
						<text class="unit">m³</text>
					</view>
					<view class="form-wrap">
						<text class="label">规格编码</text>
						<input class="uni-input" v-model="item.sku_no" placeholder="请输入规格编码" />
					</view>
					<view class="form-wrap more-wrap">
						<text class="label">默认展示</text>
						<ns-switch class="switch" :checked="item.is_default == 1" @change="isDefault(index)"></ns-switch>
					</view>
				</view>
				<view class="shrink" @click="changeMore(index)">
					<text>{{ item.shrink ? '更多选项' : '收起' }}</text>
					<text class="iconfont" :class="item.shrink ? 'iconunfold' : 'iconfold'"></text>
				</view>
			</view>
		</view>
		<view class="footer-wrap"><button type="primary" @click="save()">保存</button></view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import nsSwitch from '@/components/ns-switch/ns-switch.vue';
import shmilyDragImage from '@/components/shmily-drag-image/shmily-drag-image.vue';
export default {
	data() {
		return {
			// 商品类型:1 实物商品 ，2 虚拟商品
			goodsClass: 1,
			goodsSpecFormat: [],
			goodsSkuData: [],
			tempGoodsSkuData: [],

			// 商品图片高度
			skuImgHeight: [165]
		};
	},
	components: {
		nsSwitch,
		shmilyDragImage
	},
	onLoad(option) {
		this.goodsClass = option.goods_class || 1;
		this.goodsSpecFormat = uni.getStorageSync('editGoodsSpecFormat') ? JSON.parse(uni.getStorageSync('editGoodsSpecFormat')) : [];
		this.tempGoodsSkuData = uni.getStorageSync('editGoodsSkuData') ? JSON.parse(uni.getStorageSync('editGoodsSkuData')) : [];
		this.handlerGoodsSkuData();
		// 临时选择的商品图片
		uni.removeStorageSync('selectedAlbumImg');
		uni.removeStorageSync('selectedAlbumImgTemp');
	},
	onShow() {
		this.refreshData();
	},
	methods: {
		// 处理商品规格数据
		handlerGoodsSkuData() {
			var arr = this.goodsSpecFormat;
			this.goodsSkuData = [];
			for (var ele_1 of arr) {
				var item_prop_arr = [];
				if (this.goodsSkuData.length > 0) {
					for (var ele_2 of this.goodsSkuData) {
						for (var ele_3 of ele_1['value']) {
							var sku_spec_format = JSON.parse(JSON.stringify(ele_2.sku_spec_format)); //防止对象引用
							sku_spec_format.push(ele_3);
							var item = {
								spec_name: `${ele_2.spec_name} ${ele_3.spec_value_name}`,
								sku_no: '',
								sku_spec_format: sku_spec_format,
								price: '',
								market_price: '',
								cost_price: '',
								stock: '',
								stock_alarm: '',
								sku_image: '',
								sku_images: '',
								sku_images_arr: [],
								is_default: 0
							};
							if (this.goodsClass == 1) {
								item.weight = '';
								item.volume = '';
							}
							item_prop_arr.push(item);
						}
					}
				} else {
					for (var ele_3 of ele_1['value']) {
						var spec_name = ele_3.spec_value_name;
						var item = {
							spec_name: spec_name,
							sku_no: '',
							sku_spec_format: [ele_3],
							price: '',
							market_price: '',
							cost_price: '',
							stock: '',
							stock_alarm: '',
							sku_image: '',
							sku_images: '',
							sku_images_arr: [],
							is_default: 0
						};
						if (this.goodsClass == 1) {
							item.weight = '';
							item.volume = '';
						}
						item_prop_arr.push(item);
					}
				}
				this.goodsSkuData = item_prop_arr.length > 0 ? item_prop_arr : this.goodsSkuData;
			}

			// 比对已存在的规格项/值，并且赋值
			for (var i = 0; i < this.tempGoodsSkuData.length; i++) {
				for (var j = 0; j < this.goodsSkuData.length; j++) {
					if (this.tempGoodsSkuData[i].spec_name == this.goodsSkuData[j].spec_name) {
						this.goodsSkuData[j] = this.tempGoodsSkuData[i];
						break;
					}
				}
			}

			this.goodsSkuData.forEach((item, key) => {
				this.$set(item, 'shrink', true);
				if (item.sku_images) {
					item.sku_images_arr = item.sku_images.split(',');
				}
			});

			setTimeout(() => {
				if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				this.$forceUpdate();
			}, 300);
		},
		uploadImgCallback(data) {
			if (data.height == '') return;
			this.skuImgHeight[data.index] = parseFloat(data.height.replace('px', '')) + 80;
			if (this.goodsSkuData[data.index].sku_images_arr) {
				this.goodsSkuData[data.index].sku_image = this.goodsSkuData[data.index].sku_images_arr[0];
				this.goodsSkuData[data.index].sku_images = this.goodsSkuData[data.index].sku_images_arr.toString();
			}
			this.$forceUpdate();
			if (data.isLoad && this.$refs.loadingCover) this.$refs.loadingCover.hide();
		},
		refreshData() {
			var selectedAlbumImg = uni.getStorageSync('selectedAlbumImg');
			if (selectedAlbumImg) {
				uni.setStorageSync('selectedAlbumImgTemp', selectedAlbumImg);
				selectedAlbumImg = JSON.parse(selectedAlbumImg);
				this.goodsSkuData[selectedAlbumImg.index].sku_images_arr = selectedAlbumImg.list.split(',');
				this.goodsSkuData[selectedAlbumImg.index].sku_image = this.goodsSkuData[selectedAlbumImg.index].sku_images_arr[0];
				this.goodsSkuData[selectedAlbumImg.index].sku_images = this.goodsSkuData[selectedAlbumImg.index].sku_images_arr.toString();
				this.$refs.skuShmilyDragImg[selectedAlbumImg.index].refresh(() => {
					uni.removeStorageSync('selectedAlbumImg');
				});
				this.$forceUpdate();
			}
		},
		isDefault(index) {
			this.goodsSkuData.forEach((item, key) => {
				if (index == key) {
					item.is_default = item.is_default == 1 ? 0 : 1;
				} else {
					item.is_default = 0;
				}
			});
			this.$forceUpdate();
		},
		// 展开收缩更多选项
		changeMore(index){
			this.goodsSkuData[index].shrink = !this.goodsSkuData[index].shrink;
		},
		verify() {
			var flag = true;
			for (var i = 0; i < this.goodsSkuData.length; i++) {
				var item = this.goodsSkuData[i];
				if (item.price.length == 0) {
					this.$util.showToast({ title: `请输入[第${i + 1}个规格]的销售价` });
					flag = false;
					break;
				}

				if (isNaN(item.price) || !this.$util.data().regExp.digit.test(item.price)) {
					this.$util.showToast({ title: `[第${i + 1}个规格的销售价]格式输入错误` });
					flag = false;
					break;
				}

				if (item.stock.length == 0) {
					this.$util.showToast({ title: `请输入[第${i + 1}个规格]的库存` });
					flag = false;
					break;
				}

				if (isNaN(item.stock) || !this.$util.data().regExp.number.test(item.stock)) {
					this.$util.showToast({ title: `[第${i + 1}个规格的库存]格式输入错误` });
					flag = false;
					break;
				}

				if (item.stock_alarm.length > 0) {
					if (isNaN(item.stock_alarm) || !this.$util.data().regExp.number.test(item.stock_alarm)) {
						this.$util.showToast({ title: `[第${i + 1}个规格的库存预警]格式输入错误` });
						flag = false;
						break;
					}
					if (parseInt(item.stock_alarm) != 0 && parseInt(item.stock_alarm) === item.stock) {
						this.$util.showToast({ title: `[第${i + 1}个规格的库存预警]不能等于库存数量` });
						flag = false;
						break;
					}
					if (parseInt(item.stock_alarm) > item.stock) {
						this.$util.showToast({ title: `[第${i + 1}个规格的库存预警]不能超过库存数量` });
						flag = false;
						break;
					}
				}

				if (isNaN(item.market_price) || !this.$util.data().regExp.digit.test(item.market_price)) {
					this.$util.showToast({ title: `[第${i + 1}个规格的划线价]格式输入错误` });
					flag = false;
					break;
				}

				if (isNaN(item.cost_price) || !this.$util.data().regExp.digit.test(item.cost_price)) {
					this.$util.showToast({ title: `[第${i + 1}个规格的成本价]格式输入错误` });
					flag = false;
					break;
				}

				if (this.goodsClass == 1) {
					if (isNaN(item.weight) || !this.$util.data().regExp.digit.test(item.weight)) {
						this.$util.showToast({ title: `[第${i + 1}个规格的重量kg]格式输入错误` });
						flag = false;
						break;
					}

					if (isNaN(item.volume) || !this.$util.data().regExp.digit.test(item.volume)) {
						this.$util.showToast({ title: `[第${i + 1}个规格的体积(m³]格式输入错误` });
						flag = false;
						break;
					}
				}
			}
			return flag;
		},
		save() {
			if (!this.verify()) return;
			uni.setStorageSync('editGoodsSkuData', JSON.stringify(this.goodsSkuData));
			// 临时选择的商品图片
			uni.removeStorageSync('selectedAlbumImg');
			uni.removeStorageSync('selectedAlbumImgTemp');
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style lang="scss">
@import '../css/edit.scss';
.sku-list {
	margin-bottom: 160rpx;
	.item-inner {
		position: relative;
		margin: $margin-updown $margin-both 0;
		overflow: hidden;
		.item-wrap {
			margin-bottom: 80rpx;
			background-color: #fff;
		}
		.item-shrink {
			overflow: hidden;
			height: 620rpx;
		}
		.form-wrap {
			.spec-name {
				vertical-align: middle;
				display: inline-block;
				flex: 1;
				text-align: right;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: pre;
			}
			&.more-wrap {
				.switch {
					position: absolute;
					right: 0;
					margin-right: $margin-both;
					padding-right: $padding;
				}
			}
		}
		.shrink {
			position: absolute;
			left: 0;
			right: 0;
			bottom: 12rpx;
			display: flex;
			align-items: center;
			justify-content: center;
			padding-bottom: 20rpx;
			height: 52rpx;
			background-color: #fff;
			color: $color-tip;
			.iconfont {
				font-size: 36rpx;
				margin-left: 6rpx;
			}
		}
	}
}
</style>
