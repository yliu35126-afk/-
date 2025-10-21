<template>
	<view>
		<view class="item-wrap">
			<view class="form-wrap more-wrap">
				<text class="label">是否免邮</text>
				<ns-switch class="switch" :checked="isFreeShipping == 1" @change="isFree()"></ns-switch>
			</view>
			<view class="form-wrap more-wrap" v-if="isFreeShipping == 0">
				<text class="label">运费模板</text>
				<picker class="selected" @change="bindPickerChange" :value="index" :range="pickedArr">
					<view class="uni-input">{{ text }}</view>
				</picker>
				<text class="iconfont iconright"></text>
			</view>
		</view>
		<view class="footer-wrap"><button type="primary" @click="save()">保存</button></view>
		<loading-cover ref="loadingCover"></loading-cover>
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
			expressTemplateList: [],
			index: 0,
			pickedArr: [],
			text: '请选择',
			isFreeShipping: 1,
			templateId: 0
		};
	},
	onLoad(option) {
		this.templateId = option.template_id || 0;
		if (this.templateId > 0) this.isFreeShipping = 0;
		this.getExpressTemplateList();
	},
	onShow() {},
	methods: {
		isFree() {
			this.isFreeShipping = this.isFreeShipping == 1 ? 0 : 1;
		},
		bindPickerChange(e) {
			if (this.expressTemplateList.length == 0) return;
			this.index = e.target.value;
			this.templateId = this.expressTemplateList[this.index].template_id;
			this.text = this.expressTemplateList[this.index].template_name;
		},
		// 获取运费模板列表
		getExpressTemplateList() {
			this.$api.sendRequest({
				url: '/shopapi/express/getExpressTemplateList',
				success: res => {
					this.expressTemplateList = res.data;
					this.expressTemplateList.forEach((item, key) => {
						this.pickedArr.push(item.template_name);
						if (this.templateId > 0 && this.templateId == item.template_id) {
							this.index = key;
						}
					});
					if (this.templateId > 0) this.text = this.expressTemplateList[this.index].template_name;
					if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
				}
			});
		},
		save() {
			if (this.isFreeShipping == 0 && this.templateId == 0) {
				this.$util.showToast({
					title: '请选择运费模板'
				});
				return;
			}

			if (this.isFreeShipping == 1) this.templateId = 0;
			uni.setStorageSync('editGoodsShippingTemplateId', this.templateId);
			uni.setStorageSync('editGoodsShippingTemplateName', this.isFreeShipping == 0 ? this.text : '');
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>

<style lang="scss">
@import '../css/edit.scss';
// .item-wrap {
// 	&:first-child {
// 		margin-top: 0;
// 	}
// }
.switch {
	position: absolute;
	right: 0;
	margin-right: $margin-both;
	padding-right: $padding;
}
.footer-wrap {
	position: initial;
	background-color: transparent;
}
</style>
