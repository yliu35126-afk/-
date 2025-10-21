<template>
	<view>
		<view class="item-wrap">
			<view class="form-wrap more-wrap">
				<text class="label">参数模板</text>
				<picker class="selected" @change="bindPickerChange" :value="index" :range="pickedArr">
					<view class="uni-input">{{ goodsAttrName ? goodsAttrName : '请选择参数模板' }}</view>
				</picker>
				<text class="iconfont iconright"></text>
			</view>
			<view class="form-wrap" v-for="(item, index) in attrValueList" :key="index" :class="{ 'attr-value': item.attr_type != 3 }">
				<text class="action iconfont iconjian" @click="deleteAttr(index)"></text>
				<template v-if="item.attr_class_id > 0">
					<text class="label">{{ item.attr_name }}</text>
					<input v-if="item.attr_type == 3" class="uni-input" v-model="item.attr_value_name" :placeholder="'请输入' + item.attr_name" maxlength="50" />
				</template>
				<template v-else>
					<input class="uni-input diy-attr-input" v-model="item.attr_name" :placeholder="'请输入参数名'" maxlength="50" />
					<input class="uni-input" v-model="item.attr_value_name" :placeholder="'请输入参数值'" maxlength="50" />
				</template>
				<view v-if="item.attr_type != 3" class="select-box">
					<view v-for="(valueItem, valueIndex) in item.attr_value_format" :key="valueIndex" @click="selectBox(item.attr_type, index, valueIndex)">
						<text v-if="item.attr_type == 1" class="radio iconfont" :class="valueItem.checked ? 'iconyuan_checked color-base-text' : 'iconyuan_checkbox'"></text>
						<text v-else-if="item.attr_type == 2" class="checkbox iconfont" :class="valueItem.checked ? 'iconfuxuankuang1 color-base-text' : 'iconfuxuankuang2'"></text>
						<text class="value">{{ valueItem.attr_value_name }}</text>
					</view>
				</view>
			</view>
		</view>
		<view @click="addAttr()" class="color-base-text add-attr">+添加参数</view>
		<view class="footer-wrap"><button type="primary" @click="save()">保存</button></view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			goodsAttrClass: 0,
			goodsAttrName: '',
			goodsAttrFormat: [],
			attrClassList: [],
			attrValueList: [],
			index: 0,
			pickedArr: []
		};
	},
	onLoad(option) {
		this.goodsAttrClass = uni.getStorageSync('editGoodsAttrClass') || 0;
		this.goodsAttrName = uni.getStorageSync('editGoodsAttrName') || '';
		this.goodsAttrFormat = uni.getStorageSync('editGoodsAttrFormat') ? JSON.parse(uni.getStorageSync('editGoodsAttrFormat')) : [];
		this.getAttrClassList();
	},
	onShow() {},
	methods: {
		// 获取商品参数
		getAttrClassList() {
			this.$api.sendRequest({
				url: '/shopapi/goods/getAttrClassList',
				success: res => {
					if (res.data) {
						this.attrClassList = res.data;
						this.attrClassList.forEach((item, key) => {
							this.pickedArr.push(item.class_name);
							if (this.goodsAttrClass && this.goodsAttrClass == item.class_id) {
								this.index = key;
							}
						});
						if (this.goodsAttrClass) {
							this.goodsAttrName = this.attrClassList[this.index].class_name;
							this.getAttributeList();
						} else {
							if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
						}
					}
				}
			});
		},
		// 获取商品属性列表
		getAttributeList() {
			this.$api.sendRequest({
				url: '/shopapi/goods/getAttributeList',
				data: { attr_class_id: this.goodsAttrClass },
				success: res => {
					if (res.data) {
						this.attrValueList = res.data;
						this.goodsAttrFormat.forEach(item => {
							if (item.attr_id < 0) {
								item.attr_type = 3;
								item.attr_value_format = '';
								this.attrValueList.splice(0, 0, item);
							}
						});
						this.attrValueList.forEach((item, index) => {
							// 属性类型（1.单选 2.多选 3. 输入）
							item.sort = index;
							switch (item.attr_type) {
								case 1:
								case 2:
									item.attr_value_format.forEach(value => {
										value.checked = false;
										this.goodsAttrFormat.forEach(attr => {
											if (value.attr_value_id == attr.attr_value_id) {
												value.checked = true;
											}
										});
									});
									break;
								case 3:
									if (item.attr_id > 0) item.attr_value_name = '';
									this.goodsAttrFormat.forEach(attr => {
										if (item.attr_id == attr.attr_id) {
											item.attr_value_name = attr.attr_value_name;
										}
									});
									break;
							}
						});

						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				}
			});
		},
		bindPickerChange(e) {
			if (this.attrClassList.length == 0) return;
			this.index = e.target.value;
			this.goodsAttrClass = this.attrClassList[this.index].class_id;
			this.goodsAttrName = this.attrClassList[this.index].class_name;
			for (var i = 0; i < this.goodsAttrFormat.length; i++) {
				if (this.goodsAttrFormat[i].attr_id > 0) {
					this.goodsAttrFormat.splice(i, 1);
					i = 0;
				}
			}
			this.getAttributeList();
		},
		selectBox(attrType, index, valueIndex) {
			// 属性类型（1.单选 2.多选 3. 输入）
			if (attrType == 1) {
				this.attrValueList[index].attr_value_format.forEach((item, key) => {
					if (valueIndex != key) item.checked = false;
				});
			}
			this.attrValueList[index].attr_value_format[valueIndex].checked = !this.attrValueList[index].attr_value_format[valueIndex].checked;
			this.$forceUpdate();
		},
		// 添加自定义属性
		addAttr() {
			var i = this.attrValueList.length;
			var obj = {
				attr_name: '',
				attr_value_name: '',
				attr_type: 3,
				attr_value_format: '',
				sort: 0
			};
			obj.attr_class_id = -(i + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds()));
			obj.attr_id = obj.attr_class_id + -(i + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds()));
			obj.attr_value_id = obj.attr_id + -(i + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds()));
			this.attrValueList.push(obj);
		},
		deleteAttr(index) {
			uni.showModal({
				title: '操作提示',
				content: '确定要删除此参数吗？',
				success: res => {
					if (res.confirm) {
						this.attrValueList.splice(index, 1);
						this.goodsAttrFormat.splice(index, 1);
					}
				}
			});
		},
		save() {
			this.goodsAttrFormat = [];
			this.attrValueList.forEach((item, index) => {
				var obj = {
					attr_class_id: item.attr_class_id,
					attr_id: item.attr_id,
					attr_name: item.attr_name,
					attr_value_id: '',
					attr_value_name: '',
					sort: index
				};

				// 属性类型（1.单选 2.多选 3. 输入）
				switch (item.attr_type) {
					case 1:
					case 2:
						item.attr_value_format.forEach(value => {
							if (value.checked) {
								// 每次添加需要重新new
								obj = {
									attr_class_id: item.attr_class_id,
									attr_id: item.attr_id,
									attr_name: item.attr_name,
									attr_value_id: value.attr_value_id,
									attr_value_name: value.attr_value_name,
									sort: index
								};
								this.goodsAttrFormat.push(obj);
							}
						});
						break;
					case 3:
						if (item.attr_value_name) {
							obj.attr_value_name = item.attr_value_name;
							this.goodsAttrFormat.push(obj);
						}
						break;
				}
			});
			uni.setStorageSync('editGoodsAttrClass', this.goodsAttrClass);
			uni.setStorageSync('editGoodsAttrName', this.goodsAttrName);
			uni.setStorageSync('editGoodsAttrFormat', JSON.stringify(this.goodsAttrFormat));
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
	.form-wrap {
		&.attr-value {
			height: initial;
			display: block;
			padding-bottom: $padding;
		}
		.select-box {
			vertical-align: middle;
			display: inline-block;
			flex: 1;
			text-align: right;
			color: $color-tip;
			font-size: 40rpx;
			display: flex;
			flex-wrap: wrap;
			> view {
				margin-right: $margin-both;
				&:last-child {
					margin-right: 0;
				}
				.value {
					margin-left: 10rpx;
					max-width: 400rpx;
					display: inline-block;
					vertical-align: middle;
					white-space: pre;
					overflow: hidden;
					text-overflow: ellipsis;
				}
			}
		}
		.radio,
		.checkbox {
			vertical-align: middle;
			display: inline-block;
			flex: 1;
			text-align: right;
			color: $color-tip;
			font-size: 40rpx;
		}
		.diy-attr-input {
			text-align: left;
			margin-right: $margin-both;
		}
	}
}
.add-attr {
	text-align: center;
	background-color: #fff;
	height: 100rpx;
	line-height: 100rpx;
	border-radius: $border-radius;
	margin: 20rpx $margin-both 0;
}
.footer-wrap {
	position: initial;
	background-color: transparent;
}
</style>
