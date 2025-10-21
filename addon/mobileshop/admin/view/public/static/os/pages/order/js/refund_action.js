export default {
	data() {
		return {
			orderGoodsId: 0,
			isIphoneX: false,
			detail: {},
			orderInfo: {},
			actionCallback: null,
			repeatFlag: false,
		};
	},
	onLoad(option) {
		this.orderGoodsId = option.order_goods_id || 0;
	},
	onShow() {
		this.getOrderDetail();
		this.isIphoneX = this.$util.uniappIsIPhoneX();
		this.actionCallback = () => {
			this.getOrderDetail();
		};
	},
	methods: {
		getOrderDetail() {
			this.$api.sendRequest({
				url: '/shopapi/orderrefund/detail',
				data: {
					order_goods_id: this.orderGoodsId
				},
				success: res => {
					if (res.code == 0) {
						var data = res.data;
						this.detail = data.detail;
						if (this.detail.refund_address == '') {
							var shopInfo = uni.getStorageSync('shop_info') ? JSON.parse(uni.getStorageSync('shop_info')) : {};
							this.detail.refund_address = '商家未设置联系地址';
							if (shopInfo.full_address || shopInfo.address) {
								this.detail.refund_address = shopInfo.full_address + ' ' + shopInfo.address;
							}
						}
						this.orderInfo = data.order_info;
						this.detail.sku_spec_format = this.detail.sku_spec_format ? JSON.parse(this.detail.sku_spec_format) : [];
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					} else {
						this.$util.showToast({
							title: res.message
						});
						setTimeout(() => {
							uni.navigateBack({
								delta: 1
							});
						}, 1000);
					}
				}
			});
		},
		cancel() {
			uni.navigateBack({
				delta: 1
			});
		},
		// 拒绝
		orderRefundRefuse(order_goods_id) {
			this.$util.redirectTo("/pages/order/refund/refuse", {
				order_goods_id
			});
		},
		// 同意
		orderRefundAgree(order_goods_id) {
			this.$util.redirectTo("/pages/order/refund/agree", {
				order_goods_id
			});
		},
		// 收货
		orderRefundTakeDelivery(order_goods_id) {
			this.$util.redirectTo("/pages/order/refund/take_delivery", {
				order_goods_id
			});
		},
		// 转账
		orderRefundTransfer(order_goods_id) {
			this.$util.redirectTo("/pages/order/refund/transfer", {
				order_goods_id
			});
		},
	}
}
