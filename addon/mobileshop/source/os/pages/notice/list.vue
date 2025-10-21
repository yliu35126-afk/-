<template>
	<view>
		<mescroll-uni @getData="getData" ref="mescroll">
			<block slot="list">
				<view class="notice-list" v-if="dataList.length">
					<view class="notice-item" @click="jumpDetail(item.id)" v-for="(item, index) in dataList" :key="index">
						<view class="title-info">
							<view class="title">
								<text>{{ item.title }}</text>
							</view>
							<text class="top color-base-bg" v-if="item.is_top == 1">置顶</text>
							<text class="release-time">{{ $util.timeStampTurnTime(item.create_time, 1) }}</text>
						</view>
						<view class="more">
							<text class="detail">{{ item.info }}</text>
							<view class="iconfont iconright"></view>
						</view>
					</view>
				</view>
				<ns-empty v-else text="暂无公告"></ns-empty>
			</block>
		</mescroll-uni>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>
<script>
export default {
	data() {
		return {
			dataList: []
		};
	},
	methods: {
		getData(mescroll) {
			this.$api.sendRequest({
				url: '/shopapi/notice/lists',
				data: {
					page_size: mescroll.size,
					page: mescroll.num
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
					if (mescroll.num == 1) this.dataList = []; //如果是第一页需手动制空列表
					this.dataList = this.dataList.concat(newArr); //追加新数据
					this.dataList.forEach(item => {
						item.info = item.content.replace(/<[^>]+>/g, '');
					});
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		jumpDetail(notice_id) {
			this.$util.redirectTo('/pages/notice/detail', { notice_id });
		}
	},
	onShareAppMessage(res) {
		var title = '公告';
		var path = '/pages/notice/list';
		return {
			title: title,
			path: path,
			success: res => {},
			fail: res => {}
		};
	}
};
</script>

<style lang="scss" scoped>
.notice-list {
	.notice-item {
		margin: $margin-updown $margin-both;
		background: #fff;
		border-radius: 10rpx;
		padding: 32rpx 34rpx 23rpx 34rpx;
		box-sizing: border-box;
		display: flex;
		flex-direction: column;
		align-items: center;
		line-height: 1;

		.title-info {
			width: 100%;
			display: flex;
			justify-content: space-between;
			align-items: center;
			border-bottom: 1px solid #f3f3f3;
			padding-bottom: 20rpx;
			overflow: hidden;

			.title {
				flex: 5;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
				display: flex;
				align-items: center;

				> text:first-child {
					font-size: $font-size-base;
					line-height: 28rpx;
					overflow: hidden;
					text-overflow: ellipsis;
					white-space: nowrap;
				}
			}
			.top {
				font-size: 22rpx;
				color: #ffffff;
				line-height: 28rpx;
				border-radius: 6rpx;
				padding: 0 6rpx;
				margin-left: 14rpx;
			}
			.release-time {
				flex: 2;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
				margin-left: 20rpx;
				color: $color-tip;
				text-align: right;
				font-size: $font-size-tag;
			}
		}

		.content {
			margin-top: 10rpx;
			display: inline-block;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			width: 100%;
			color: $color-tip;
			font-size: $font-size-goods-tag;
			padding-bottom: 30rpx;
		}

		.more {
			width: 100%;
			display: flex;
			justify-content: space-between;
			padding-top: 20rpx;
			font-size: $font-size-tag;
			align-items: center;

			.detail {
				width: 505rpx;
				color: $color-sub;
				font-size: $font-size-tag;
				display: -webkit-box;
				-webkit-box-orient: vertical;
				-webkit-line-clamp: 1;
				overflow: hidden;
			}

			.iconright {
				color: $color-tip;
				font-size: $font-size-base;
			}
		}
	}
}
</style>
