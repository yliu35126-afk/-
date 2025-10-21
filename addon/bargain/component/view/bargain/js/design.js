// 顶部内容组件
var bargainTopConHtml = '<div class="goods-head">';
	bargainTopConHtml +=	'<div class="title-wrap">';
	bargainTopConHtml +=		'<div class="left-icon" v-if="list.imageUrl && list.imageUrl.split(\'/\')[0] == \'public\'"><img v-bind:src="imgUrl" /></div>';
	bargainTopConHtml +=		'<div class="left-icon" v-if="list.imageUrl && list.imageUrl.split(\'/\')[0] != \'public\'"><img v-bind:src="$parent.$parent.changeImgUrl(list.imageUrl)" /></div>';
	bargainTopConHtml +=		'<span class="name" v-if="!list.imageUrl">{{list.title}}</span>';
	bargainTopConHtml +=		'<span class="desc">低价一目了然</span>';
	bargainTopConHtml +=	'</div>';
	
	bargainTopConHtml +=	'<div class="more violet" v-if="data.bgSelect==\'violet\'">';
	bargainTopConHtml +=		'<span>';
	bargainTopConHtml +=			'<span style="color: #8662FD;">查看</span>';
	bargainTopConHtml +=			'<span style="color: #627BFD;">更多</span>';
	bargainTopConHtml +=		'</span>';
	bargainTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #627BFD;"></i>';
	bargainTopConHtml +=	'</div>';
	
	bargainTopConHtml +=	'<div class="more red" v-if="data.bgSelect==\'red\'">';
	bargainTopConHtml +=		'<span>';
	bargainTopConHtml +=			'<span style="color: #FF7B91;">查看</span>';
	bargainTopConHtml +=			'<span style="color: #FF5151;">更多</span>';
	bargainTopConHtml +=		'</span>';
	bargainTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #FF5151;"></i>';
	bargainTopConHtml +=	'</div>';
	
	bargainTopConHtml +=	'<div class="more blue" v-if="data.bgSelect==\'blue\'">';
	bargainTopConHtml +=		'<span>';
	bargainTopConHtml +=			'<span style="color: #12D0AE;">查看</span>';
	bargainTopConHtml +=			'<span style="color: #0ECFD3;">更多</span>';
	bargainTopConHtml +=		'</span>';
	bargainTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #0ECFD3;"></i>';
	bargainTopConHtml +=	'</div>';
	
	bargainTopConHtml +=	'<div class="more yellow" v-if="data.bgSelect==\'yellow\'">';
	bargainTopConHtml +=		'<span>';
	bargainTopConHtml +=			'<span style="color: #FEB632;">查看</span>';
	bargainTopConHtml +=			'<span style="color: #FE6232;">更多</span>';
	bargainTopConHtml +=		'</span>';
	bargainTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #FE6232;"></i>';
	bargainTopConHtml +=	'</div>';

	bargainTopConHtml +=	'<div class="more yellow" v-if="data.bgSelect==\'white\'">';
	bargainTopConHtml +=		'<span>';
	bargainTopConHtml +=			'<span style="color: #888;background: none;-webkit-text-fill-color: initial;">查看</span>';
	bargainTopConHtml +=			'<span style="color: #888;background: none;-webkit-text-fill-color: initial;">更多</span>';
	bargainTopConHtml +=		'</span>';
	bargainTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #888;background: none;-webkit-text-fill-color: initial;"></i>';
	bargainTopConHtml +=	'</div>';
	
	// bargainTopConHtml +=	'<div class="more ns-red-color" v-if="listMore.title">';
	// bargainTopConHtml +=		'<span v-bind:style="{color: data.moreTextColor}">{{listMore.title}}</span>';
	// bargainTopConHtml +=		'<div class="right-icon" v-if="listMore.imageUrl"><img v-bind:src="$parent.$parent.changeImgUrl(listMore.imageUrl)" /></div>';
	// bargainTopConHtml +=		'<i class="iconfont iconyoujiantou" v-else v-bind:style="{color: data.moreTextColor}"></i>';
	// bargainTopConHtml +=	'</div>';
	bargainTopConHtml +='</div>';

