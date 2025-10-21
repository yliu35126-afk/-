(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/ns-copyright/ns-copyright"],{"0415":function(t,n,o){"use strict";o.r(n);var i=o("396e"),e=o("8021");for(var u in e)["default"].indexOf(u)<0&&function(t){o.d(n,t,(function(){return e[t]}))}(u);o("8bb9");var c=o("f0c5"),a=Object(c["a"])(e["default"],i["b"],i["c"],!1,null,null,null,!1,i["a"],void 0);n["default"]=a.exports},"396e":function(t,n,o){"use strict";o.d(n,"b",(function(){return i})),o.d(n,"c",(function(){return e})),o.d(n,"a",(function(){}));var i=function(){var t=this.$createElement,n=(this._self._c,this.bottom_info&&this.bottom_info.logo?this.$util.img(this.bottom_info.logo):null),o=this.bottom_info?null:this.$util.img("upload/uniapp/logo_copy.png");this.$mp.data=Object.assign({},{$root:{g0:n,g1:o}})},e=[]},8021:function(t,n,o){"use strict";o.r(n);var i=o("e182"),e=o.n(i);for(var u in i)["default"].indexOf(u)<0&&function(t){o.d(n,t,(function(){return i[t]}))}(u);n["default"]=e.a},"8bb9":function(t,n,o){"use strict";var i=o("e7f8a"),e=o.n(i);e.a},e182:function(t,n,o){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var i={data:function(){return{bottom_info:{}}},created:function(){this.getAdvList()},methods:{getAdvList:function(){var t=this;this.$api.sendRequest({url:"/api/config/copyright",success:function(n){0==n.code&&n.data&&(t.bottom_info=n.data)}})},link:function(t){t&&this.$util.redirectTo("/otherpages/webview/webview",{link:encodeURIComponent(t)})}}};n.default=i},e7f8a:function(t,n,o){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/ns-copyright/ns-copyright-create-component',
    {
        'components/ns-copyright/ns-copyright-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("0415"))
        })
    },
    [['components/ns-copyright/ns-copyright-create-component']]
]);
