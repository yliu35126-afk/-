// 顶部内容组件
var presaleTopConHtml = '<div class="goods-head">';
	presaleTopConHtml +=	'<div class="title-wrap">';
	presaleTopConHtml +=		'<div class="left-icon" v-if="list.imageUrl && list.imageUrl.split(\'/\')[0] == \'public\'"><img v-bind:src="imgUrl" /></div>';
	presaleTopConHtml +=		'<div class="left-icon" v-if="list.imageUrl && list.imageUrl.split(\'/\')[0] != \'public\'"><img v-bind:src="$parent.$parent.changeImgUrl(list.imageUrl)" /></div>';
	presaleTopConHtml +=		'<span class="name">{{list.title}}</span>';
	presaleTopConHtml +=	'</div>';
	
	presaleTopConHtml +=	'<div class="more violet" v-if="data.bgSelect==\'violet\'">';
	presaleTopConHtml +=		'<span>';
	presaleTopConHtml +=			'<span style="color: #8662FD;">更多</span>';
	presaleTopConHtml +=			'<span style="color: #627BFD;">预售</span>';
	presaleTopConHtml +=		'</span>';
	presaleTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #627BFD;"></i>';
	presaleTopConHtml +=	'</div>';
	
	presaleTopConHtml +=	'<div class="more red" v-if="data.bgSelect==\'red\'">';
	presaleTopConHtml +=		'<span>';
	presaleTopConHtml +=			'<span style="color: #FF7B91;">更多</span>';
	presaleTopConHtml +=			'<span style="color: #FF5151;">预售</span>';
	presaleTopConHtml +=		'</span>';
	presaleTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #FF5151;"></i>';
	presaleTopConHtml +=	'</div>';

	presaleTopConHtml +=	'<div class="more blue" v-if="data.bgSelect==\'blue\'">';
	presaleTopConHtml +=		'<span>';
	presaleTopConHtml +=			'<span style="color: #12D0AE;">更多</span>';
	presaleTopConHtml +=			'<span style="color: #0ECFD3;">预售</span>';
	presaleTopConHtml +=		'</span>';
	presaleTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #0ECFD3;"></i>';
	presaleTopConHtml +=	'</div>';
	
	presaleTopConHtml +=	'<div class="more yellow" v-if="data.bgSelect==\'yellow\'">';
	presaleTopConHtml +=		'<span>';
	presaleTopConHtml +=			'<span style="color: #FEB632;">更多</span>';
	presaleTopConHtml +=			'<span style="color: #FE6232;">预售</span>';
	presaleTopConHtml +=		'</span>';
	presaleTopConHtml +=		'<i class="iconfont iconyoujiantou" style="color: #FE6232;"></i>';
	presaleTopConHtml +=	'</div>';

	/* presaleTopConHtml +=	'<div class="more ns-red-color" v-if="listMore.title">';
	presaleTopConHtml +=		'<span v-bind:style="{color: data.moreTextColor?data.moreTextColor:\'rgba(0,0,0,0)\'}">{{listMore.title}}</span>';
	presaleTopConHtml +=		'<div class="right-icon" v-if="listMore.imageUrl"><img v-bind:src="$parent.$parent.changeImgUrl(listMore.imageUrl)" /></div>';
	presaleTopConHtml +=		'<i class="iconfont iconyoujiantou" v-else v-bind:style="{color: data.moreTextColor?data.moreTextColor:\'rgba(0,0,0,0)\'}"></i>';
	presaleTopConHtml +=	'</div>'; */
	presaleTopConHtml +='</div>';

Vue.component("presale-top-content", {
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
	template: presaleTopConHtml
});

/**
 * 空的验证组件，后续如果增加业务，则更改组件
 */
