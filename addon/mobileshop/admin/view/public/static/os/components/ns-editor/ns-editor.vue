<template>
	<view class="container" :style="{ paddingTop: showMoreTool ? '200rpx' : '100rpx' }">
		<!-- 操作工具 -->
		<view class="tool-view">
			<view class="tool">
				<view class="iconfont iconshangchuantupian" title="插入图片" @click="insertImage"></view>
				<view class="iconfont iconT" title="修改文字样式" @click="showMore" :class="{ 'color-base-text': showMoreTool }"></view>
				<view class="iconfont iconfengexian" title="分割线" @click="insertDivider"></view>
				<view class="iconfont iconchexiao" title="撤销" @click="undo"></view>
				<view class="iconfont iconzhongzuo" title="重做" @click="redo"></view>
				<view class="iconfont iconqueding_queren" title="草稿箱" @click="draftBox" :class="{ disabled: disabledDraftBox }"></view>
			</view>
			<!-- 文字相关操作 -->
			<view class="font-more" :style="{ height: showMoreTool ? '100rpx' : 0 }">
				<view class="iconfont iconjiacu1" title="加粗" @click="setBold" :class="{ 'color-base-text': showBold }"></view>
				<view class="iconfont iconzitixieti" title="斜体" @click="setItalic" :class="{ 'color-base-text': showItalic }"></view>
				<view class="iconfont iconxiahuaxian1" title="下划线" @click="setIns" :class="{ 'color-base-text': showIns }"></view>
				<view class="iconfont iconbiaotizhengwenqiehuan" title="标题" @click="setHeader" :class="{ 'color-base-text': showHeader }"></view>
				<view class="iconfont iconjuzhongduiqi" title="居中" @click="setCenter" :class="{ 'color-base-text': showCenter }"></view>
				<view class="iconfont iconjuyouduiqi" title="居右" @click="setRight" :class="{ 'color-base-text': showRight }"></view>
			</view>
		</view>
		<editor
			id="editor"
			class="ql-container"
			:class="{ 'safe-area': isIphoneX }"
			:placeholder="placeholder"
			:show-img-size="true"
			:show-img-toolbar="true"
			:show-img-resize="true"
			@ready="onEditorReady"
			@statuschange="statuschange"
			@input="editInput"
			@focus="editFocus"
			@blur="editBlur"
			ref="editor"
		></editor>
		<view class="footer-wrap" :class="{ 'safe-area': isIphoneX }"><button type="primary" @click="save()">保存</button></view>
	</view>
</template>

