/**
 * 空的验证组件，后续如果增加业务，则更改组件
 */
var shopContentHtml = '<div class="goods-list-edit layui-form">';
	shopContentHtml += '</div>';

Vue.component("shop-content-empty", {
	template: shopContentHtml,
	data: function () {
		return {
			data: this.$parent.data,
		}
	},
	created:function() {

	},
	methods: {
		switchType:function (type) {
			console.log(type)
		}
	}
});


/**
 * [样式]·组件
 */
var shopNavHtml = '<div class="shop_content">';
shopNavHtml += '<div class="shop-nav">';

shopNavHtml += '<div class="shop-nav-item" :class="[item.type == type ? \'active\' : \'\']" v-for="(item, index) in typeList" v-bind:key="index"  @click="switchType(item.type)">';
shopNavHtml += '<img v-bind:src="item.type == type ? $parent.$parent.changeImgUrl(item.select_path) : $parent.$parent.changeImgUrl(item.path)">';
shopNavHtml += '<span :style="{\'color\' : item.type == type ? data.color : \'#222\'}">{{item.text}}</span>';
shopNavHtml += '<span :style="{\'background\' : item.type == type ? data.color : \'#fff\'}" class="line"></span>';

shopNavHtml += '</div>';

shopNavHtml += '</div>';

shopNavHtml += '<div class="shop-content-list">';
shopNavHtml += '<div class="shop-content-item ranking-box" v-show="type == 1">';
shopNavHtml += '<div class="title" >本地榜单</div>';
shopNavHtml += '<div class="switch-tab">';
shopNavHtml += '<div class="active" :style="{\'background\' : data.color}">收藏排行</div>';
shopNavHtml += '<div>销量排行</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="ranking-list" >';

shopNavHtml += '<div class="goods-item">';
shopNavHtml += '<div class="goods-img">';
shopNavHtml += '<img src="'+STATICEXT_IMG+'/crack_figure.png">';
shopNavHtml += '<img class="ranking-icon" src="'+resourcePath+'/shop_content/img/ranking_1.png">';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-wrap">';
shopNavHtml += '<div class="name-wrap">';
shopNavHtml += '<div class="goods-name">商品名称</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="pro-info">';
shopNavHtml += '<div class="discount-price" :style="{\'color\' : data.color}">';
shopNavHtml += '<span class="unit">¥</span>25.69';
shopNavHtml += '</div>';
shopNavHtml += '<div class="sale">1005人付款</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-label">';
shopNavHtml += '<div :style="{\'color\' : data.color, \'border-color\':data.color}">包邮</div>';
shopNavHtml += '<div>假一赔十</div>';
shopNavHtml += '<div>破损包赔</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';

shopNavHtml += '<div class="goods-item">';
shopNavHtml += '<div class="goods-img">';
shopNavHtml += '<img src="'+STATICEXT_IMG+'/crack_figure.png">';
shopNavHtml += '<img class="ranking-icon" src="'+resourcePath+'/shop_content/img/ranking_2.png">';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-wrap">';
shopNavHtml += '<div class="name-wrap">';
shopNavHtml += '<div class="goods-name">商品名称</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="pro-info">';
shopNavHtml += '<div class="discount-price" :style="{\'color\' : data.color}">';
shopNavHtml += '<span class="unit">¥</span>25.69';
shopNavHtml += '</div>';
shopNavHtml += '<div class="sale">1005人付款</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-label">';
shopNavHtml += '<div :style="{\'color\' : data.color, \'border-color\':data.color}">包邮</div>';
shopNavHtml += '<div>假一赔十</div>';
shopNavHtml += '<div>破损包赔</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';

shopNavHtml += '<div class="goods-item">';
shopNavHtml += '<div class="goods-img">';
shopNavHtml += '<img src="'+STATICEXT_IMG+'/crack_figure.png">';
shopNavHtml += '<img class="ranking-icon" src="'+resourcePath+'/shop_content/img/ranking_3.png">';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-wrap">';
shopNavHtml += '<div class="name-wrap">';
shopNavHtml += '<div class="goods-name">商品名称</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="pro-info">';
shopNavHtml += '<div class="discount-price" :style="{\'color\' : data.color}">';
shopNavHtml += '<span class="unit">¥</span>25.69';
shopNavHtml += '</div>';
shopNavHtml += '<div class="sale">1005人付款</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-label">';
shopNavHtml += '<div :style="{\'color\' : data.color, \'border-color\':data.color}">包邮</div>';
shopNavHtml += '<div>假一赔十</div>';
shopNavHtml += '<div>破损包赔</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';