Vue.component("bargain-top-content", {
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
	template: bargainTopConHtml
});

/**
 * 空的验证组件，后续如果增加业务，则更改组件
 */
var bargainListHtml = '<div class="goods-list-edit layui-form">';

		bargainListHtml += '<div class="layui-form-item ns-icon-radio">';
			bargainListHtml += '<label class="layui-form-label sm">商品来源</label>';
			bargainListHtml += '<div class="layui-input-block">';
				bargainListHtml += '<template v-for="(item, index) in goodsSources" v-bind:k="index">';
					bargainListHtml += '<span :class="[item.value == data.sources ? \'\' : \'layui-hide\']">{{item.text}}</span>';
				bargainListHtml += '</template>';
				bargainListHtml += '<ul class="ns-icon">';
					bargainListHtml += '<li v-for="(item, index) in goodsSources" v-bind:k="index" :class="[item.value == data.sources ? \'ns-text-color ns-border-color ns-bg-color-diaphaneity\' : \'\']" @click="data.sources=item.value">';
						bargainListHtml += `<i :class="['iconfont',item.src]"></i>`;
					bargainListHtml += '</li>';
				bargainListHtml += '</ul>';
			bargainListHtml += '</div>';
		bargainListHtml += '</div>';
		
		bargainListHtml += '<div class="layui-form-item" v-if="data.sources == \'diy\'">';
			bargainListHtml += '<label class="layui-form-label sm">手动选择</label>';
			bargainListHtml += '<div class="layui-input-block">';
				bargainListHtml += '<a href="#" class="ns-input-text selected-style" v-on:click="addGoods">选择 <i class="layui-icon layui-icon-right"></i></a>';
			bargainListHtml += '</div>';
		bargainListHtml += '</div>';
		
		bargainListHtml += '<slide v-bind:data="{ field : \'goodsCount\', label: \'商品数量\', max: 9, min: 1}" v-show="data.sources == \'default\'"></slide>';
		// bargainListHtml += '<div class="layui-form-item" v-show="data.sources == \'default\'">';
		// 	bargainListHtml += '<label class="layui-form-label sm">商品数量</label>';
		// 	bargainListHtml += '<div class="layui-input-block">';
		// 		// bargainListHtml += '<input class="layui-input goods-account" v-model="data.goodsCount" />';
		// 		bargainListHtml += '<input type="number" class="layui-input goods-account" v-on:keyup="shopNum" v-model="data.goodsCount"/>';
		// 	bargainListHtml += '</div>';
		// bargainListHtml += '</div>';
		
		// bargainListHtml += '<div class="layui-form-item" v-show="data.sources == \'default\'">';
		// 	bargainListHtml += '<label class="layui-form-label sm"></label>';
		// 	bargainListHtml += '<div class="layui-input-block">';
		// 		bargainListHtml += '<template v-for="(item,index) in goodsCount" v-bind:k="index">';
		// 			bargainListHtml += '<div v-on:click="data.goodsCount=item" v-bind:class="{ \'layui-unselect layui-form-radio\' : true,\'layui-form-radioed\' : (data.goodsCount==item) }"><i class="layui-anim layui-icon">&#xe643;</i><div>{{item}}</div></div>';
		// 		bargainListHtml += '</template>';
		// 	bargainListHtml += '</div>';
		// bargainListHtml += '</div>';

		// bargainListHtml += '<p class="hint">商品数量选择 0 时，前台会自动上拉加载更多</p>';
		
	bargainListHtml += '</div>';

