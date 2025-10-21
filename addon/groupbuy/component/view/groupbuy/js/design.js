// 顶部内容组件
var groupbuyTopConHtml = '<div class="goods-head">';
	groupbuyTopConHtml +=	'<div class="title-wrap">';
	groupbuyTopConHtml +=		'<div class="left-icon" v-if="list.imageUrl && list.imageUrl.split(\'/\')[0] == \'public\'"><img v-bind:src="imgUrl" /></div>';
	groupbuyTopConHtml +=		'<div class="left-icon" v-if="list.imageUrl && list.imageUrl.split(\'/\')[0] != \'public\'"><img v-bind:src="$parent.$parent.changeImgUrl(list.imageUrl)" /></div>';
	groupbuyTopConHtml +=		'<span class="name" v-if="!list.imageUrl">{{list.title}}</span>';
	groupbuyTopConHtml +=		'<span class="desc">一起团购才实惠</span>';
	groupbuyTopConHtml +=	'</div>';
	
	groupbuyTopConHtml +=	'<div class="more violet" v-if="data.bgSelect==\'violet\'">';
	groupbuyTopConHtml +=		'<span>';
	groupbuyTopConHtml +=			'<span style="color: #8662FD;">查看</span>';
	groupbuyTopConHtml +=			'<span style="color: #627BFD;">更多</span>';
	groupbuyTopConHtml +=		'</span>';
	groupbuyTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #627BFD;"></i>';
	groupbuyTopConHtml +=	'</div>';
	
	groupbuyTopConHtml +=	'<div class="more red" v-if="data.bgSelect==\'red\'">';
	groupbuyTopConHtml +=		'<span>';
	groupbuyTopConHtml +=			'<span style="color: #FF7B91;">查看</span>';
	groupbuyTopConHtml +=			'<span style="color: #FF5151;">更多</span>';
	groupbuyTopConHtml +=		'</span>';
	groupbuyTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #FF5151;"></i>';
	groupbuyTopConHtml +=	'</div>';
	
	groupbuyTopConHtml +=	'<div class="more blue" v-if="data.bgSelect==\'blue\'">';
	groupbuyTopConHtml +=		'<span>';
	groupbuyTopConHtml +=			'<span style="color: #12D0AE;">查看</span>';
	groupbuyTopConHtml +=			'<span style="color: #0ECFD3;">更多</span>';
	groupbuyTopConHtml +=		'</span>';
	groupbuyTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #0ECFD3;"></i>';
	groupbuyTopConHtml +=	'</div>';
	
	groupbuyTopConHtml +=	'<div class="more yellow" v-if="data.bgSelect==\'yellow\'">';
	groupbuyTopConHtml +=		'<span>';
	groupbuyTopConHtml +=			'<span style="color: #FEB632;">查看</span>';
	groupbuyTopConHtml +=			'<span style="color: #FE6232;">更多</span>';
	groupbuyTopConHtml +=		'</span>';
	groupbuyTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #FE6232;"></i>';
	groupbuyTopConHtml +=	'</div>';

	groupbuyTopConHtml +=	'<div class="more yellow" v-if="data.bgSelect==\'white\'">';
	groupbuyTopConHtml +=		'<span>';
	groupbuyTopConHtml +=			'<span style="color: #888;background: none;-webkit-text-fill-color: initial;">查看</span>';
	groupbuyTopConHtml +=			'<span style="color: #888;background: none;-webkit-text-fill-color: initial;">更多</span>';
	groupbuyTopConHtml +=		'</span>';
	groupbuyTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #888;background: none;-webkit-text-fill-color: initial;"></i>';
	groupbuyTopConHtml +=	'</div>';
	
	groupbuyTopConHtml +='</div>';

