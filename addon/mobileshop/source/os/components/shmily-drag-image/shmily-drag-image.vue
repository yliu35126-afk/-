<template>
	<view class="con">
		<movable-area class="area" :style="{ height: areaHeight ? areaHeight : imageHeight + 'rpx' }" @mouseenter="mouseenter" @mouseleave="mouseleave">
			<block v-for="(item, index) in imageList" :key="item.id">
				<movable-view
					class="view"
					:x="item.x"
					:y="item.y"
					direction="all"
					:damping="40"
					:disabled="item.disable"
					@change="onChange($event, item)"
					@touchstart="touchstart(item)"
					@mousedown="touchstart(item)"
					@touchend="touchend(item)"
					@mouseup="touchend(item)"
					:style="{ width: viewWidth + 'px', height: viewWidth + 'px', 'z-index': item.zIndex, opacity: item.opacity }"
				>
					<view class="area-con" :style="{ width: childWidth, height: childWidth, transform: 'scale(' + item.scale + ')' }">
						<image class="pre-image" :src="$util.img(item.src)" mode="aspectFit"></image>
						<view
							class="del-con"
							@click="delImage(item, index)"
							@touchstart.stop="delImageMp(item, index)"
							@touchend.stop="nothing()"
							@mousedown.stop="nothing()"
							@mouseup.stop="nothing()"
						>
							<view class="del-wrap iconfont iconclose"></view>
						</view>
					</view>
				</movable-view>
			</block>
			<view class="add" v-if="imageList.length < number" :style="{ top: add.y, left: add.x, width: viewWidth + 'px', height: viewWidth + 'px' }" @click="addImages">
				<view class="add-wrap iconfont iconadd1" :style="{ width: childWidth, height: childWidth }"></view>
			</view>
		</movable-area>

		<!-- 选择图片 -->
		<uni-popup ref="choosePicturePopup" type="bottom">
			<view class="popup choose-picture" @touchmove.prevent.stop>
				<view class="popup-body" :class="{ 'safe-area': isIphoneX }">
					<view class="select-wrap">
						<view class="item color-base-text" @click="goAlbum()">从相册图库选择</view>
						<view class="item" @click="photograph()">手机拍照</view>
						<view class="item" @click="selectPhonePhoto()">从手机相册选择</view>
					</view>
					<view class="item cancle" @click="closeChoosePicturePop()">取消</view>
				</view>
			</view>
		</uni-popup>
	</view>
</template>