var select_goods_list = []; //配合商品选择器使用
Vue.component("bargain-list", {
	template: bargainListHtml,
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
		shopNum: function(){
			if (this.$parent.data.goodsCount > 50){
				layer.msg("商品数量最多为50");
				this.$parent.data.goodsCount = 50;
			}
			if (this.$parent.data.goodsCount.length > 0 && this.$parent.data.goodsCount < 1) {
				layer.msg("商品数量不能小于0");
				this.$parent.data.goodsCount = 1;
			}
		},
		verify : function () {
			var res = { code : true, message : "" };
			if(this.$parent.data.goodsCount.length===0) {
				res.code = false;
				res.message = "请输入商品数量";
			}
			if (this.$parent.data.goodsCount < 0) {
				res.code = false;
				res.message = "商品数量不能小于0";
			}
			if(this.$parent.data.goodsCount > 50){
				res.message = "商品数量最多为50";
			}
			return res;
		},
		addGoods: function(){
			var self = this;

			goodsSelect(function (res) {
				// if (!res.length) return false;
				self.$parent.data.goodsId = [];
				for (var i=0; i<res.length; i++) {
					self.$parent.data.goodsId.push(res[i]);
				}

			}, self.$parent.data.goodsId, {mode: "spu", promotion: "bargain",disabled:0});
		}
	}
});

var bargainStyleHtml = '<div class="layui-form-item">';
		bargainStyleHtml += '<label class="layui-form-label sm">选择风格</label>';
		bargainStyleHtml += '<div class="layui-input-block">';
			bargainStyleHtml += '<div v-if="data.styleName" class="ns-input-text ns-text-color selected-style" v-on:click="selectGroupbuyStyle">{{data.styleName}} <i class="layui-icon layui-icon-right"></i></div>';
			bargainStyleHtml += '<div v-else class="ns-input-text selected-style" v-on:click="selectGroupbuyStyle">选择 <i class="layui-icon layui-icon-right"></i></div>';
		bargainStyleHtml += '</div>';
	bargainStyleHtml += '</div>';

Vue.component("bargain-style", {
	template: bargainStyleHtml,
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
				content: $(".draggable-element[data-index='" + self.data.index + "'] .edit-attribute .bargain-list-style").html(),
				success: function(layero, index) {
					$(".layui-layer-content input[name='style']").val(self.data.style);
					$("body").on("click", ".layui-layer-content .style-list-con-bargain .style-li-bargain", function () {
						$(this).addClass("selected ns-border-color").siblings().removeClass("selected ns-border-color");
						$(".layui-layer-content input[name='style']").val($(this).index() + 1);
					});
				},
				yes: function (index, layero) {
					self.data.style = $(".layui-layer-content input[name='style']").val();
					layer.closeAll()
				}
			});
		},
	}
});

// 图片上传
var bargainTopHtml = '<ul class="fenxiao-addon-title">';
		bargainTopHtml += '<li>';
		
			bargainTopHtml += '<div class="layui-form-item">';
				bargainTopHtml += '<label class="layui-form-label sm">左侧图标</label>';
				bargainTopHtml += '<div class="layui-input-block ns-img-upload">';
					bargainTopHtml += '<img-sec-upload v-bind:data="{ data : list, text: \'\' }"></img-sec-upload>';
				bargainTopHtml += '</div>';
				bargainTopHtml += '<div class="ns-word-aux ns-diy-word-aux">建议上传图片大小：125px * 30px</div>';
			bargainTopHtml += '</div>';
			
			// bargainTopHtml += '<img-upload v-bind:data="{ data : list }"></img-upload>';
			bargainTopHtml += '<div class="content-block">';
				bargainTopHtml += '<div class="layui-form-item">';
					bargainTopHtml += '<label class="layui-form-label sm">标题内容</label>';
					bargainTopHtml += '<div class="layui-input-block">';
						bargainTopHtml += '<input type="text" name=\'title\' v-model="list.title" class="layui-input" />';
					bargainTopHtml += '</div>';
				bargainTopHtml += '</div>';
			bargainTopHtml += '</div>';
			
			// bargainTopHtml += '<color v-bind:data="{ field : \'titleTextColor\', label : \'标题颜色\', defaultcolor: \'defaultTitleTextColor\' }"></color>';
		bargainTopHtml += '</li>';
		
		// bargainTopHtml += '<li>';
			// bargainTopHtml += '<div class="layui-form-item">';
			// 	bargainTopHtml += '<label class="layui-form-label sm">右侧图标</label>';
			// 	bargainTopHtml += '<div class="layui-input-block">';
			// 		bargainTopHtml += '<img-upload v-bind:data="{ data : item }"></img-upload>';
			// 	bargainTopHtml += '</div>';
			// bargainTopHtml += '</div>';
			
			// bargainTopHtml += '<div class="content-block">';
			// 	bargainTopHtml += '<div class="layui-form-item">';
			// 		bargainTopHtml += '<label class="layui-form-label sm">文本内容</label>';
			// 		bargainTopHtml += '<div class="layui-input-block">';
			// 			bargainTopHtml += '<input type="text" name=\'title\' v-model="listMore.title" class="layui-input" />';
			// 		bargainTopHtml += '</div>';
			// 	bargainTopHtml += '</div>';
			// 	bargainTopHtml += '<color v-bind:data="{ field : \'moreTextColor\', defaultcolor: \'defaultMoreTextColor\' }"></color>';
				
				// bargainTopHtml += '<nc-link v-bind:data="{ field : $parent.data.list[index].link }"></nc-link>';
				
		// 	bargainTopHtml += '</div>';
		// bargainTopHtml += '</li>';
	bargainTopHtml += '</ul>';

