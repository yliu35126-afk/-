import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex)

import Http from '../common/js/http.js'

const store = new Vuex.Store({
	state: {
		Development: 1,
		addonIsExit: {},
		token: null,
	},
	mutations: {
		setAddonIsexit(state, addonIsExit) {
			state.addonIsExit = Object.assign(state.addonIsExit, addonIsExit);
		},
		setToken(state, value) {
			state.token = value;
		}
	},
	actions: {
		// 获取插件是否安装
		async getAddonIsexit() {
			if (uni.getStorageSync('memberAddonIsExit')) {
				this.commit('setAddonIsexit', uni.getStorageSync('memberAddonIsExit'))
			}
			var res = await Http.sendRequest({
				url: '/shopapi/addon/addonisexit',
				async: false
			})
			let data = res;
			if (res.code == 0) {
				uni.setStorageSync('memberAddonIsExit', res.data);
				this.commit('setAddonIsexit', res.data)
			}
		},
		// 获取店铺信息
		getShopInfo() {
			Http.sendRequest({
				url: '/shopapi/shop/shopInfo',
				success: res => {
					let data = res.data;
					if (res.code == 0 && data) {
						uni.setStorageSync('shop_info', JSON.stringify(data.shop_info));
						uni.setStorageSync('user_info', JSON.stringify(data.user_info));
					}
				}
			});
		},
		// 获取默认图
		getDefaultImg() {
			Http.sendRequest({
				url: '/shopapi/config/defaultimg',
				success: res => {
					let data = res.data;
					if (res.code == 0 && data) {
						uni.setStorageSync('default_img', JSON.stringify(data));
					}
				}
			});
		},
	}
})

export default store
