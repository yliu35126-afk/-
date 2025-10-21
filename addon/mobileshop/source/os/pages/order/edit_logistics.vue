<template>
	<view>
		<view class="form-title">填写物流信息</view>
		<view class="item-wrap">
			<view class="form-wrap">
				<text class="label">收货地址</text>
				<text class="value">{{ order.full_address }} {{ order.address }}</text>
			</view>
			<view class="form-wrap delivery-way">
				<text class="label">发货方式</text>
				<button :type="data.delivery_type == 1 ? 'primary' : 'default'" size="mini" @click="data.delivery_type = 1">物流发货</button>
				<button :type="data.delivery_type == 0 ? 'primary' : 'default'" size="mini" @click="data.delivery_type = 0">无需物流</button>
			</view>
			<template v-if="data.delivery_type == 1">
				<view class="form-wrap more-wrap">
					<text class="label">物流公司</text>
					<picker class="selected" @change="bindPickerChange" :value="picker.index" :range="picker.arr">
						<view class="uni-input" :class="{ 'color-tip': !company_name }">{{ company_name ? company_name : '请选择物流公司' }}</view>
					</picker>
					<text class="iconfont iconright"></text>
				</view>
				<view class="form-wrap">
					<view class="label">快递单号</view>
					<input class="uni-input" v-model="data.delivery_no" placeholder="请输入快递单号" />
				</view>
			</template>
		</view>
		<view class="footer-wrap"><button type="primary" @click="save()">确定</button></view>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			order: {},
			repeatFlag: false,
			data: {
				order_id: 0,
				package_id: 0,
				delivery_type: 1,
				express_company_id: 0,
				delivery_no: ''
			},
			expressCompany: [],
			company_name: '',
			picker: {
				index: 0,
				arr: []
			},
			logistics: {}
		};
	},
	async onLoad(option) {
		this.logistics = uni.getStorageSync('editLogistics') ? JSON.parse(uni.getStorageSync('editLogistics')) : {};
		if (this.logistics.id) {
			this.data.order_id = this.logistics.order_id;
			this.data.package_id = this.logistics.id;
			this.data.delivery_type = this.logistics.delivery_type;
			this.data.delivery_no = this.logistics.delivery_no;
			this.data.express_company_id = this.logistics.express_company_id;
			this.company_name = this.logistics.express_company_name;
			this.getShopExpressCompanyList();
			await this.getOrderInfo();
		} else {
			this.$util.showToast({
				title: '订单信息不存在'
			});
			setTimeout(() => {
				uni.navigateBack({
					delta: 1
				});
			}, 1000);
		}
	},
	onShow() {},
	methods: {
		// 获取订单信息
		async getOrderInfo() {
			var res = await this.$api.sendRequest({
				url: '/shopapi/order/getOrderInfo',
				data: {
					order_id: this.data.order_id
				},
				async: false
			});

			if (res.code == 0) {
				this.order = res.data;
				if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
			} else {
				this.$util.showToast({
					title: res.message
				});
				setTimeout(() => {
					this.$util.redirectTo('/pages/order/list', {}, 'redirectTo');
				}, 1000);
			}
		},
		// 获取物流公司
		getShopExpressCompanyList() {
			this.$api.sendRequest({
				url: '/shopapi/express/expressCompany',
				success: res => {
					if (res.code == 0) {
						this.expressCompany = res.data;
						this.expressCompany.forEach((item, key) => {
							if (this.express_company_id == item.company_id) this.picker.index = key;
							this.picker.arr.push(item.company_name);
						});
					}
				}
			});
		},
		// 绑定物流公司切换事件
		bindPickerChange(e) {
			if (this.expressCompany.length == 0) return;
			this.picker.index = e.target.value;
			this.data.express_company_id = this.expressCompany[this.picker.index].company_id;
			this.company_name = this.expressCompany[this.picker.index].company_name;
		},
		verify() {
			var count = 0;

			if (this.data.delivery_type == 1) {
				if (this.data.express_company_id == 0) {
					this.$util.showToast({
						title: '请选择物流公司'
					});
					return false;
				}
				if (this.data.delivery_no.length == 0) {
					this.$util.showToast({
						title: '请输入快递单号'
					});
					return false;
				}
			}

			return true;
		},
		save() {
			if (!this.verify()) return;

			// 无需物流，清空物流公司id、快递单号
			if (this.data.delivery_type == 0) {
				this.data.express_company_id = 0;
				this.data.delivery_no = '';
			}

			if (this.repeatFlag) return;
			this.repeatFlag = true;

			this.$api.sendRequest({
				url: '/shopapi/order/editOrderDelivery',
				data: this.data,
				success: res => {
					if (res.code == 0) {
						uni.removeStorageSync('editLogistics');
						setTimeout(() => {
							uni.navigateBack({
								delta: 1
							});
						}, 1000);
					} else {
						this.repeatFlag = false;
					}
					this.$util.showToast({
						title: res.message
					});
				}
			});
		}
	}
};
</script>

<style lang="scss">
.form-title {
	margin: $margin-updown $margin-both;
	color: $color-tip;
}
.item-wrap {
	background: #fff;
	margin-top: $margin-updown;
	.form-wrap {
		display: flex;
		align-items: center;
		margin: 0 $margin-both;
		border-bottom: 1px solid $color-line;
		height: 100rpx;
		line-height: 100rpx;
		&:last-child {
			border-bottom: none;
		}
		.label {
			vertical-align: middle;
			margin-right: $margin-both;
		}
		&.delivery-way {
			.label {
				flex: 1;
			}
			button:last-child {
				margin-left: 20rpx !important;
			}
		}
		.value {
			vertical-align: middle;
			display: inline-block;
			flex: 1;
			text-align: right;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: pre;
		}
		input {
			vertical-align: middle;
			display: inline-block;
			flex: 1;
			text-align: right;
		}
		&.more-wrap {
			.selected {
				vertical-align: middle;
				display: inline-block;
				flex: 1;
				text-align: right;
				color: $color-tip;
				overflow: hidden;
				white-space: pre;
				text-overflow: ellipsis;
				&.have {
					color: $color-title;
				}
			}
			.iconfont {
				color: $color-tip;
				margin-left: 20rpx;
			}
		}
	}
}
.footer-wrap {
	width: 100%;
	padding: 40rpx 0;
}
</style>
