export default {
	data() {
		return {
			repeatFlag: false,
			// 操作后的回调
			actionCallback: null
		};
	},
	methods: {
		// 线下支付
		offlinePay(order_id) {
			if (this.repeatFlag) return;
			this.repeatFlag = true;

			uni.showLoading({
				title: '操作中...',
				mask: true
			});

			this.$api.sendRequest({
				url: '/shopapi/order/offlinePay',
				data: {
					order_id
				},
				success: res => {
					this.$util.showToast({
						title: res.message
					});
					if (res.code == 0) {
						if (this.actionCallback) this.actionCallback();
					}
					this.repeatFlag = false;
					uni.hideLoading();
				}
			});
		},
		// 订单关闭
		orderClose(order_id) {
			if (this.repeatFlag) return;
			this.repeatFlag = true;

			uni.showLoading({
				title: '操作中...',
				mask: true
			});

			this.$api.sendRequest({
				url: '/shopapi/order/close',
				data: {
					order_id
				},
				success: res => {
					this.$util.showToast({
						title: res.message
					});
					if (res.code == 0) {
						if (this.actionCallback) this.actionCallback();
					}
					this.repeatFlag = false;
					uni.hideLoading();
				}
			});
		},
		// 门店提货
		storeOrderTakedelivery(order_id) {
			if (this.repeatFlag) return;
			this.repeatFlag = true;

			uni.showLoading({
				title: '操作中...',
				mask: true
			});

			this.$api.sendRequest({
				url: '/shopapi/Storeorder/storeOrderTakeDelivery',
				data: {
					order_id
				},
				success: res => {
					this.$util.showToast({
						title: res.message
					});
					if (res.code == 0) {
						if (this.actionCallback) this.actionCallback();
					}
					this.repeatFlag = false;
					uni.hideLoading();
				}
			});
		},
		// 延长收货
		extendTakeDelivery(order_id) {
			uni.showModal({
				title: '操作提示',
				content: '确定要延长该订单的收货时间吗？\n单次延长收货可以延迟三天的自动收货时间',
				success: res => {
					if (res.confirm) {

						if (this.repeatFlag) return;
						this.repeatFlag = true;

						uni.showLoading({
							title: '操作中...',
							mask: true
						});

						this.$api.sendRequest({
							url: '/shopapi/order/extendtakedelivery',
							data: {
								order_id
							},
							success: res => {
								this.$util.showToast({
									title: res.message
								});
								if (res.code == 0) {
									if (this.actionCallback) this.actionCallback();
								}
								this.repeatFlag = false;
								uni.hideLoading();
							}
						});
					}
				}
			});
		},
		// 发货
		orderDelivery(order_id) {
			this.$util.redirectTo("/pages/order/delivery", {
				order_id
			});
		},
		// 外卖发货
		orderLocalDelivery(order_id) {
			this.$util.redirectTo("/pages/order/local_delivery", {
				order_id
			});
		},
		goRefund(order_goods_id) {
			this.$util.redirectTo('/pages/order/refund/detail', {
				order_goods_id
			});
		},
		// 调整价格
		orderAdjustMoney(order_id) {
			this.$util.redirectTo("/pages/order/adjust_price", {
				order_id
			});
		},
		// 修改地址
		orderAddressUpdate(order_id) {
			this.$util.redirectTo("/pages/order/address_update", {
				order_id
			});
		},
		// 查看订单日志
		goLog(order_id) {
			this.$util.redirectTo("/pages/order/log", {
				order_id
			});
		}
	}
}
