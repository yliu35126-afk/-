<template>
	<view>
		<view class="form-title">选择发货商品</view>
		<view class="goods-wrap">
			<view class="goods-item" v-for="(item, index) in orderGoodsList" :key="index" @click="change(index)">
				<view class="iconfont" :class="[item.checked ? 'iconyuan_checked color-base-text' : 'iconyuan_checkbox', item.disabled ? 'disabled' : '']"></view>
				<view class="goods-img"><image :src="$util.img(item.sku_image, { size: 'mid' })" mode="widthFix" @error="imgError(index)"></image></view>
				<view class="info-wrap">
					<view class="name-wrap">{{ item.sku_name }}</view>
					<view class="num">x{{ item.num }}</view>
					<view class="delivery-no" v-if="item.delivery_no">物流单号：{{ item.delivery_no }}</view>
					<view class="delivery-status-name color-base-text">
						<text>{{ item.delivery_status_name }}</text>
						<view class="refund" v-if="item.refund_status != 0" @click="goRefund(item.order_goods_id)">
							<text>{{ item.refund_status_name }}</text>
							<text class="color-base-text">(处理维权)</text>
						</view>
					</view>
				</view>
			</view>
		</view>
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
			orderGoodsList: [],
			repeatFlag: false,
			data: {
				order_id: 0,
				delivery_type: 1,
				express_company_id: 0,
				delivery_no: '',
				order_goods_ids: []
			},
			expressCompany: [],
			company_name: '',
			picker: {
				index: 0,
				arr: []
			}
		};
	},
	async onLoad(option) {
		this.data.order_id = option.order_id || 0;
		this.getShopExpressCompanyList();
		await this.getOrderInfo();
		this.getOrderGoodsList();
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
		},
		// 获取订单项
		getOrderGoodsList() {
			this.$api.sendRequest({
				url: '/shopapi/order/getOrderGoodsList',
				data: {
					order_id: this.data.order_id
				},
				success: res => {
					if (res.code == 0) {
						this.orderGoodsList = res.data;
						this.orderGoodsList.forEach(item => {
							item.checked = true;
							item.disabled = item.delivery_status != 0 || this.order.is_lock == 1;
						});
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				}
			});
		},
		// 获取物流公司
		getShopExpressCompanyList() {
			this.$api.sendRequest({
				url: '/shopapi/express/expressCompany',
				success: res => {
					if (res.code == 0) {
						this.expressCompany = res.data;
						this.expressCompany.forEach((item, key) => {
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
		change(index) {
			if (this.orderGoodsList[index].disabled) return;
			this.orderGoodsList[index].checked = !this.orderGoodsList[index].checked;
			this.$forceUpdate();
		},
		goRefund(order_goods_id) {
			this.$util.redirectTo('/pages/order/refund/detail', {
				order_goods_id
			});
		},
		verify() {
			var count = 0;

			this.orderGoodsList.forEach(item => {
				if (!item.disabled && item.checked) count++;
			});
			if (count == 0) {
				this.$util.showToast({
					title: '请选择发货商品'
				});
				return false;
			}

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

			this.data.order_goods_ids = [];
			this.orderGoodsList.forEach(item => {
				if (!item.disabled && item.checked) this.data.order_goods_ids.push(item.order_goods_id);
			});

			this.data.order_goods_ids = this.data.order_goods_ids.toString();

			if (this.repeatFlag) return;
			this.repeatFlag = true;

			this.$api.sendRequest({
				url: '/shopapi/order/delivery',
				data: this.data,
				success: res => {
					if (res.code == 0) {
						this.$util.showToast({
							title: '发货成功'
						});
						setTimeout(() => {
							uni.navigateBack({
								delta: 1
							});
						}, 1000);
					} else {
						this.$util.showToast({
							title: res.message
						});
						this.repeatFlag = false;
					}
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
.goods-item {
	background: #fff;
	margin-top: $margin-updown;
	display: flex;
	position: relative;
	flex-flow: row wrap;
	padding: 20rpx 30rpx;
	align-items: center;

	.iconfont {
		font-size: 40rpx;
		margin-right: 30rpx;
		color: $color-tip;
		&.disabled {
			color: $color-tip !important;
		}
	}

	.goods-img {
		width: 160rpx;
		height: 160rpx;
		overflow: hidden;
		border-radius: $border-radius;
		margin-right: 20rpx;

		image {
			width: 100%;
			height: 100%;
		}
	}

	.info-wrap {
		flex: 1;
		display: flex;
		flex-direction: column;
		.name-wrap {
			font-size: $font-size-base;
			line-height: 1.3;
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-line-clamp: 2;
			-webkit-box-orient: vertical;
			margin-bottom: 10rpx;
		}
		.num {
			margin-top: 4rpx;
			color: $color-tip;
			font-size: $font-size-activity-tag;
			line-height: 1;
		}
		.delivery-no {
			margin-bottom: 4rpx;
			display: inline-block;
			line-height: 1;
			font-size: $font-size-tag;
			flex: 1;
			text-align: right;
		}

		.delivery-status-name {
			text-align: right;
			font-weight: bold;
			.refund {
				text:first-child {
					font-weight: normal;
				}
			}
		}
	}
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
	bottom: 0;
	z-index: 10;
}
</style>
