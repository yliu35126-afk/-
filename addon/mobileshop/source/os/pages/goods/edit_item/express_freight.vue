<template>
	<view>
		<view class="form-main">
			<view class="form-title">快递运费</view>
			<view class="item-wrap">
				<view class="form-wrap more-wrap">
					<text class="label">是否免邮</text>
					<ns-switch class="is-free" :checked="isFreeShipping == 1" @change="isFree()"></ns-switch>
				</view>
				<view class="form-wrap more-wrap" v-if="isFreeShipping == 1">
					<text class="label">运费模板</text>
					<picker class="selected" @change="bindPickerChange" :value="index" :range="pickedArr">
						<view class="uni-input">{{ text }}</view>
					</picker>
					<text class="iconfont iconright"></text>
				</view>
			</view>
		</view>
		<view class="footer-wrap"><button type="primary" @click="save()">保存</button></view>
	</view>
</template>

<script>
import nsSwitch from '@/components/ns-switch/ns-switch.vue';
export default {
	components: {
		nsSwitch
	},
	data() {
		return {
			isFreeShipping: 1,
			array: ['中国', '美国', '巴西', '日本'],
			templateId: 0,
			index: 0,
			expressTemplateList: [],
			pickedArr: [],
			text: '请选择'
		};
	},
	onLoad(option) {
		this.templateId = option.template_id || 0;
		this.getExpressTemplateList();
	},
	onShow() {},
	methods: {
		isFree() {
			this.isFreeShipping = this.isFreeShipping == 1 ? 0 : 1;
		},
		bindPickerChange(e) {
			this.index = e.target.value;
			this.templateId = this.expressTemplateList[this.index].template_id;
		},
		getExpressTemplateList() {
			this.$api.sendRequest({
				url: '/shopapi/goods/getExpressTemplateList',
				success: res => {
					this.expressTemplateList = res.data;
					this.expressTemplateList.forEach((item, key) => {
						this.pickedArr.push(item.template_name);
						if (this.templateId && this.templateId == item.template_id) {
							this.index = key;
						}
					});
					if (this.templateId) this.text = this.expressTemplateList[this.index].template_name;
				}
			});
		},
		save() {
			if (this.isFreeShipping == 1 && this.templateId == 0) {
				this.$util.showToast({
					title: '请选择运费模板'
				});
				return;
			}
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style lang="scss">
.form-title {
	margin: $margin-updown $margin-both;
	padding: 0 $padding;
	color: $color-tip;
}

.item-wrap {
	background: #fff;
	padding: $padding;
	margin-top: $margin-updown;
	.form-wrap {
		display: flex;
		align-items: center;
		margin: 0 $margin-both;
		border-bottom: 1px solid $color-line;
		height: 100rpx;
		line-height: 100rpx;
		&:last-child {
			border-bottom: none;
		}
		.label {
			vertical-align: middle;
		}
		&.more-wrap {
			.selected {
				vertical-align: middle;
				display: inline-block;
				flex: 1;
				text-align: right;
				color: $color-tip;
			}
			.iconfont {
				color: $color-tip;
				margin-left: 20rpx;
			}
		}
		.is-free {
			position: absolute;
			right: 0;
			margin-right: $margin-both;
			padding-right: $padding;
		}
	}
}
.footer-wrap {
	padding: 40rpx 0;
}
</style>