shopNavHtml += '<div class="goods-item">';
shopNavHtml += '<div class="goods-img">';
shopNavHtml += '<img src="'+STATICEXT_IMG+'/crack_figure.png">';
shopNavHtml += '<img class="ranking-icon" src="'+resourcePath+'/shop_content/img/ranking_4.png">';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-wrap">';
shopNavHtml += '<div class="name-wrap">';
shopNavHtml += '<div class="goods-name">商品名称</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="pro-info">';
shopNavHtml += '<div class="discount-price" :style="{\'color\' : data.color}">';
shopNavHtml += '<span class="unit">¥</span>25.69';
shopNavHtml += '</div>';
shopNavHtml += '<div class="sale">1005人付款</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-label">';
shopNavHtml += '<div :style="{\'color\' : data.color, \'border-color\':data.color}">包邮</div>';
shopNavHtml += '<div>假一赔十</div>';
shopNavHtml += '<div>破损包赔</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';

shopNavHtml += '<div class="goods-item">';
shopNavHtml += '<div class="goods-img">';
shopNavHtml += '<img src="'+STATICEXT_IMG+'/crack_figure.png">';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-wrap">';
shopNavHtml += '<div class="name-wrap">';
shopNavHtml += '<div class="goods-name">商品名称</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="pro-info">';
shopNavHtml += '<div class="discount-price" :style="{\'color\' : data.color}">';
shopNavHtml += '<span class="unit">¥</span>25.69';
shopNavHtml += '</div>';
shopNavHtml += '<div class="sale">1005人付款</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-label">';
shopNavHtml += '<div :style="{\'color\' : data.color, \'border-color\':data.color}">包邮</div>';
shopNavHtml += '<div>假一赔十</div>';
shopNavHtml += '<div>破损包赔</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';


shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="shop-content-item" v-show="type == 2 || type == 3">';
shopNavHtml += '<div class="goods-sort" v-show="type == 2">';
shopNavHtml += '<div>综合</div>';
shopNavHtml += '<div>销量</div>';
shopNavHtml += '<div>最新</div>';
shopNavHtml += '<div class="price">';
shopNavHtml += '价格';
shopNavHtml += '<div class="icon-wrap">';
shopNavHtml += '<i class="iconxiangshang1 iconfont"></i>';
shopNavHtml += '<i class="iconxiangxia1 iconfont"></i>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="goods-list double-column">';

shopNavHtml += '<div class="goods-item margin-bottom">';
	shopNavHtml += '<div class="goods-img">';
	shopNavHtml += '<img src="'+STATICEXT_IMG+'/crack_figure.png">';
	shopNavHtml += '</div>';
shopNavHtml += '<div class="info-wrap">';
	shopNavHtml += '<div class="name-wrap">';
		shopNavHtml += '<div class="goods-name">商品名称</div>';
	shopNavHtml += '</div>';
	shopNavHtml += '<div class="pro-info">';
		shopNavHtml += '<div class="delete-price" :style="{\'color\' : data.color}"><span class="unit">¥</span>15.99</div>';
		shopNavHtml += '<div class="sale">已售1005件</div>';
	shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';

shopNavHtml += '<div class="goods-item margin-bottom">';
shopNavHtml += '<div class="goods-img">';
shopNavHtml += '<img src="'+STATICEXT_IMG+'/crack_figure.png">';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-wrap">';
shopNavHtml += '<div class="name-wrap">';
shopNavHtml += '<div class="goods-name">商品名称</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="pro-info">';
shopNavHtml += '<div class="delete-price" :style="{\'color\' : data.color}"><span class="unit">¥</span>15.99</div>';
shopNavHtml += '<div class="sale">已售1005件</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';

shopNavHtml += '<div class="goods-item margin-bottom">';
shopNavHtml += '<div class="goods-img">';
shopNavHtml += '<img src="'+STATICEXT_IMG+'/crack_figure.png">';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-wrap">';
shopNavHtml += '<div class="name-wrap">';
shopNavHtml += '<div class="goods-name">商品名称</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="pro-info">';
shopNavHtml += '<div class="delete-price" :style="{\'color\' : data.color}"><span class="unit">¥</span>15.99</div>';
shopNavHtml += '<div class="sale">已售1005件</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';

shopNavHtml += '<div class="goods-item margin-bottom">';
shopNavHtml += '<div class="goods-img">';
shopNavHtml += '<img src="'+STATICEXT_IMG+'/crack_figure.png">';
shopNavHtml += '</div>';
shopNavHtml += '<div class="info-wrap">';
shopNavHtml += '<div class="name-wrap">';
shopNavHtml += '<div class="goods-name">商品名称</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="pro-info">';
shopNavHtml += '<div class="delete-price" :style="{\'color\' : data.color}"><span class="unit">¥</span>15.99</div>';
shopNavHtml += '<div class="sale">已售1005件</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';


shopNavHtml += '</div>';

shopNavHtml += '</div>';

shopNavHtml += '<div  class="coupon-list" v-show="type == 4">';

