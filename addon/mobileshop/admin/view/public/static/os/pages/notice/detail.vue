<template>
	<view class="page">
		<view class="notice-title">{{ detail.title }}</view>
		<view class="notice-content"><rich-text :nodes="content"></rich-text></view>
		<view class="notice-meta">
			<text class="notice-time">发表时间: {{ $util.timeStampTurnTime(detail.create_time) }}</text>
		</view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import htmlParser from '@/common/js/html-parser';
export default {
	data() {
		return {
			notice_id: 0,
			content: '',
			detail: {}
		};
	},
	onLoad(option) {
		option.notice_id ? (this.notice_id = option.notice_id) : this.$util.redirectTo('/pages/notice/list', {}, 'redirectTo');
		this.$api.sendRequest({
			url: '/shopapi/notice/detail',
			data: {
				id: this.notice_id
			},
			success: res => {
				if (res.code == 0 && res.data) {
					this.detail = res.data;
					this.content = htmlParser(res.data.content);
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				} else {
					this.$util.showToast({
						title: res.message
					});
					setTimeout(() => {
						this.$util.redirectTo('/pages/notice/list', {}, 'redirectTo');
					}, 1000);
				}
			}
		});
	},
	onShareAppMessage(res) {
		var title = '[公告]' + this.detail.title;
		var path = '/pages/notice/detail?notice_id=' + this.notice_id;
		return {
			title: title,
			path: path,
			success: res => {},
			fail: res => {}
		};
	}
};
</script>

<style lang="scss">
.page {
	width: 100%;
	height: 100%;
	padding: 30rpx;
	box-sizing: border-box;
	background-color: #fff;
}

.notice-title {
	font-size: $font-size-toolbar;
	text-align: center;
}

.notice-content {
	margin-top: $margin-updown;
	word-break: break-all;
	font-size: $font-size-base;
}

.notice-meta {
	text-align: right;
	margin-top: $margin-updown;
	color: $color-tip;

	.notice-time {
		font-size: $font-size-tag;
	}
}
</style>
