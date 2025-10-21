<template>
	<view>
		<view class="single-spec" v-if="goodsSpecFormat.length == 0">单规格</view>
		<view class="more-spec" :class="{ 'safe-area': isIphonex }">
			<view class="spec-item" v-for="(item, index) in goodsSpecFormat" :key="index">
				<view class="spec-name">
					<text class="action iconfont iconjian" @click="deleteSpec(index)"></text>
					<text class="label">规格项</text>
					<input class="uni-input" v-model="item.spec_name" placeholder="请输入规格项" />
				</view>
				<view class="spec-value" v-for="(value_item, valueIndex) in item.value" :key="valueIndex">
					<text class="action iconfont iconjian" @click="deleteSpecValue(index, valueIndex)"></text>
					<text class="label">规格值</text>
					<input class="uni-input" v-model="value_item.spec_value_name" placeholder="请输入规格值" />
				</view>
				<view v-if="item.value.length < goodsSpecValueMax" class="color-base-text add-spec-value" @click="addSpecValue(index)">+添加规格值</view>
				<view v-else class="tip">最多添加{{ goodsSpecValueMax }}个规格值</view>
			</view>
			<view v-if="goodsSpecFormat.length < goodsSpecMax" @click="addSpec()" class="color-base-text add-spec">+添加规格</view>
			<view v-else class="tip">最多添加{{ goodsSpecMax }}个规格</view>
		</view>
		<view class="footer-wrap" :class="{ 'safe-area': isIphonex }"><button type="primary" @click="save()">保存</button></view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			isIphonex: false,
			// 规格项最大数量
			goodsSpecMax: 4,

			// 规格值最大数量
			goodsSpecValueMax: 10,

			goodsSpecFormat: []
		};
	},
	onLoad(option) {
		this.isIphonex = this.$util.uniappIsIPhoneX();
		this.goodsSpecFormat = uni.getStorageSync('editGoodsSpecFormat') ? JSON.parse(uni.getStorageSync('editGoodsSpecFormat')) : [];
	},
	onShow() {},
	methods: {
		// 添加规格项
		addSpec() {
			var spec_id = -(this.goodsSpecFormat.length - 1 + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds()));
			var spec = {
				spec_id: spec_id,
				spec_name: '',
				value: []
			};
			this.goodsSpecFormat.push(spec);
			var lastIndex = this.goodsSpecFormat.length - 1 > -1 ? this.goodsSpecFormat.length - 1 : 0;
			this.addSpecValue(lastIndex);
		},
		// 添加规格值
		addSpecValue(index) {
			var count = this.goodsSpecFormat[index].value.length;
			var spec_value_id = -(Math.abs(this.goodsSpecFormat[index].spec_id) + Math.floor(new Date().getSeconds()) + Math.floor(new Date().getMilliseconds())) + count;
			this.goodsSpecFormat[index].value.push({
				spec_id: this.goodsSpecFormat[index].spec_id,
				spec_name: this.goodsSpecFormat[index].spec_name,
				spec_value_id: spec_value_id,
				spec_value_name: ''
			});
		},
		deleteSpec(index) {
			uni.showModal({
				title: '操作提示',
				content: '确定要删除此规格项吗？',
				success: res => {
					if (res.confirm) this.goodsSpecFormat.splice(index, 1);
				}
			});
		},
		deleteSpecValue(index, valueIndex) {
			uni.showModal({
				title: '操作提示',
				content: '确定要删除此规格值吗？',
				success: res => {
					if (res.confirm) this.goodsSpecFormat[index].value.splice(valueIndex, 1);
				}
			});
		},
		verify() {
			var flag = true;
			for (var i = 0; i < this.goodsSpecFormat.length; i++) {
				var item = this.goodsSpecFormat[i];
				if (item.spec_name.trim().length == 0) {
					this.$util.showToast({ title: `请输入规格项` });
					flag = false;
					break;
				}
				for (var j = 0; j < item.value.length; j++) {
					var child_item = item.value[j];
					if (child_item.spec_value_name.trim().length == 0) {
						this.$util.showToast({ title: `请输入规格值` });
						flag = false;
						break;
					}
				}
			}
			return flag;
		},
		save() {
			if (!this.verify()) return;
			this.goodsSpecFormat.forEach(item => {
				item.value.forEach(citem => {
					// if (citem.spec_name == '') {
					// 	citem.spec_name = item.spec_name;
					// }
					citem.spec_name = item.spec_name ? item.spec_name : '';
				});
			});
			uni.setStorageSync('editGoodsSpecFormat', JSON.stringify(this.goodsSpecFormat));
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style lang="scss">
.single-spec {
	font-size: $font-size-toolbar;
	text-align: center;
	margin-top: $margin-updown;
	padding: 40rpx;
}
.footer-wrap {
	padding-bottom: 40rpx;
	padding-bottom: 40rpx;
	position: fixed;
	width: 100%;
	bottom: 0;
	padding: 20px 0;
	z-index: 10;
	background-color: #fff;
}
.safe-area {
	/* #ifndef MP */
	padding-bottom: calc(constant(safe-area-inset-bottom) + 100rpx);
	padding-bottom: calc(env(safe-area-inset-bottom) + 100rpx);
	/* #endif */
}
.more-spec {
	margin: $margin-updown $margin-both 180rpx $margin-both;
	&.safe-area {
		margin-bottom: 200rpx;
	}
	.spec-item {
		background-color: #fff;
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
		}
		.label {
			vertical-align: middle;
			margin-right: $margin-both;
		}
		input {
			vertical-align: middle;
			display: inline-block;
			flex: 1;
			text-align: right;
		}
		.spec-name,
		.spec-value {
			display: flex;
			align-items: center;
			height: 100rpx;
			line-height: 100rpx;
			padding: 0 30rpx;
			border-bottom: 1px solid $color-line;
		}
		.spec-value {
			margin-left: 60rpx;
			padding-left: 0;
		}
		.add-spec-value {
			height: 100rpx;
			line-height: 100rpx;
			margin-left: 60rpx;
		}
	}
	.add-spec {
		text-align: center;
		background-color: #fff;
		height: 100rpx;
		line-height: 100rpx;
		border-radius: $border-radius;
	}
	.tip {
		text-align: center;
		color: $color-tip;
		font-size: $font-size-tag;
		padding: $padding;
	}
}
</style>
