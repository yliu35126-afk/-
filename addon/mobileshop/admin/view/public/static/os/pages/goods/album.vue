<template>
	<view>
		<view class="album-wrap">
			<scroll-view scroll-y="true" class="group-wrap">
				<view class="item" :class="{ selected: item.album_id == albumId }" v-for="(item, index) in albumList" :key="index" @click="changeAlbum(item.album_id)">
					<text :class="{ 'color-base-border': item.album_id == albumId }">{{ item.album_name }}</text>
				</view>
			</scroll-view>
			<view class="pic-wrap">
				<mescroll-uni @getData="getData" ref="mescroll" size="30">
					<block slot="list">
						<view class="list-wrap">
							<view class="item-wrap upload" @click="photograph()">
								<text class="iconfont iconxiangji"></text>
								<text class="txt">拍摄照片</text>
							</view>
							<view class="item-wrap" v-for="(item, index) in picList" :key="index" @click="previewImg(item.pic_path)">
								<image :src="$util.img(item.pic_path, { size: 'mid' })" mode="scaleToFill" @error="imgError(index)"></image>
								<view @click.stop="checkImg(item.pic_path,index)" class="circle" :class="{ 'selected color-base-bg': isSelected(item.pic_path) }">
									{{ getImgIndex(item.pic_path) }}
								</view>
								<view v-show="isSelected(item.pic_path)" class="mask-layer"></view>
							</view>
						</view>
					</block>
				</mescroll-uni>
			</view>
		</view>
		<view class="footer-wrap">
			<button type="primary" :disabled="selectedImg.length == number" @click="save()" size="mini">
				确定{{ selectedImg.length ? '（' + selectedImg.length + '）' : '' }}
			</button>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			albumList: [],
			albumId: 0,
			picList: [],
			mescroll: null,
			number: 9,
			selectedImg: [],
			index: 0
		};
	},
	onLoad(option) {
		this.number = option.number || 9;
		if (!this.$util.checkToken('/pages/goods/album?number=' + this.number)) return;
		var selectedAlbumImgTemp = uni.getStorageSync('selectedAlbumImgTemp') ? JSON.parse(uni.getStorageSync('selectedAlbumImgTemp')) : null;
		if (selectedAlbumImgTemp) {
			if (selectedAlbumImgTemp.list) {
				this.selectedImg = selectedAlbumImgTemp.list.split(',');
			}
			this.index = selectedAlbumImgTemp.index;
		}
	},
	async onShow() {
		await this.getAlbumList();
	},
	methods: {
		async getAlbumList() {
			var res = await this.$api.sendRequest({
				url: '/shopapi/album/lists',
				async: false,
				success: res => {}
			});
			if (res.data) {
				this.albumList = res.data;
				if (this.albumList.length > 0) this.albumId = this.albumList[0].album_id;
				if (this.mescroll) this.mescroll.resetUpScroll();
			}
		},
		getData(mescroll) {
			this.mescroll = mescroll;
			if (this.albumId == 0) return;
			this.$api.sendRequest({
				url: '/shopapi/album/picList',
				data: {
					page_size: mescroll.size,
					page: mescroll.num,
					album_id: this.albumId
				},
				success: res => {
					let newArr = [];
					let msg = res.message;
					if (res.code == 0 && res.data) {
						newArr = res.data.list;
					} else {
						this.$util.showToast({
							title: msg
						});
					}
					mescroll.endSuccess(newArr.length);
					//设置列表数据
					if (mescroll.num == 1) this.picList = []; //如果是第一页需手动制空列表
					this.picList = this.picList.concat(newArr); //追加新数据
					// newArr.forEach(v => {
					// 	v.isExist = false; // 图片是否存在
					// });
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		changeAlbum(album_id) {
			this.albumId = album_id;
			if (this.mescroll) this.mescroll.resetUpScroll();
		},
		// 手机拍照
		photograph() {
			this.$util.upload(
				{
					number: this.number,
					path: 'album',
					sourceType: ['camera'],
					album_id: this.albumId
				},
				res => {
					if (this.mescroll) this.mescroll.resetUpScroll();
				}
			);
		},
		checkImg(pic_path,key) {
			var index = this.selectedImg.indexOf(pic_path);
			// if(this.picList[key].isExist) {
			// 	this.$util.showToast({
			// 		title: '该图片未找到',
			// 	});
			// 	return;
			// }
			if (index == -1) {
				if (this.selectedImg.length + 1 > this.number) return;
				this.selectedImg.push(pic_path);
			} else {
				this.selectedImg.splice(index, 1);
			}
		},
		isSelected(pic_path) {
			if (this.selectedImg.indexOf(pic_path) > -1) return true;
			else return false;
		},
		getImgIndex(pic_path) {
			var index = this.selectedImg.indexOf(pic_path);
			if (index > -1) return index + 1;
			else return '';
		},
		previewImg(pic_path) {
			var index = this.selectedImg.indexOf(pic_path);
			var paths = [];
			if (index > -1) {
				this.selectedImg.forEach(item => {
					paths.push(this.$util.img(item));
				});
			} else {
				paths = [this.$util.img(pic_path)];
			}
			uni.previewImage({
				current: 0,
				urls: paths
			});
		},
		imgError(index) {
			// this.picList[index].isExist = true;
			// this.picList[index].pic_path = this.$util.getDefaultImage().default_goods_img;
			// this.$forceUpdate();
		},
		save() {
			var temp = {
				list: this.selectedImg.toString(),
				index: this.index
			};
			uni.setStorageSync('selectedAlbumImg', JSON.stringify(temp));
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style lang="scss">
page {
	overflow: hidden;
}
.album-wrap {
	display: flex;
	.group-wrap {
		width: 25%;
		height: 93vh;
		padding-bottom: constant(safe-area-inset-bottom);
		padding-bottom: env(safe-area-inset-bottom);
		.item {
			padding: 20rpx 20rpx 20rpx 0;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			text {
				padding: 0 20rpx;
				border-left: 4rpx solid transparent;
			}
			&.selected {
				background-color: #fff;
			}
		}
	}
	.pic-wrap {
		flex: 1;
		.list-wrap {
			display: flex;
			flex-direction: row;
			flex-wrap: wrap;
			align-items: center;
			padding-bottom: constant(safe-area-inset-bottom);
			padding-bottom: env(safe-area-inset-bottom);
			.item-wrap {
				position: relative;
				width: 32.5%;
				height: 170rpx;
				margin-right: 4rpx;
				margin-bottom: 4rpx;
				line-height: initial;
				text-align: center;
				image {
					width: 170rpx;
					height: 170rpx;
				}
				.mask-layer {
					background-color: rgba(0, 0, 0, 0.4);
					position: absolute;
					z-index: 10;
					top: 0;
					left: 0;
					right: 0;
					bottom: 0;
				}
				.circle {
					border: 2rpx solid #fff;
					position: absolute;
					z-index: 20;
					border-radius: 50%;
					width: 40rpx;
					height: 40rpx;
					background-color: rgba(0, 0, 0, 0.2);
					top: 10rpx;
					right: 10rpx;
					text-align: center;
					line-height: 40rpx;
					color: #fff;
					&.selected {
						border-color: transparent;
					}
				}
				&.upload {
					background-color: #f2f2f2;
					color: $color-tip;
					line-height: inherit;
					.iconfont {
						font-size: 60rpx;
						display: block;
						text-align: center;
					}
					.txt {
						display: block;
						text-align: center;
						font-size: $font-size-tag;
					}
				}
			}
		}
	}
}
.footer-wrap {
	position: fixed;
	bottom: 0;
	right: 0;
	padding: 20rpx 30rpx 0;
	z-index: 10;
	background-color: #fff;
	width: 100%;
	text-align: right;
	padding-bottom: constant(safe-area-inset-bottom);
	padding-bottom: env(safe-area-inset-bottom);
}
</style>
<style scoped>
.pic-wrap >>> .mescroll-uni-fixed {
	top: initial;
	bottom: initial;
	left: initial;
	right: initial;
	bottom: 100rpx; /* !important*/
	width: 75%;
}
</style>