var presaleListHtml = '<div class="goods-list-edit layui-form">';

		presaleListHtml += '<div class="layui-form-item ns-icon-radio">';
			presaleListHtml += '<label class="layui-form-label sm">商品来源</label>';
			presaleListHtml += '<div class="layui-input-block">';
				presaleListHtml += '<template v-for="(item, index) in goodsSources" v-bind:k="index">';
					presaleListHtml += '<span :class="[item.value == data.sources ? \'\' : \'layui-hide\']">{{item.text}}</span>';
				presaleListHtml += '</template>';
				presaleListHtml += '<ul class="ns-icon">';
					presaleListHtml += '<li v-for="(item, index) in goodsSources" v-bind:k="index" :class="[item.value == data.sources ? \'ns-text-color ns-border-color ns-bg-color-diaphaneity\' : \'\']" @click="data.sources=item.value">';
						presaleListHtml += `<i :class="['iconfont',item.src]"></i>`;
					presaleListHtml += '</li>';
				presaleListHtml += '</ul>';
			
				/* presaleListHtml += '<template v-for="(item,index) in goodsSources" v-bind:k="index">';
					presaleListHtml += '<div v-on:click="data.sources=item.value" v-bind:class="{ \'layui-unselect layui-form-radio\' : true,\'layui-form-radioed\' : (data.sources==item.value) }"><i class="layui-anim layui-icon">&#xe63f;</i><div>{{item.text}}</div></div>';
				presaleListHtml += '</template>'; */
			presaleListHtml += '</div>';
		presaleListHtml += '</div>';
		
		presaleListHtml += '<div class="layui-form-item" v-if="data.sources == \'diy\'">';
			presaleListHtml += '<label class="layui-form-label sm">手动选择</label>';
			presaleListHtml += '<div class="layui-input-block">';
				presaleListHtml += '<a href="#" class="ns-input-text selected-style" v-on:click="addGoods">选择<i class="layui-icon layui-icon-right"></i></a>';
			presaleListHtml += '</div>';
		presaleListHtml += '</div>';
		
		/* presaleListHtml += '<div class="layui-form-item" v-show="data.sources == \'default\'">';
			presaleListHtml += '<label class="layui-form-label sm">商品数量</label>';
			presaleListHtml += '<div class="layui-input-block">';
				presaleListHtml += '<input type="number" class="layui-input goods-account" v-on:keyup="shopNum" v-model="data.goodsCount"/>';
			presaleListHtml += '</div>';
		presaleListHtml += '</div>';
		
		presaleListHtml += '<div class="layui-form-item" v-show="data.sources == \'default\'">';
			presaleListHtml += '<label class="layui-form-label sm"></label>';
			presaleListHtml += '<div class="layui-input-block">';
				presaleListHtml += '<template v-for="(item,index) in goodsCount" v-bind:k="index">';
					presaleListHtml += '<div v-on:click="data.goodsCount=item" v-bind:class="{ \'layui-unselect layui-form-radio\' : true,\'layui-form-radioed\' : (data.goodsCount==item) }"><i class="layui-anim layui-icon">&#xe63f;</i><div>{{item}}</div></div>';
				presaleListHtml += '</template>';
			presaleListHtml += '</div>';
		presaleListHtml += '</div>'; */
		
		presaleListHtml += '<slide v-bind:data="{ field : \'goodsCount\', label: \'商品数量\', max: 9, min: 1}" v-show="data.sources == \'default\'"></slide>';

		// presaleListHtml += '<p class="hint">商品数量选择 0 时，前台会自动上拉加载更多</p>';
		
	presaleListHtml += '</div>';

var select_goods_list = []; //配合商品选择器使用
Vue.component("presale-list", {
	template: presaleListHtml,
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

			}, self.$parent.data.goodsId, {mode: "spu", promotion: "presale", disabled: 0, post: post});
		}
	}
});

var presaleStyleHtml = '<div class="layui-form-item">';
		presaleStyleHtml += '<label class="layui-form-label sm">选择风格</label>';
		presaleStyleHtml += '<div class="layui-input-block">';
			// presaleStyleHtml += '<span>{{data.styleName}}</span>';
			presaleStyleHtml += '<div v-if="data.styleName" class="ns-input-text ns-text-color selected-style" v-on:click="selectGroupbuyStyle">{{data.styleName}} <i class="layui-icon layui-icon-right"></i></div>';
			presaleStyleHtml += '<div v-else class="ns-input-text selected-style" v-on:click="selectGroupbuyStyle">选择 <i class="layui-icon layui-icon-right"></i></div>';
		presaleStyleHtml += '</div>';
	presaleStyleHtml += '</div>';

