(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["otherpages/components/ns-chat/ns-chat-receiveGoods"],{"07d4":function(t,e,n){},"574f":function(t,e,n){"use strict";n.d(e,"b",(function(){return o})),n.d(e,"c",(function(){return s})),n.d(e,"a",(function(){}));var o=function(){var t=this.$createElement,e=(this._self._c,this.$util.img(this.goodsINfo.goods_image));this.$mp.data=Object.assign({},{$root:{g0:e}})},s=[]},7123:function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var o={name:"ns-chat-receiveGoods",props:{skuId:{type:[Number,String]}},data:function(){return{goodsINfo:{}}},mounted:function(){this.getInfo()},methods:{getInfo:function(){var t=this;this.$api.sendRequest({url:"/api/goodssku/detail",data:{sku_id:this.skuId},success:function(e){console.log(e,"res"),e.code>=0&&(t.goodsINfo=e.data.goods_sku_detail)}})},go_shop:function(){this.$util.redirectTo("/pages/goods/detail/detail?sku_id="+this.skuId)}}};e.default=o},b82f:function(t,e,n){"use strict";var o=n("07d4"),s=n.n(o);s.a},c932:function(t,e,n){"use strict";n.r(e);var o=n("574f"),s=n("fae4");for(var i in s)["default"].indexOf(i)<0&&function(t){n.d(e,t,(function(){return s[t]}))}(i);n("b82f");var u=n("f0c5"),a=Object(u["a"])(s["default"],o["b"],o["c"],!1,null,null,null,!1,o["a"],void 0);e["default"]=a.exports},fae4:function(t,e,n){"use strict";n.r(e);var o=n("7123"),s=n.n(o);for(var i in o)["default"].indexOf(i)<0&&function(t){n.d(e,t,(function(){return o[t]}))}(i);e["default"]=s.a}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'otherpages/components/ns-chat/ns-chat-receiveGoods-create-component',
    {
        'otherpages/components/ns-chat/ns-chat-receiveGoods-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("c932"))
        })
    },
    [['otherpages/components/ns-chat/ns-chat-receiveGoods-create-component']]
]);