<script>
export default {
	data() {
		return {
			imageList: [],
			width: 0,
			add: {
				x: 0,
				y: 0
			},
			colsValue: 0,
			viewWidth: 0,
			tempItem: null,
			timer: null,
			changeStatus: true,
			preStatus: true,
			isIphoneX: false
		};
	},
	props: {
		// 返回排序后图片
		list: {
			type: Array,
			default: function() {
				return [];
			}
		},
		// 选择图片数量限制
		number: {
			type: Number,
			default: 6
		},
		// 选择图片的数组下标
		index: {
			type: Number,
			default: 0
		},
		// 图片父容器宽度（实际显示的图片宽度为 imageWidth / 1.1 ），单位 rpx
		imageWidth: {
			type: Number,
			default: 230
		},
		imageHeight: {
			type: Number,
			default: 230
		},
		// 图片列数（cols > 0 则 imageWidth 无效）
		cols: {
			type: Number,
			default: 0
		},
		// 图片周围空白填充，单位 rpx
		padding: {
			type: Number,
			default: 10
		},
		// 拖动图片时放大倍数 [0, ∞)
		scale: {
			type: Number,
			default: 1.1
		},
		// 拖动图片时不透明度
		opacity: {
			type: Number,
			default: 0.7
		},
		// 自定义添加（需配合 @aaddImage 事件使用）
		custom: {
			type: Boolean,
			default: false
		},
		uploadMethod: {
			type: String,
			default: 'image'
		},
		// 开启选择图片方式
		openSelectMode: {
			type: Boolean,
			default: false
		},
		// 是否需要等待数据
		isAWait: {
			type: Boolean,
			default: false
		}
	},
	computed: {
		areaHeight() {
			if (this.isAWait && this.colsValue == 0) return '';
			var height = '';
			if (this.imageList.length < this.number) {
				height = Math.ceil((this.imageList.length + 1) / this.colsValue) * this.viewWidth;
			} else {
				height = Math.ceil(this.imageList.length / this.colsValue) * this.viewWidth;
			}
			if (height != 'Infinity') return height + 'px';
			else return '';
		},
		childWidth() {
			return this.viewWidth - this.rpx2px(this.padding) * 2 + 'px';
		}
	},
	created() {
		this.width = uni.getSystemInfoSync().windowWidth;
		this.viewWidth = this.rpx2px(this.imageWidth);
		this.isIphoneX = this.$util.uniappIsIPhoneX();
	},
	mounted() {
		this.refresh();
	},
	methods: {
		refresh() {
			var flag = false;
			var time = setInterval(() => {
				if (this.isAWait && this.list.length == 0) return;
				this.imageList = [];
				const query = uni.createSelectorQuery().in(this);
				query.select('.area').boundingClientRect(data => {
					this.colsValue = Math.floor(data.width / this.viewWidth);
					if (this.cols > 0) {
						this.colsValue = this.cols;
						this.viewWidth = data.width / this.cols;
					}
				});
				query.exec();
				if ((this.isAWait && this.colsValue > 0) || !this.isAWait) {
					for (let item of this.list) {
						this.addProperties(item);
					}
				}
				if (this.areaHeight) {
					flag = true;
					if (flag) {
						this.$emit('callback', { height: this.areaHeight, index: this.index, isLoad: true });
						clearInterval(time);
					}
				}
			}, 10);
		},
		onChange(e, item) {
			if (!item) return;
			item.oldX = e.detail.x;
			item.oldY = e.detail.y;
			if (e.detail.source === 'touch') {
				if (item.moveEnd) {
					item.offset = Math.sqrt(Math.pow(item.oldX - item.absX * this.viewWidth, 2) + Math.pow(item.oldY - item.absY * this.viewWidth, 2));
				}
				let x = Math.floor((e.detail.x + this.viewWidth / 2) / this.viewWidth);
				if (x >= this.colsValue) return;
				let y = Math.floor((e.detail.y + this.viewWidth / 2) / this.viewWidth);
				let index = this.colsValue * y + x;
				if (item.index != index && index < this.imageList.length) {
					this.changeStatus = false;
					for (let obj of this.imageList) {
						if (item.index > index && obj.index >= index && obj.index < item.index) {
							this.change(obj, 1);
						} else if (item.index < index && obj.index <= index && obj.index > item.index) {
							this.change(obj, -1);
						} else if (obj.id != item.id) {
							obj.offset = 0;
							obj.x = obj.oldX;
							obj.y = obj.oldY;
							setTimeout(() => {
								this.$nextTick(() => {
									obj.x = obj.absX * this.viewWidth;
									obj.y = obj.absY * this.viewWidth;
								});
							}, 0);
						}
					}
					item.index = index;
					item.absX = x;
					item.absY = y;
					this.sortList();
				}
			}
		},
		change(obj, i) {
			obj.index += i;
			obj.offset = 0;
			obj.x = obj.oldX;
			obj.y = obj.oldY;
			obj.absX = obj.index % this.colsValue;
			obj.absY = Math.floor(obj.index / this.colsValue);
			setTimeout(() => {
				this.$nextTick(() => {
					obj.x = obj.absX * this.viewWidth;
					obj.y = obj.absY * this.viewWidth;
				});
			}, 0);
		},
		touchstart(item) {
			this.imageList.forEach(v => {
				v.zIndex = v.index + 9;
			});
			item.zIndex = 99;
			item.moveEnd = true;
			this.tempItem = item;
			this.timer = setTimeout(() => {
				item.scale = this.scale;
				item.opacity = this.opacity;
				clearTimeout(this.timer);
				this.timer = null;
			}, 200);
		},
		touchend(item) {
			this.previewImage(item);
			item.scale = 1;
			item.opacity = 1;
			item.x = item.oldX;
			item.y = item.oldY;
			item.offset = 0;
			item.moveEnd = false;
			setTimeout(() => {
				this.$nextTick(() => {
					item.x = item.absX * this.viewWidth;
					item.y = item.absY * this.viewWidth;
					this.tempItem = null;
					this.changeStatus = true;
				});
			}, 0);
		},
		previewImage(item) {
			if (this.timer && this.preStatus && this.changeStatus && item.offset < 28.28) {
				clearTimeout(this.timer);
				this.timer = null;
				this.list.forEach((item, i) => {
					this.list[i] = this.$util.img(item);
				});
				let src = this.list.findIndex(v => v === item.src);
				uni.previewImage({
					urls: this.list,
					current: src,
					success: () => {
						this.preStatus = false;
						setTimeout(() => {
							this.preStatus = true;
						}, 600);
					}
				});
			} else if (this.timer) {
				clearTimeout(this.timer);
				this.timer = null;
			}
		},
		mouseenter() {
			//#ifdef H5
			this.imageList.forEach(v => {
				v.disable = false;
			});
			//#endif
		},
		mouseleave() {
			//#ifdef H5
			if (this.tempItem) {
				this.imageList.forEach(v => {
					v.disable = true;
					v.zIndex = v.index + 9;
					v.offset = 0;
					v.moveEnd = false;
					if (v.id == this.tempItem.id) {
						if (this.timer) {
							clearTimeout(this.timer);
							this.timer = null;
						}
						v.scale = 1;
						v.opacity = 1;
						v.x = v.oldX;
						v.y = v.oldY;
						this.$nextTick(() => {
							v.x = v.absX * this.viewWidth;
							v.y = v.absY * this.viewWidth;
							this.tempItem = null;
						});
					}
				});
				this.changeStatus = true;
			}
			//#endif
		},
		addImages() {
			if (this.custom) {
				this.$emit('addImage');
			} else if (this.openSelectMode) {
				this.openChoosePicturePop();
			} else {
				this.selectPhonePhoto(['album', 'camera']);
			}
		},
		// 选择图片
		openChoosePicturePop() {
			this.$refs.choosePicturePopup.open();
		},
		closeChoosePicturePop() {
			this.$refs.choosePicturePopup.close();
		},
		// 从相册图库选择
		goAlbum() {
			this.closeChoosePicturePop();
			// this.number - this.imageList.length
			var temp = {
				list: this.list.toString(),
				index: this.index
			};
			uni.setStorageSync('selectedAlbumImgTemp', JSON.stringify(temp));
			this.$util.redirectTo('/pages/goods/album', { number: this.number });
		},
		// 手机拍照
		photograph() {
			this.closeChoosePicturePop();
			let checkNumber = this.number - this.imageList.length;
			this.$util.upload(
				{
					number: checkNumber,
					path: this.uploadMethod,
					sourceType: ['camera']
				},
				res => {
					for (var i = 0; i < res.length; i++) {
						this.addProperties(res[i]);
					}
					this.$emit('callback', { height: this.areaHeight, index: this.index });
				}
			);
		},
		// 从手机相册选择
		selectPhonePhoto(sourceType) {
			sourceType = sourceType || ['album'];
			this.closeChoosePicturePop();
			let checkNumber = this.number - this.imageList.length;
			this.$util.upload(
				{
					number: checkNumber,
					path: this.uploadMethod,
					sourceType: sourceType
				},
				res => {
					for (var i = 0; i < res.length; i++) {
						this.addProperties(res[i]);
					}
					var time = setInterval(() => {
						if (this.areaHeight) {
							this.$emit('callback', { height: this.areaHeight, index: this.index });
							clearInterval(time);
						}
					}, 10);
				}
			);
		},
		addImage(image) {
			this.addProperties(image);
		},
		delImage(item, index) {
			this.imageList.splice(index, 1);
			for (let obj of this.imageList) {
				if (obj.index > item.index) {
					obj.index -= 1;
					obj.x = obj.oldX;
					obj.y = obj.oldY;
					obj.absX = obj.index % this.colsValue;
					obj.absY = Math.floor(obj.index / this.colsValue);
					this.$nextTick(() => {
						obj.x = obj.absX * this.viewWidth;
						obj.y = obj.absY * this.viewWidth;
					});
				}
			}
			this.add.x = (this.imageList.length % this.colsValue) * this.viewWidth + 'px';
			this.add.y = Math.floor(this.imageList.length / this.colsValue) * this.viewWidth + 'px';
			this.$emit('callback', { height: this.areaHeight, index: this.index });
			this.sortList();
		},
		delImageMp(item, index) {
			//#ifdef MP
			this.delImage(item, index);
			//#endif
		},
		sortList() {
			let list = this.imageList.slice();
			list.sort((a, b) => {
				return a.index - b.index;
			});
			for (let i = 0; i < list.length; i++) {
				list[i] = list[i].src;
			}
			this.$emit('update:list', list);
		},
		addProperties(item) {
			let absX = this.imageList.length % this.colsValue;
			let absY = Math.floor(this.imageList.length / this.colsValue);
			let x = absX * this.viewWidth;
			let y = absY * this.viewWidth;
			this.imageList.push({
				src: item,
				x,
				y,
				oldX: x,
				oldY: y,
				absX,
				absY,
				scale: 1,
				zIndex: 9,
				opacity: 1,
				index: this.imageList.length,
				id: this.guid(),
				disable: false,
				offset: 0,
				moveEnd: false
			});
			this.add.x = (this.imageList.length % this.colsValue) * this.viewWidth + 'px';
			this.add.y = Math.floor(this.imageList.length / this.colsValue) * this.viewWidth + 'px';
			this.sortList();
		},
		nothing() {},
		rpx2px(v) {
			return (this.width * v) / 750;
		},
		guid() {
			function S4() {
				return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
			}
			return S4() + S4() + '-' + S4() + '-' + S4() + '-' + S4() + '-' + S4() + S4() + S4();
		}
	}
};
</script>

