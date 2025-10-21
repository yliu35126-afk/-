<template>
	<view><jin-edit ref="jxImgTextEdit" @editOk="editOk"></jin-edit></view>
</template>

<script>
import jinEdit from '@/components/jin-edit/jin-edit.vue';
export default {
	data() {
		return {};
	},
	components: {
		jinEdit
	},
	onLoad(data) {},
	onShow() {},
	methods: {
		editOk(res) {
			console.log(res);
		}
	}
};
</script>

<style lang="scss"></style>