Vue.component("groupbuy-top-content", {
	data: function () {
		return {
			data: this.$parent.data,
			list: this.$parent.data.list,
			listMore: this.$parent.data.listMore,
			imgUrl: ""
		}
	},
	created: function () {
		this.imgUrl = ns.img(this.list.imageUrl);
		
		if(!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
	},
	methods: {
		verify : function () {
			var res = { code : true, message : "" };
			return res;
		},
	},
	template: groupbuyTopConHtml
});


/**
 * 空的验证组件，后续如果增加业务，则更改组件
 */
var groupbuyListHtml = '<div class="goods-list-edit layui-form">';

		groupbuyListHtml += '<div class="layui-form-item ns-icon-radio">';
			groupbuyListHtml += '<label class="layui-form-label sm">商品来源</label>';
			groupbuyListHtml += '<div class="layui-input-block">';
				groupbuyListHtml += '<template v-for="(item, index) in goodsSources" v-bind:k="index">';
					groupbuyListHtml += '<span :class="[item.value == data.sources ? \'\' : \'layui-hide\']">{{item.text}}</span>';
				groupbuyListHtml += '</template>';
				groupbuyListHtml += '<ul class="ns-icon">';
					groupbuyListHtml += '<li v-for="(item, index) in goodsSources" v-bind:k="index" :class="[item.value == data.sources ? \'ns-text-color ns-border-color ns-bg-color-diaphaneity\' : \'\']" @click="data.sources=item.value">';
						groupbuyListHtml += `<i :class="['iconfont',item.src]"></i>`;
					groupbuyListHtml += '</li>';
				groupbuyListHtml += '</ul>';
			
				/* groupbuyListHtml += '<template v-for="(item,index) in goodsSources" v-bind:k="index">';
					groupbuyListHtml += '<div v-on:click="data.sources=item.value" v-bind:class="{ \'layui-unselect layui-form-radio\' : true,\'layui-form-radioed\' : (data.sources==item.value) }"><i class="layui-anim layui-icon">&#xe63f;</i><div>{{item.text}}</div></div>';
				groupbuyListHtml += '</template>'; */
			groupbuyListHtml += '</div>';
		groupbuyListHtml += '</div>';
		
		groupbuyListHtml += '<div class="layui-form-item" v-if="data.sources == \'diy\'">';
			groupbuyListHtml += '<label class="layui-form-label sm">手动选择</label>';
			groupbuyListHtml += '<div class="layui-input-block">';
				groupbuyListHtml += '<a href="#" class="ns-input-text selected-style" v-on:click="addGoods">选择<i class="layui-icon layui-icon-right"></i></a>';
			groupbuyListHtml += '</div>';
		groupbuyListHtml += '</div>';
		
		groupbuyListHtml += '<slide v-bind:data="{ field : \'goodsCount\', label: \'商品数量\', max: 9, min: 1}" v-show="data.sources == \'default\'"></slide>';
		
	groupbuyListHtml += '</div>';
var select_goods_list = []; //配合商品选择器使用
Vue.component("groupbuy-list", {
	template: groupbuyListHtml,
	data: function () {
		var url = post == 'shop'?'':'_admin';
		return {
			data: this.$parent.data,
			goodsSources: [
				{
					text: "默认",
					value: "default",
					src: "iconmofang"
				},
				{
					text : "手动选择",
					value : "diy",
					src: "iconshoudongxuanze"
				}
			],
			categoryList: [],
			isLoad: false,
			isShow: false,
			selectIndex: 0,//当前选中的下标
			goodsCount: [6, 12, 18, 24, 30],
		}
	},
	created:function() {
		if(!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
	},
	methods: {
		shopNum: function () {
			if (this.$parent.data.goodsCount > 50) {
				layer.msg("商品数量最多为50");
				this.$parent.data.goodsCount = 50;
			}
			if (this.$parent.data.goodsCount.length > 0 && this.$parent.data.goodsCount < 1) {
				layer.msg("商品数量不能小于0");
				this.$parent.data.goodsCount = 1;
			}
		},
		verify: function () {
			var res = {code: true, message: ""};
			if (this.data.goodsCount.length === 0) {
				res.code = false;
				res.message = "请输入商品数量";
			}
			if (this.data.goodsCount < 0) {
				res.code = false;
				res.message = "商品数量不能小于0";
			}
			if (this.data.goodsCount > 50) {
				res.message = "商品数量最多为50";
			}
			return res;
		},
		addGoods: function () {
			var self = this;
			goodsSelect(function (res) {
				// if (!res.length) return false;
				// self.$parent.data.goodsId = [];
				// for (var i = 0; i < res.length; i++) {
				// 	self.$parent.data.goodsId.push(res[i]);
				// }
				self.$parent.data.goodsId = res;

			}, self.$parent.data.goodsId, {mode: "spu", promotion: "groupbuy", disabled: 0, post: post});
		},
	}
});

var groupbuyStyleHtml = '<div class="layui-form-item">';
		groupbuyStyleHtml += '<label class="layui-form-label sm">选择风格</label>';
		groupbuyStyleHtml += '<div class="layui-input-block">';
			// groupbuyStyleHtml += '<span>{{data.styleName}}</span>';
			groupbuyStyleHtml += '<div v-if="data.styleName" class="ns-input-text ns-text-color selected-style" v-on:click="selectGroupbuyStyle">{{data.styleName}} <i class="layui-icon layui-icon-right"></i></div>';
			groupbuyStyleHtml += '<div v-else class="ns-input-text selected-style" v-on:click="selectGroupbuyStyle">选择 <i class="layui-icon layui-icon-right"></i></div>';
		groupbuyStyleHtml += '</div>';
	groupbuyStyleHtml += '</div>';

Vue.component("groupbuy-style", {
	template: groupbuyStyleHtml,
	data: function() {
		return {
			data: this.$parent.data,
		}
	},
	created:function() {
		if(!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
	},
	methods: {
		verify: function () {
			var res = { code: true, message: "" };
			return res;
		},
		selectGroupbuyStyle: function() {
			var self = this;
			layer.open({
				type: 1,
				title: '风格选择',
				area:['930px','630px'],
				btn: ['确定', '返回'],
				content: $(".draggable-element[data-index='" + self.data.index + "'] .edit-attribute .groupbuy-list-style").html(),
				success: function(layero, index) {
					$(".layui-layer-content input[name='style']").val(self.data.style);
					$(".layui-layer-content input[name='style_name']").val(self.data.styleName);
					$("body").on("click", ".layui-layer-content .style-list-con-groupbuy .style-li-groupbuy", function () {
						$(this).addClass("selected ns-border-color").siblings().removeClass("selected ns-border-color");
						$(".layui-layer-content input[name='style']").val($(this).index() + 1);
						$(".layui-layer-content input[name='style_name']").val($(this).find("span").text());
					});
				},
				yes: function (index, layero) {
					self.data.style = $(".layui-layer-content input[name='style']").val();
					self.data.styleName = $(".layui-layer-content input[name='style_name']").val();
					layer.closeAll()
				}
			});
		},
	}
});

// 图片上传
var groupbuyTopHtml = '<ul class="fenxiao-addon-title">';
		groupbuyTopHtml += '<li>';
		
			groupbuyTopHtml += '<div class="layui-form-item">';
				groupbuyTopHtml += '<label class="layui-form-label sm">左侧图标</label>';
				groupbuyTopHtml += '<div class="layui-input-block ns-img-upload">';
					groupbuyTopHtml += '<img-sec-upload v-bind:data="{ data : list, text: \'\' }"></img-sec-upload>';
				groupbuyTopHtml += '</div>';
				groupbuyTopHtml += '<div class="ns-word-aux ns-diy-word-aux">建议上传图标大小：125px * 30px</div>';
			groupbuyTopHtml += '</div>';
			
			// groupbuyTopHtml += '<img-upload v-bind:data="{ data : item }"></img-upload>';
			groupbuyTopHtml += '<div class="content-block">';
				groupbuyTopHtml += '<div class="layui-form-item">';
					groupbuyTopHtml += '<label class="layui-form-label sm">标题内容</label>';
					groupbuyTopHtml += '<div class="layui-input-block">';
						groupbuyTopHtml += '<input type="text" name=\'title\' v-model="list.title" class="layui-input" />';
					groupbuyTopHtml += '</div>';
				groupbuyTopHtml += '</div>';
			groupbuyTopHtml += '</div>';
			
			// groupbuyTopHtml += '<color v-bind:data="{ field : \'titleTextColor\', label : \'标题颜色\', defaultcolor: \'#000\' }"></color>';
		groupbuyTopHtml += '</li>';
		
		/* groupbuyTopHtml += '<li>';
			groupbuyTopHtml += '<div class="content-block">';
				groupbuyTopHtml += '<div class="layui-form-item">';
					groupbuyTopHtml += '<label class="layui-form-label sm">文本内容</label>';
					groupbuyTopHtml += '<div class="layui-input-block">';
						groupbuyTopHtml += '<input type="text" name=\'title\' v-model="listMore.title" class="layui-input" />';
					groupbuyTopHtml += '</div>';
				groupbuyTopHtml += '</div>';
				groupbuyTopHtml += '<color v-bind:data="{ field : \'moreTextColor\', defaultcolor: \'#858585\' }"></color>';
				
			groupbuyTopHtml += '</div>';
		groupbuyTopHtml += '</li>'; */
	groupbuyTopHtml += '</ul>';

Vue.component("groupbuy-top-list",{
	template : groupbuyTopHtml,
	data : function(){
		return {
            data : this.$parent.data,
			list : this.$parent.data.list,
			listMore: this.$parent.data.listMore
		};
	},
	created : function(){
		if(!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
	},
	watch : {
	},
	methods : {
		verify:function () {
			var res = { code : true, message : "" };
			var _self = this;
			$(".draggable-element[data-index='" + this.data.index + "'] .graphic-navigation .graphic-nav-list>ul>li").each(function(index){
				
				if(_self.selectedTemplate == "imageNavigation"){
					$(this).find("input[name='title']").removeAttr("style");//清空输入框的样式
					//检测是否有未上传的图片
					if(_self.list[index].imageUrl == ""){
						res.code = false;
						res.message = "请选择一张图片";
						$(this).find(".error-msg").text("请选择一张图片").show();
						return res;
					}else{
						$(this).find(".error-msg").text("").hide();
					}
				}else{
					if(_self.list[index].title == ""){
						res.code = false;
						res.message = "请输入标题";
						$(this).find("input[name='title']").attr("style","border-color:red !important;").focus();
						$(this).find(".error-msg").text("请输入标题").show();
						return res;
					}else{
						$(this).find("input[name='title']").removeAttr("style");
						$(this).find(".error-msg").text("").hide();
					}
				}
			});
			return res;
		}
	}
});


// 背景颜色可选
var groupbuyColorHtml = '<div class="layui-form-item ns-bg-select">';
	groupbuyColorHtml +=	 '<label class="layui-form-label sm">背景颜色</label>';
	groupbuyColorHtml +=	 '<div class="layui-input-block">';
	groupbuyColorHtml +=		 '<ul class="ns-bg-select-ul">';
	groupbuyColorHtml +=			 '<li v-for="(item, index) in colorList" v-bind:k="index" :class="[item.className == data.bgSelect ? \'ns-text-color ns-border-color\' : \'\']" @click="data.bgSelect = item.className">';
	groupbuyColorHtml +=				 '<div :style="{background: item.color}"></div>';
	groupbuyColorHtml +=			 '</li>';
	groupbuyColorHtml +=		 '</ul>';
	groupbuyColorHtml +=	 '</div>';
	groupbuyColorHtml += '</div>';

Vue.component("groupbuy-color", {
	template: groupbuyColorHtml,
	data: function () {
		return {
			data: this.$parent.data,
			colorList: [
				{name: "红", className: "red", color: "#FFD7D7"},
				{name: "蓝", className: "blue", color: "#D7FAFF"},
				{name: "黄", className: "yellow", color: "#FFF4E0"},
				{name: "紫", className: "violet", color: "#F9E5FF"},
				{name: "白", className: "white", color: "#FFFFFF"}
			]
		};
	},
	created: function () {
		if(!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
	},
	methods: {
		verify : function () {
			var res = { code : true, message : "" };
			return res;
		}
	},
});


// 切换方式
var groupbuyChangeType = '<div class="layui-form-item ns-icon-radio">';

		groupbuyChangeType += '<label class="layui-form-label sm">滑动方式</label>';
		groupbuyChangeType += '<div class="layui-input-block align-right">';
			groupbuyChangeType += '<template v-for="(item,index) in changeTypeList" v-bind:k="index">';
				groupbuyChangeType += '<div v-on:click="data.changeType=item.value" v-bind:class="{ \'layui-unselect layui-form-radio\' : true,\'layui-form-radioed\' : (data.changeType==item.value) }"><i class="layui-anim layui-icon">&#xe63f;</i><div>{{item.name}}</div></div>';
			groupbuyChangeType += '</template>';
		groupbuyChangeType += '</div>';
		 
	/* groupbuyChangeType +=	 '<label class="layui-form-label sm">滑动方式</label>';
	groupbuyChangeType +=	 '<div class="layui-input-block">';
	groupbuyChangeType +=		 '<template v-for="(item, index) in changeTypeList" v-bind:k="index">';
	groupbuyChangeType +=			 '<span :class="[item.value == data.changeType ? \'\' : \'layui-hide\']">{{item.name}}</span>';
	groupbuyChangeType +=		 '</template>';
	groupbuyChangeType +=		 '<ul class="ns-icon">';
	groupbuyChangeType +=			 '<li v-for="(item, index) in changeTypeList" v-bind:k="index" :class="[item.value == data.changeType ? \'ns-text-color ns-border-color\' : \'\']" @click="data.changeType = item.value">';
	groupbuyChangeType +=				 '<img v-if="item.value == data.changeType" :src="item.selectedSrc" />';
	groupbuyChangeType +=				 '<img v-else :src="item.src" />';
	groupbuyChangeType +=			 '</li>';
	groupbuyChangeType +=		 '</ul>';
	groupbuyChangeType +=	 '</div>'; */
	groupbuyChangeType += '</div>';

Vue.component("groupbuy-change-type", {
	template: groupbuyChangeType,
	data: function () {
		return {
			data: this.$parent.data,
			changeTypeList: [
				{name: "平移滑动", value: 1},
				{name: "切屏滑动", value: 2},
			]
		};
	},
	created: function () {
		if(!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
	},
	methods: {
		verify : function () {
			var res = { code : true, message : "" };
			return res;
		}
	},
});