<style lang="scss" scoped>
.con {
	// padding: 30rpx;
	.area {
		width: 100%;
		.view {
			display: flex;
			justify-content: center;
			align-items: center;
			.area-con {
				position: relative;
				.pre-image {
					width: 100%;
					height: 100%;
				}
				.del-con {
					position: absolute;
					top: -14rpx;
					right: -14rpx;
					.del-wrap {
						width: 32rpx;
						height: 32rpx;
						background-color: rgba(0, 0, 0, 0.5);
						border-radius: 50%;
						display: flex;
						justify-content: center;
						align-items: center;
						font-size: $font-size-tag;
						color: #fff;
						font-weight: bold;
					}
				}
			}
		}
		.add {
			position: absolute;
			display: flex;
			justify-content: center;
			align-items: center;
			.add-wrap {
				display: flex;
				justify-content: center;
				align-items: center;
				border: 1px dashed $color-disabled;
				width: 140rpx;
				height: 140rpx;
				line-height: 140rpx;
				text-align: center;
				color: $color-tip;
				font-weight: bold;
				font-size: 40rpx;
			}
		}
	}
}

.popup {
	width: 100vw;
	background: #fff;
	border-top-left-radius: 24rpx;
	border-top-right-radius: 24rpx;

	.popup-body {
		height: calc(100% - 250rpx);
		&.safe-area {
			height: calc(100% - 270rpx);
		}
	}
	&.choose-picture {
		background-color: $color-bg;
		.popup-header {
			border-bottom: none;
			background-color: #fff;
		}
		.popup-body {
			background-color: $color-bg;
			height: initial;
			.select-wrap {
				background-color: #fff;
				padding: 0 30rpx;
			}
			.item {
				text-align: center;
				padding: 20rpx;
				background-color: #fff;
				border-bottom: 1px solid $color-bg;
				&:last-child {
					border-bottom: none;
				}
				&.cancle {
					margin-top: 20rpx;
				}
			}
		}
	}
}
</style>