Vue.component("presale-style", {
	template: presaleStyleHtml,
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
				content: $(".draggable-element[data-index='" + self.data.index + "'] .edit-attribute .presale-list-style").html(),
				success: function(layero, index) {
					$(".layui-layer-content input[name='style']").val(self.data.style);
					$(".layui-layer-content input[name='style_name']").val(self.data.styleName);
					$("body").on("click", ".layui-layer-content .style-list-con-presale .style-li-presale", function () {
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
})

// 图片上传
var presaleTopHtml = '<ul class="fenxiao-addon-title">';
		presaleTopHtml += '<li>';
		
			presaleTopHtml += '<div class="layui-form-item">';
				presaleTopHtml += '<label class="layui-form-label sm">左侧图标</label>';
				presaleTopHtml += '<div class="layui-input-block ns-img-upload">';
					presaleTopHtml += '<img-sec-upload v-bind:data="{ data : list, text: \'\' }"></img-sec-upload>';
				presaleTopHtml += '</div>';
				presaleTopHtml += '<div class="ns-word-aux ns-diy-word-aux">建议上传图标大小：125px * 30px</div>';
			presaleTopHtml += '</div>';
			
			// presaleTopHtml += '<img-upload v-bind:data="{ data : list }"></img-upload>';
			presaleTopHtml += '<div class="content-block">';
				presaleTopHtml += '<div class="layui-form-item">';
					presaleTopHtml += '<label class="layui-form-label sm">标题内容</label>';
					presaleTopHtml += '<div class="layui-input-block">';
						presaleTopHtml += '<input type="text" name=\'title\' v-model="list.title" class="layui-input" />';
					presaleTopHtml += '</div>';
				presaleTopHtml += '</div>';
			presaleTopHtml += '</div>';
			
			// presaleTopHtml += '<color v-bind:data="{ field : \'titleTextColor\', label : \'标题颜色\', defaultcolor: \'#000\' }"></color>';
		presaleTopHtml += '</li>';
		
		/* presaleTopHtml += '<li>';
			presaleTopHtml += '<div class="content-block">';
				presaleTopHtml += '<div class="layui-form-item">';
					presaleTopHtml += '<label class="layui-form-label sm">文本内容</label>';
					presaleTopHtml += '<div class="layui-input-block">';
						presaleTopHtml += '<input type="text" name=\'title\' v-model="listMore.title" class="layui-input" />';
					presaleTopHtml += '</div>';
				presaleTopHtml += '</div>';
				presaleTopHtml += '<color v-bind:data="{ field : \'moreTextColor\', defaultcolor: \'#858585\' }"></color>';
				
			presaleTopHtml += '</div>';
		presaleTopHtml += '</li>'; */
	presaleTopHtml += '</ul>';

Vue.component("presale-top-list",{
	template : presaleTopHtml,
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
var presaleColorHtml = '<div class="layui-form-item ns-bg-select">';
	presaleColorHtml +=	 '<label class="layui-form-label sm">背景颜色</label>';
	presaleColorHtml +=	 '<div class="layui-input-block">';
	presaleColorHtml +=		 '<ul class="ns-bg-select-ul">';
	presaleColorHtml +=			 '<li v-for="(item, index) in colorList" v-bind:k="index" :class="[item.className == data.bgSelect ? \'ns-text-color ns-border-color\' : \'\']" @click="data.bgSelect = item.className">';
	presaleColorHtml +=				 '<div :style="{background: item.color}"></div>';
	presaleColorHtml +=			 '</li>';
	presaleColorHtml +=		 '</ul>';
	presaleColorHtml +=	 '</div>';
	presaleColorHtml += '</div>';

Vue.component("presale-color", {
	template: presaleColorHtml,
	data: function () {
		return {
			data: this.$parent.data,
			colorList: [
				{name: "红", className: "red", color: "#FFD7D7"},
				{name: "蓝", className: "blue", color: "#D7FAFF"},
				{name: "黄", className: "yellow", color: "#FFF4E0"},
				{name: "紫", className: "violet", color: "#F9E5FF"}
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
var changeType = '<div class="layui-form-item ns-icon-radio">';
		changeType += '<label class="layui-form-label sm">滑动方式</label>';
		changeType += '<div class="layui-input-block align-right">';
			changeType += '<template v-for="(item,index) in changeTypeList" v-bind:k="index">';
				changeType += '<div v-on:click="data.changeType=item.value" v-bind:class="{ \'layui-unselect layui-form-radio\' : true,\'layui-form-radioed\' : (data.changeType==item.value) }"><i class="layui-anim layui-icon">&#xe63f;</i><div>{{item.name}}</div></div>';
			changeType += '</template>';
		changeType += '</div>';
	/* changeType +=	 '<label class="layui-form-label sm">滑动方式</label>';
	changeType +=	 '<div class="layui-input-block">';
	changeType +=		 '<template v-for="(item, index) in changeTypeList" v-bind:k="index">';
	changeType +=			 '<span :class="[item.value == data.changeType ? \'\' : \'layui-hide\']">{{item.name}}</span>';
	changeType +=		 '</template>';
	changeType +=		 '<ul class="ns-icon">';
	changeType +=			 '<li v-for="(item, index) in changeTypeList" v-bind:k="index" :class="[item.value == data.changeType ? \'ns-text-color ns-border-color\' : \'\']" @click="data.changeType = item.value">';
	changeType +=				 '<img v-if="item.value == data.changeType" :src="item.selectedSrc" />'
	changeType +=				 '<img v-else :src="item.src" />'
	changeType +=			 '</li>';
	changeType +=		 '</ul>';
	changeType +=	 '</div>'; */
	changeType += '</div>';

Vue.component("change-type", {
	template: changeType,
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