shopNavHtml += '<div class="title">本地榜单</div>';
shopNavHtml += '<div class="coupon-item" >';
shopNavHtml += '<div class="item-base">';
shopNavHtml += '<span>￥</span>10';
shopNavHtml += '</div>';
shopNavHtml += '<div class="line"></div>';
shopNavHtml += '<div class="coupon-content">';
shopNavHtml += '<div class="item-info">';
shopNavHtml += '<div class="name">店铺券</div>';
shopNavHtml += '<div class="desc">';
shopNavHtml += '满100元可用';
shopNavHtml += '</div>';
shopNavHtml += '<div class="time">有效期：2025-11-20 12:00:00</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="item-btn">立即领取</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';

shopNavHtml += '<div class="coupon-item" >';
shopNavHtml += '<div class="item-base">';
shopNavHtml += '<span>￥</span>20';
shopNavHtml += '</div>';
shopNavHtml += '<div class="line"></div>';
shopNavHtml += '<div class="coupon-content">';
shopNavHtml += '<div class="item-info">';
shopNavHtml += '<div class="name">店铺券</div>';
shopNavHtml += '<div class="desc">';
shopNavHtml += '满150元可用';
shopNavHtml += '</div>';
shopNavHtml += '<div class="time">有效期：2025-11-20 12:00:00</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="item-btn">立即领取</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';

shopNavHtml += '<div class="coupon-item" >';
shopNavHtml += '<div class="item-base">';
shopNavHtml += '9<span>折</span>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="line"></div>';
shopNavHtml += '<div class="coupon-content">';
shopNavHtml += '<div class="item-info">';
shopNavHtml += '<div class="name">店铺券</div>';
shopNavHtml += '<div class="desc">';
shopNavHtml += '满100元可用';
shopNavHtml += '</div>';
shopNavHtml += '<div class="time">有效期：2025-11-20 12:00:00</div>';
shopNavHtml += '</div>';
shopNavHtml += '<div class="item-btn">立即领取</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';
shopNavHtml += '</div>';


shopNavHtml += '</div>';
shopNavHtml += '</div>';

Vue.component("shop-nav",{
	template : shopNavHtml,
	data : function(){
		return {
			data : this.$parent.data,
			typeList : this.$parent.data.list,
			type : 1,
		};
	},

	created : function(){
		this.$parent.data.verify = this.verify;//加载验证方法
		// this.$parent.data.list = this.list;
	},

	watch : {

	},

	methods : {
		switchType: function (type) {
			this.type = type;
		}

	},
});

/**
 * [编辑]·组件
 */
var shopNavListHtml = '<div class="shopcontent-nav-list">';



shopNavListHtml += '<div class="template-edit-wrap">';
shopNavListHtml += '<ul>';
shopNavListHtml += '<p class="hint">建议上传尺寸相同的图片(60px * 60px)</p>';
shopNavListHtml += '<li v-for="(item,index) in list" v-bind:key="index" >';


shopNavListHtml += '<div class="content-block" >';
shopNavListHtml += '<div class="layui-form-item">';
shopNavListHtml += '<label class="layui-form-label sm">标题</label>';
shopNavListHtml += '<div class="layui-input-block">';
shopNavListHtml += '<input type="text" name=\'title\' v-model="item.text" class="layui-input" />';
shopNavListHtml += '</div>';
shopNavListHtml += '</div>';

shopNavListHtml += '</div>';

shopNavListHtml += '<div class="upload-img-box">';
shopNavListHtml += '<img-upload v-bind:data="{ data : item, field: \'path\' }" ></img-upload>';
shopNavListHtml += '<img-upload v-bind:data="{ data : item, field: \'select_path\' }" ></img-upload>';
shopNavListHtml += '</div>';

shopNavListHtml += '<div class="error-msg"></div>';
shopNavListHtml += '</li>';

shopNavListHtml += '</ul>';
shopNavListHtml += '</div>';

// shopNavListHtml += '<div class="template-edit-wrap">';
// shopNavListHtml += '<color v-bind:data="{ field : \'backgroundColor\', label : \'背景颜色\' }"></color>';
//
// shopNavListHtml += '</div>';

shopNavListHtml += '</div>';

Vue.component("nav-list",{
	template : shopNavListHtml,
	data : function(){
		return {
			data : this.$parent.data,
			showAddItem : true,
			list : this.$parent.data.list,
			list : this.$parent.data.list,
		};
	},

	created : function(){
		if(!this.$parent.data.verify) this.$parent.data.verify = [];
		this.$parent.data.verify.push(this.verify);//加载验证方法
	},

	watch : {

	},
	methods : {
		//改变图片比例
		changeImageScale : function(event){
			var v = event.target.value;
			if(v != ""){
				if(v > 0 && v <= 100){
					this.imageScale = v;
					this.$parent.data.imageScale =  this.imageScale;//更新父级对象
				}else{
					layer.msg("请输入合法数字1~100");
				}
			}else{
				layer.msg("请输入合法数字1~100");
			}
		},

		verify:function () {

			var res = { code : true, message : "" };
			var _self = this;

			$(".draggable-element[data-index='" + this.data.index + "'] .shopcontent-nav-list .template-edit-wrap>ul>li").each(function(index){
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

	},


});