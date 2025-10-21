import Vue from 'vue'
import App from './App'
import store from './store'
import Util from './common/js/util.js'
import Http from './common/js/http.js'
import Config from './common/js/config.js'

Vue.prototype.$store = store //挂在vue

Vue.config.productionTip = false

Vue.prototype.$util = Util;
Vue.prototype.$api = Http;

Vue.prototype.$config = Config;

App.mpType = 'app'

//常用组件
import loadingCover from '@/components/loading-cover/loading-cover.vue';
Vue.component('loading-cover', loadingCover);

import nsEmpty from '@/components/ns-empty/ns-empty.vue';
Vue.component('ns-empty', nsEmpty);

import MescrollUni from "@/components/mescroll/my-list-mescroll.vue";
Vue.component("mescroll-uni", MescrollUni); //上拉加载,下拉刷新组件

import MescrollBody from "@/components/mescroll/mescroll-body.vue"
Vue.component('mescroll-body', MescrollBody);

const app = new Vue({
	...App,
	store
})
app.$mount()
