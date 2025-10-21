<template>
	<view class="container">
		<view class="goods-category-wrap">
			<view class="item-wrap" v-for="(item, index) in list" :key="index">
				<view class="first-category" @click="selectCategory(item)">
					<template v-if="item.child_list && item.child_list.length">
						<text class="action iconfont" :class="[item.isShow ? 'iconjian' : 'iconjia1 color-base-bg']" @click.stop="changeShow(index)"></text>
					</template>
					<text class="label">{{ item.category_name }}</text>
					<view class="checkbox iconfont" :class="item.selected ? 'iconyuan_checked color-base-text' : 'iconyuan_checkbox'"></view>
				</view>
				<view
					v-if="item.child_list && item.child_list.length && item.isShow"
					class="second-category"
					v-for="(secondItem, secondIndex) in item.child_list"
					:key="secondIndex"
					@click="selectCategory(item, secondItem)"
				>
					<template v-if="secondItem.child_list && secondItem.child_list.length">
						<text class="action iconfont" :class="[secondItem.isShow ? 'iconjian' : 'iconjia1 color-base-bg']" @click.stop="changeShow(index, secondIndex)"></text>
					</template>
					<text class="label">{{ secondItem.category_name }}</text>
					<view class="checkbox iconfont" :class="secondItem.selected ? 'iconyuan_checked color-base-text' : 'iconyuan_checkbox'"></view>
					<view
						v-if="secondItem.child_list && secondItem.child_list.length && secondItem.isShow"
						class="third-category"
						v-for="(thirdItem, third_index) in secondItem.child_list"
						:key="third_index"
						@click.stop="selectCategory(item, secondItem, thirdItem)"
					>
						<text class="label">{{ thirdItem.category_name }}</text>
						<view class="checkbox iconfont" :class="thirdItem.selected ? 'iconyuan_checked color-base-text' : 'iconyuan_checkbox'"></view>
					</view>
				</view>
			</view>
		</view>
		<view class="footer-wrap"><button type="primary" @click="save()">保存</button></view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			categoryId: [0, 0, 0],
			categoryName: ['', '', ''],
			lastLevel: 1,
			list: [],
			selectCategoryId: []
		};
	},
	onLoad(option) {
		this.selectCategoryId = option.category_id ? option.category_id.split(',') : [];
	},
	onShow() {
		this.getCategoryTree();
	},
	methods: {
		// 获取商品分类树状结构
		getCategoryTree() {
			this.$api.sendRequest({
				url: '/shopapi/goods/getCategoryTree',
				success: res => {
					if (res.data) {
						this.list = res.data;
						this.list.forEach((item, index) => {
							item.selected = this.selectCategoryId.indexOf(item.category_id.toString()) != -1;
							if (item.selected) {
								this.categoryId[0] = item.category_id;
								this.categoryName[0] = item.category_name;
							}
							if (item.child_list) {
								item.isShow = item.selected;
								item.child_list.forEach(secondItem => {
									secondItem.selected = this.selectCategoryId.indexOf(secondItem.category_id.toString()) != -1;
									if (secondItem.selected) {
										this.categoryId[1] = secondItem.category_id;
										this.categoryName[1] = secondItem.category_name;
									}
									if (secondItem.child_list) {
										secondItem.isShow = secondItem.selected;
										secondItem.child_list.forEach(thirdItem => {
											thirdItem.selected = this.selectCategoryId.indexOf(thirdItem.category_id.toString()) != -1;
											if (thirdItem.selected) {
												this.categoryId[2] = thirdItem.category_id;
												this.categoryName[2] = thirdItem.category_name;
											}
										});
									}
								});
							}
						});
					}
				}
			});
		},
		/**
		 * 切换一二级分类展开缩回
		 * @param {Object} firstIndex 一级分类下标
		 * @param {Object} secondIndex 二级分类下标
		 */
		changeShow(firstIndex, secondIndex) {
			if (secondIndex != undefined) this.list[firstIndex].child_list[secondIndex].isShow = !this.list[firstIndex].child_list[secondIndex].isShow;
			else this.list[firstIndex].isShow = !this.list[firstIndex].isShow;
			this.$forceUpdate();
		},
		/**
		 * 选择分类
		 * @param {Object} firstCategory
		 * @param {Object} secondCategory
		 * @param {Object} thirdCategory
		 */
		selectCategory(firstCategory, secondCategory, thirdCategory) {
			this.categoryId[0] = firstCategory.category_id;
			this.categoryName[0] = firstCategory.category_name;

			if (secondCategory) {
				this.categoryId[1] = secondCategory.category_id;
				this.categoryName[1] = secondCategory.category_name;
			} else {
				this.categoryId[1] = 0;
				this.categoryName[1] = '';
			}

			if (thirdCategory) {
				this.categoryId[2] = thirdCategory.category_id;
				this.categoryName[2] = thirdCategory.category_name;
			} else {
				this.categoryId[2] = 0;
				this.categoryName[2] = '';
			}
			this.lastLevel = 1;
			this.list.forEach((item, index) => {
				item.selected = this.categoryId[0] == item.category_id;
				if (item.child_list) {
					if (item.selected) this.lastLevel = 2;
					item.child_list.forEach((secondItem, secondIndex) => {
						secondItem.selected = this.categoryId[1] == secondItem.category_id;
						if (secondItem.child_list) {
							if (secondItem.selected) this.lastLevel = 3;
							secondItem.child_list.forEach((thirdItem, third_index) => {
								thirdItem.selected = this.categoryId[2] == thirdItem.category_id;
							});
						}
					});
				}
			});

			this.$forceUpdate();
		},
		verify() {
			var flag = true;
			if (this.categoryId[0] == 0) {
				this.$util.showToast({ title: `请选择一级商品分类` });
				flag = false;
			} else if (this.lastLevel == 2 && this.categoryId[1] == 0) {
				this.$util.showToast({ title: `请选择二级商品分类` });
				flag = false;
			} else if (this.lastLevel == 3 && this.categoryId[2] == 0) {
				this.$util.showToast({ title: `请选择三级商品分类` });
				flag = false;
			}
			return flag;
		},
		save() {
			if (!this.verify()) return;

			for (var i = 0; i < this.categoryId.length; i++) {
				if (this.categoryId[i] == 0) {
					this.categoryId.splice(i, 1);
					i = 0;
				}
			}

			for (var i = 0; i < this.categoryName.length; i++) {
				if (this.categoryName[i] == 0) {
					this.categoryName.splice(i, 1);
					i = 0;
				}
			}

			uni.setStorageSync('editGoodsCategoryId', this.categoryId.toString());
			uni.setStorageSync('editGoodsCategoryName', this.categoryName.join(' / ').toString());
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style lang="scss">
@import '../css/edit.scss';
.goods-category-wrap {
	margin: $margin-updown $margin-both 160rpx $margin-both;
	.item-wrap {
		border-radius: $border-radius;
		margin-bottom: $margin-updown;
		.action {
			background-color: $color-disabled;
			border-radius: 50%;
			color: #fff;
			width: 36rpx;
			height: 36rpx;
			line-height: 36rpx;
			display: inline-block;
			text-align: center;
			font-weight: bold;
			margin-right: 20rpx;
			vertical-align: middle;
		}
		.checkbox {
			vertical-align: middle;
			display: inline-block;
			text-align: right;
			font-size: 40rpx;
			float: right;
			line-height: 100rpx;
			color: $color-tip;
		}
		.label {
			vertical-align: middle;
			overflow: hidden;
			white-space: pre;
			text-overflow: ellipsis;
			width: 80%;
			display: inline-block;
		}
		.first-category,
		.second-category,
		.third-category {
			height: 100rpx;
			line-height: 100rpx;
			padding: 0 30rpx;
			border-bottom: 1px solid $color-line;
		}
		.second-category {
			margin-left: 60rpx;
			padding-left: 0;
			height: initial;
		}
		.third-category {
			margin-left: 80rpx;
			padding-left: 0;
			padding-right: 0;
		}
	}
}
</style>
