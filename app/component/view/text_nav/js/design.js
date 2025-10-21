/**
 * 文本导航中的属性插件
 */
var textNavPreviewHtml = '<div v-bind:id="id" class="text-navigation">';

		textNavPreviewHtml += '<div v-show="data.arrangement==\'horizontal\'" v-bind:class="[data.arrangement]">';
			textNavPreviewHtml += '<div class="item" v-for="item in list">';
				textNavPreviewHtml += '<a href="javascript:;" v-bind:style="{color: data.textColor}">{{item.text}}</a>';
			textNavPreviewHtml += '</div>';
		textNavPreviewHtml += '</div>';

		textNavPreviewHtml += '<div v-show="data.arrangement==\'vertical\'" v-bind:style="{ textAlign : data.textAlign }">';
			textNavPreviewHtml += '<a href="javascript:;" v-bind:style="{color: data.textColor}">{{list[0].text}}</a>';
		textNavPreviewHtml += '</div>';

textNavPreviewHtml += '</div>';

Vue.component("text-nav", {
	data: function () {
		return {
			id: "text_nav_" + ns.gen_non_duplicate(10),
			data: this.$parent.data,
			list: this.$parent.data.list
		}
	},
	template: textNavPreviewHtml
});

var arrangementHtml = '<div class="layui-form-item">';
		arrangementHtml += '<label class="layui-form-label sm">排列方式</label>';
		arrangementHtml += '<div class="layui-input-block">';
		
			arrangementHtml += '<div v-bind:class="{ \'layui-unselect layui-form-select\' : true, \'layui-form-selected\' : isShowArrangement }" v-on:click="isShowArrangement=!isShowArrangement;">';
				arrangementHtml += '<div class="layui-select-title">';
					arrangementHtml += '<input type="text" placeholder="请选择" v-bind:value="($parent.data.arrangement==\'vertical\') ? \'竖排\' : \'横排\'" readonly="readonly" class="layui-input layui-unselect">';
					arrangementHtml += '<i class="layui-edge"></i>';
				arrangementHtml += '</div>';
				
				arrangementHtml += '<dl class="layui-anim layui-anim-upbit">';
					arrangementHtml += '<dd v-bind:class="{ \'layui-this\' : ($parent.data.arrangement==\'vertical\') }" v-on:click.stop="change(\'vertical\')">竖排</dd>';
					arrangementHtml += '<dd v-bind:class="{ \'layui-this\' : ($parent.data.arrangement==\'horizontal\') }" v-on:click.stop="change(\'horizontal\')">橫排</dd>';
				arrangementHtml += '</dl>';
			arrangementHtml += '</div>';
			
		arrangementHtml += '</div>';
arrangementHtml += '</div>';

Vue.component("arrangement", {
	data: function () {
		return {
			isShowArrangement: false
		}
	},
	created:function() {
		this.$parent.data.verify = this.verify;//加载验证方法
	},
	methods: {
		change: function (v) {
			this.$parent.data.arrangement = v;
			if (v == "vertical") {
				for (var i = 1; i < this.$parent.data.list.length;) {
					this.$parent.data.list.splice(i, 1);
					i = 1;
				}
			}
			this.isShowArrangement = false;
		},
		
		verify : function () {
			var res = {code: true, message: ""};
			for (var i = 0; i < this.$parent.data.list.length; i++) {
				if (this.$parent.data.list[i].text == "") {
					res.message = "请输入导航名称";
					res.code = false;
					break;
				}
			}
			return res;
		}
	},
	template: arrangementHtml
});

var navHtml = '<div class="layui-form-item">';
	navHtml +=	 '<label class="layui-form-label sm">{{data.label}}</label>';
	navHtml +=	 '<div class="layui-input-block">';
	navHtml +=		 '<template v-for="(item,index) in list" v-bind:k="index">';
	navHtml +=			 '<div v-on:click="parent[data.field]=item.value" v-bind:class="{ \'layui-unselect layui-form-radio\' : true,\'layui-form-radioed\' : (parent[data.field]==item.value) }"><i class="layui-anim layui-icon">&#xe643;</i><div>{{item.label}}</div></div>';
	navHtml +=		 '</template>';
	navHtml +=	 '</div>';
	navHtml += '</div>';

Vue.component("nav-position", {
	props: {
		data: {
			type: Object,
			default: function () {
				return {
					field: "textAlign",
					label: "文本位置"
				};
			}
		}
	},
	data: function () {
		return {
			list: [
				{label: "居左", value: "left"},
				{label: "居中", value: "center"},
			],
			parent: this.$parent.data,
		};
	},
	created: function () {
		if(!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
		if (this.data.label == undefined) this.data.label = "文本位置";
		if (this.data.field == undefined) this.data.field = "textAlign";
		
		var self = this;
		setTimeout(function () {
			layui.use(['form'], function() {
				self.form = layui.form;
				self.form.render();
			});
		},10);
	},
	watch: {
		data: function (val, oldVal) {
			if (val.field == undefined) val.field = oldVal.field;
			if (val.label == undefined) val.label = "文本位置";
		},
	},
	methods: {
		verify : function () {
			var res = { code : true, message : "" };
			return res;
		},
	},
	template: navHtml
});