<script>
export default {
	props: {
		// 点击图片时显示图片大小控件
		showImgSize: {
			type: Boolean,
			default: false
		},
		// 点击图片时显示工具栏控件
		showImgToolbar: {
			type: Boolean,
			default: false
		},
		// 点击图片时显示修改尺寸控件
		showImgResize: {
			type: Boolean,
			default: false
		},
		// 占位符
		placeholder: {
			type: String,
			default: '请输入内容...'
		},
		verify: {
			type: String,
			default: '请输入内容'
		},
		// 初始化html
		html: {
			type: String,
			default: ''
		}
	},
	computed: {},
	data() {
		return {
			isIphoneX: false,
			imgCount: 9999,
			showMoreTool: false,
			showBold: false,
			showItalic: false,
			showIns: false,
			showHeader: false,
			showCenter: false,
			showRight: false,

			// 是否禁用草稿箱，true 是，false 否
			disabledDraftBox: true,

			// 草稿箱内容
			draftboxHtml: ''
		};
	},
	created() {
		this.isIphoneX = this.$util.uniappIsIPhoneX();
		// this.draftboxHtml = uni.getStorageSync('goodsContentByDraftBox') || '';
	},
	methods: {
		onEditorReady(e) {
			uni.createSelectorQuery()
				.in(this)
				.select('.ql-container')
				.fields(
					{
						size: true,
						context: true
					},
					res => {
						this.editorCtx = res.context;
						if (this.html) {
							this.disabledDraftBox = false;
							this.editorCtx.setContents({
								html: this.html
							});
						} else if (this.draftboxHtml) {
							this.disabledDraftBox = false;
							this.editorCtx.setContents({
								html: this.draftboxHtml
							});
						}
					}
				)
				.exec();
		},
		undo() {
			this.editorCtx.undo();
		},
		// 插入图片
		insertImage() {
			this.$util.upload(
				{
					number: this.imgCount,
					path: 'image'
				},
				res => {
					for (var i = 0; i < res.length; i++) {
						this.editorCtx.insertImage({
							src: this.$util.img(res[i]),
							alt: '图片',
							success: e => {}
						});
					}
				}
			);
		},
		insertDivider() {
			this.editorCtx.insertDivider();
		},
		redo() {
			this.editorCtx.redo();
		},
		// 草稿箱
		draftBox() {
			// this.editorCtx.getContents({
			// 	success: res => {
			// 		if (this.disabledDraftBox || res.text.length == 1) return;
			// 		uni.setStorageSync('goodsContentByDraftBox', res.html);
			// 		this.$util.showToast({
			// 			title: '保存草稿箱成功'
			// 		});
			// 	}
			// });
		},
		showMore() {
			this.showMoreTool = !this.showMoreTool;
			this.editorCtx.setContents();
		},
		setBold() {
			this.showBold = !this.showBold;
			this.editorCtx.format('bold');
		},
		setItalic() {
			this.showItalic = !this.showItalic;
			this.editorCtx.format('italic');
		},
		checkStatus(name, detail, obj) {
			if (detail.hasOwnProperty(name)) {
				this[obj] = true;
			} else {
				this[obj] = false;
			}
		},
		statuschange(e) {
			var detail = e.detail;
			this.checkStatus('bold', detail, 'showBold');
			this.checkStatus('italic', detail, 'showItalic');
			this.checkStatus('ins', detail, 'showIns');
			this.checkStatus('header', detail, 'showHeader');
			if (detail.hasOwnProperty('align')) {
				if (detail.align == 'center') {
					this.showCenter = true;
					this.showRight = false;
				} else if (detail.align == 'right') {
					this.showCenter = false;
					this.showRight = true;
				} else {
					this.showCenter = false;
					this.showRight = false;
				}
			} else {
				this.showCenter = false;
				this.showRight = false;
			}
		},
		setIns() {
			this.showIns = !this.showIns;
			this.editorCtx.format('ins');
		},
		setHeader() {
			this.showHeader = !this.showHeader;
			this.editorCtx.format('header', this.showHeader ? 'H2' : false);
		},
		setCenter() {
			this.showCenter = !this.showCenter;
			this.editorCtx.format('align', this.showCenter ? 'center' : false);
		},
		setRight() {
			this.showRight = !this.showRight;
			this.editorCtx.format('align', this.showRight ? 'right' : false);
		},
		async editInput(e) {
			// if (e.detail.text) this.disabledDraftBox = e.detail.text.length == 1;
		},
		async editFocus() {},
		editBlur() {},
		save() {
			this.editorCtx.getContents({
				success: res => {
					var len = res.text.length;
					if (len == 1 && res.html == '<p><br></p>') {
						this.$util.showToast({ title: this.verify });
						return;
					}
					if (len < 1 || len > 5000) {
						this.$util.showToast({ title: '内容描述字符数应在1～5000之间' });
						return;
					}
					this.$emit('editOk', res);
				}
			});
		}
	}
};
</script>

<style lang="scss" scoped>
.container {
	padding-right: 0;
	padding-left: 0;
	box-sizing: border-box;
	padding-top: 100rpx;
	padding-bottom: 40rpx;
}
.ql-container {
	line-height: 160%;
	font-size: $font-size-base;
	height: auto;
	background-color: #fff;
	padding: 30rpx;
	margin-top: 20rpx;
	margin-bottom: 160rpx;
	&.safe-area {
		margin-bottom: 200rpx;
	}
}
.tool-view {
	width: 100vw;
	position: fixed;
	top: 0; //20rpx;
	left: 0;
	z-index: 1;
}
.tool {
	height: 100rpx;
	display: flex;
	align-items: center;
	justify-content: space-around;
	width: 100%;
	background: #fff;
}

.iconfont {
	font-size: 44rpx;
	color: $color-sub;
}
.disabled {
	color: $color-disabled;
}

/deep/ .ql-editor.ql-blank:before {
	font-style: initial;
}
.font-more {
	position: absolute;
	left: 0;
	top: 100rpx;
	display: flex;
	align-items: center;
	justify-content: space-around;
	width: 100%;
	background: #fff;
	overflow: hidden;
	transition: all 0.15s;
}
.footer-wrap {
	position: fixed;
	background-color: #fff;
	width: 100%;
	bottom: 0;
	padding: 40rpx 0;
	z-index: 1;
	/* #ifdef MP */
	padding-bottom: 40rpx;
	/* #endif */
	&.safe-area {
		/* #ifndef MP */
		padding-bottom: calc(constant(safe-area-inset-bottom) + 100rpx);
		padding-bottom: calc(env(safe-area-inset-bottom) + 100rpx);
		/* #endif */
	}
}
</style>
