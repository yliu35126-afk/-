(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/l-time/l-time"],{"34cc":function(t,e,n){"use strict";n.r(e);var a=n("cc24"),i=n.n(a);for(var u in a)["default"].indexOf(u)<0&&function(t){n.d(e,t,(function(){return a[t]}))}(u);e["default"]=i.a},"904d":function(t,e,n){"use strict";n.r(e);var a=n("b850"),i=n("34cc");for(var u in i)["default"].indexOf(u)<0&&function(t){n.d(e,t,(function(){return i[t]}))}(u);var c=n("f0c5"),r=Object(c["a"])(i["default"],a["b"],a["c"],!1,null,null,null,!1,a["a"],void 0);e["default"]=r.exports},b850:function(t,e,n){"use strict";n.d(e,"b",(function(){return a})),n.d(e,"c",(function(){return i})),n.d(e,"a",(function(){}));var a=function(){var t=this.$createElement;this._self._c},i=[]},cc24:function(t,e,n){"use strict";var a=n("4ea4");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i=a(n("4826")),u={name:"l-time",props:{text:{type:[String,Number,Date],default:""},maxDate:{type:Boolean,default:!1}},data:function(){return{textVal:this.text}},watch:{text:function(){this.textVal=this.text}},computed:{temp:function(){return this.getText()}},methods:{getText:function(){var t=this,e=i.default.getFormatTime(t.textVal,t.maxDate);return e&&(e.endsWith("刚刚")||e.endsWith("分钟前"))&&setTimeout((function(){var e=t.textVal;t.textVal="",t.textVal=e}),6e4),this.textVal?e:""},onClick:function(){this.$emit("on-tap",this.textVal)}}};e.default=u}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/l-time/l-time-create-component',
    {
        'components/l-time/l-time-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("904d"))
        })
    },
    [['components/l-time/l-time-create-component']]
]);
