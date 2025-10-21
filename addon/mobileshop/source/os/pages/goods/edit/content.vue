<template>
	<view><ns-editor ref="edit" @editOk="editOk" placeholder="请输入商品详情..." verify="请输入商品详情" :html="goodsContent"></ns-editor></view>
</template>

<script>
import nsEditor from '@/components/ns-editor/ns-editor.vue';
export default {
	data() {
		return {
			goodsContent: ''
		};
	},
	components: {
		nsEditor
	},
	onLoad(option) {
		this.goodsContent = uni.getStorageSync('editGoodsContent') || '';
	},
	onShow() {},
	methods: {
		editOk(res) {
			uni.setStorageSync('editGoodsContent', res.html);
			uni.navigateBack({
				delta: 1
			});
		}
	}
};
</script>
<style lang="scss"></style>
