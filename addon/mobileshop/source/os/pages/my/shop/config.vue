<template>
	<view class="iphone-safe-area">
		<view class="item-wrap">
			<view class="form-wrap">
				<text class="label">开店套餐</text>
				<text class="value">{{ shopInfo.group_name }}</text>
			</view>
			<view class="form-wrap">
				<text class="label">主营行业</text>
				<text class="value">{{ shopInfo.category_name }}</text>
			</view>
			<view class="form-wrap">
				<text class="label">店铺名称</text>
				<text class="value">{{ shopInfo.site_name }}</text>
			</view>
			<view class="form-wrap goods-img">
				<text class="label">店铺logo</text>
				<view class="img-list">
					<view class="add logo" @click="uplodImg('logo')">
						<text class="iconfont iconadd1" v-if="!shopInfo.logo"></text>
						<image v-else :src="$util.img(shopInfo.logo)" mode="aspectFit" @error="imgError('logo')" @click.stop="previewMedia('logo')"></image>
						<view class="del-wrap iconfont iconclose" @click.stop="delImg('logo')" v-if="shopInfo.logo"></view>
					</view>
					<view class="tips">建议图片尺寸：200*60</view>
				</view>
			</view>
			<view class="form-wrap goods-img">
				<text class="label">店铺头像</text>
				<view class="img-list">
					<view class="add avatar" @click="uplodImg('avatar')">
						<text class="iconfont iconadd1" v-if="!shopInfo.avatar"></text>
						<image v-else :src="$util.img(shopInfo.avatar)" mode="aspectFit" @error="imgError('avatar')" @click.stop="previewMedia('avatar')"></image>
						<view class="del-wrap iconfont iconclose" @click.stop="delImg('avatar')" v-if="shopInfo.avatar"></view>
					</view>
					<view class="tips">建议图片尺寸：800*800像素</view>
				</view>
			</view>
			<view class="form-wrap goods-img">
				<text class="label">店铺大图</text>
				<view class="img-list">
					<view class="add banner" @click="uplodImg('banner')">
						<text class="iconfont iconadd1" v-if="!shopInfo.banner"></text>
						<image v-else :src="$util.img(shopInfo.banner)" mode="aspectFit" @error="imgError('banner')" @click.stop="previewMedia('banner')"></image>
						<view class="del-wrap iconfont iconclose" @click.stop="delImg('banner')" v-if="shopInfo.banner"></view>
					</view>
					<view class="tips">建议图片高度：150像素</view>
				</view>
			</view>
			<view class="form-wrap more-wrap">
				<text class="label">店铺简介</text>
				<textarea class="uni-input font-size-base" placeholder="请输入店铺简介" v-model="shopInfo.seo_description" maxlength="300" auto-height="true"></textarea>
			</view>
			<view class="form-wrap more-wrap">
				<text class="label" style="align-self: flex-start;">店铺关键字</text>
				<view class="flex_1">
					<input class="uni-input" placeholder="请输入店铺关键字,用逗号隔开" v-model="shopInfo.seo_keywords" maxlength="100" />
					<view class="tips font-size-activity-tag color-tip padding-bottom">多个关键字之间请用英文","隔开</view>
				</view>
			</view>
		</view>
		<button type="primary" @click="save()">保存</button>
	</view>
</template>

<script>
export default {
	data() {
		return {
			shopInfo: {}
		};
	},
	onShow() {
		if (!this.$util.checkToken('/pages/my/shop/config')) return;
	},
	onLoad() {
		this.shopInfo = uni.getStorageSync('shop_info') ? JSON.parse(uni.getStorageSync('shop_info')) : {};
	},
	computed: {
		formData() {
			return {
				logo: this.shopInfo.logo,
				avatar: this.shopInfo.avatar,
				banner: this.shopInfo.banner,
				seo_description: this.shopInfo.seo_description,
				seo_keywords: this.shopInfo.seo_keywords
			};
		}
	},
	methods: {
		save() {
			this.$api.sendRequest({
				url: '/shopapi/shop/config',
				data: this.formData,
				success: res => {
					this.$util.showToast({
						title: res.message
					});
					if (res.code >= 0) {
						uni.setStorageSync('shop_info', JSON.stringify(this.shopInfo));
						setTimeout(() => {
							uni.navigateBack({
								delta: 1
							});
						}, 1000);
					}
				}
			});
		},
		uplodImg(type) {
			this.$util.upload(
				{
					number: 1,
					path: 'image'
				},
				res => {
					if (res) {
						this.$util.showToast({
							title: '上传成功'
						});
						if (type == 'logo') this.shopInfo.logo = res[0];
						else if (type == 'avatar') this.shopInfo.avatar = res[0];
						else if (type == 'banner') this.shopInfo.banner = res[0];
					}
				}
			);
		},
		delImg(type) {
			if (type == 'logo') this.shopInfo.logo = '';
			else if (type == 'avatar') this.shopInfo.avatar = '';
			else if (type == 'banner') this.shopInfo.banner = '';
		},
		previewMedia(type) {
			var paths = [this.$util.img(this.shopInfo[type])];
			uni.previewImage({
				current: 0,
				urls: paths
			});
		},
		imgError(type) {
			this.shopInfo[type] = this.$util.img(this.$util.getDefaultImage().default_headimg);
			this.$forceUpdate();
		}
	}
};
</script>

<style lang="scss">
@import '../css/edit.scss';

.value {
	vertical-align: middle;
	display: inline-block;
	flex: 1;
	text-align: right;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: pre;
}
button {
	margin-top: 40rpx;
}
</style>