Vue.component("bargain-top-list",{
	template : bargainTopHtml,
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
var bgColorSelectHtml = '<div class="layui-form-item ns-bg-select">';
	bgColorSelectHtml +=	 '<label class="layui-form-label sm">背景颜色</label>';
	bgColorSelectHtml +=	 '<div class="layui-input-block">';
	bgColorSelectHtml +=		 '<ul class="ns-bg-select-ul">';
	bgColorSelectHtml +=			 '<li v-for="(item, index) in colorList" v-bind:k="index" :class="[item.className == data.bgSelect ? \'ns-text-color ns-border-color\' : \'\']" @click="data.bgSelect = item.className">';
	bgColorSelectHtml +=				 '<div :style="{background: item.color}"></div>';
	bgColorSelectHtml +=			 '</li>';
	bgColorSelectHtml +=		 '</ul>';
	bgColorSelectHtml +=	 '</div>';
	bgColorSelectHtml += '</div>';

Vue.component("bargin-color", {
	template: bgColorSelectHtml,
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
var bargainChangeType = '<div class="layui-form-item ns-icon-radio">';
		bargainChangeType += '<label class="layui-form-label sm">滑动方式</label>';
		bargainChangeType += '<div class="layui-input-block align-right">';
			bargainChangeType += '<template v-for="(item,index) in changeTypeList" v-bind:k="index">';
				bargainChangeType += '<div v-on:click="data.changeType=item.value" v-bind:class="{ \'layui-unselect layui-form-radio\' : true,\'layui-form-radioed\' : (data.changeType==item.value) }"><i class="layui-anim layui-icon">&#xe63f;</i><div>{{item.text}}</div></div>';
			bargainChangeType += '</template>';
		bargainChangeType += '</div>'; 
		
	/* bargainChangeType +=	 '<label class="layui-form-label sm">滑动方式</label>';
	bargainChangeType +=	 '<div class="layui-input-block">';
	bargainChangeType +=		 '<template v-for="(item, index) in changeTypeList" v-bind:k="index">';
	bargainChangeType +=			 '<span :class="[item.value == data.changeType ? \'\' : \'layui-hide\']">{{item.name}}</span>';
	bargainChangeType +=		 '</template>';
	bargainChangeType +=		 '<ul class="ns-icon">';
	bargainChangeType +=			 '<li v-for="(item, index) in changeTypeList" v-bind:k="index" :class="[item.value == data.changeType ? \'ns-text-color ns-border-color\' : \'\']" @click="data.changeType = item.value">';
	bargainChangeType +=				 '<img v-if="item.value == data.changeType" :src="item.selectedSrc" />';
	bargainChangeType +=				 '<img v-else :src="item.src" />';
	bargainChangeType +=			 '</li>';
	bargainChangeType +=		 '</ul>';
	bargainChangeType +=	 '</div>'; */
	bargainChangeType += '</div>';

Vue.component("bargain-change-type", {
	template: bargainChangeType,
	data: function () {
		return {
			data: this.$parent.data,
			changeTypeList: [
				{text: "平移滑动", value: 1},
				{text: "切屏滑动", value: 2},
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


