<template>
	<div class="goods-info">
		<div class="goods-box">
			<div class="goods-img">
				<img :src="orderInfo.order_goods[0].sku_image ? $util.img(orderInfo.order_goods[0].sku_image) :  $util.img(defaultGoodsImage)"
				 alt />
			</div>
			<div class="goods-desc">
				<div class="text-hidden-two-row">{{orderInfo.order_name}}</div>
				<div>订单状态：{{orderInfo.order_status_name}}</div>
				<div class="flex-box"> <span class="ns-text-color">¥{{orderInfo.order_money}}</span> <span>{{orderInfo.delivery_type_name}}</span>
				</div>
			</div>
		</div>
		<!-- v-if="canSend" -->
		<div class="more text-center">
			<div class="order-num">订单号：454564645665</div>
			<div @click="jumo_order()" class="see-order">
				查看订单<i class="el-icon-arrow-right"></i>
			</div>
			<!-- <el-breadcrumb-item :to="{ path: '/member/order_detail?order_id=' + orderId }">订单详情</el-breadcrumb-item> -->

		</div>
	</div>
</template>
<script>
	import {
		apiOrderDetail
	} from "@/api/order/order"
	import {
		mapGetters
	} from "vuex"
	export default {
		name: "orderItem",
		props: {
			orderId: 0,
			canSend: {
				type: Boolean,
				default: false
			}
		},
		data() {
			return {
				orderInfo: {
					order_status_name: '',
					delivery_type_name: '',
					order_money: '',
					order_goods: [{
						sku_image: ''
					}]
				}
			}
		},
		computed: {
			...mapGetters(["defaultGoodsImage"])
		},
		created() {
			if (!this.orderId) return;
			this.getOrderInfo()
		},
		methods: {
			sendMessage() {
				this.$emit("sendMessage")
			},
			jumo_order() {
				this.$router.pushToTab('/member/order_detail?order_id=' + this.orderId)
			},
			getOrderInfo() {
				apiOrderDetail({
					order_id: this.orderId
				}).then((res) => {
					if (res.code >= 0) {
						console.log(res);
						this.orderInfo = res.data
					}
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.goods-info {
		padding: 0 10px;
		border: 1px solid #eee;
		box-sizing: border-box;
		border-radius: 10px;
		background-color: #eee;
		width: 350px;

		.goods-box {
			display: flex;
			border-bottom: 1px solid #eee;
			margin: 10px 0;
			padding: 10px;
			border-radius: 10px;
			box-sizing: border-box;
			background-color: white;

			.goods-img {
				overflow: hidden;
				width: 80px;
				border-radius: 4px;
				height: 80px;
				margin-right: 10px;
			}

			.goods-desc {
				width: 250px;
				display: flex;
				flex-direction: column;
				justify-content: space-between;

				.text-hidden-two-row {
					color: #000000;
					font-size: 15px;
				}

				.flex-box {
					display: flex;
					align-items: center;
					justify-content: space-between;
				}

				.price {
					color: #999999;
				}
			}
		}

		.more {
			margin: 10px 0 5px;
			cursor: pointer;
			display: flex;
			justify-content: space-between;

			.order-num {
				color: #909399;
			}

			.see-order {
				align-items: center;
				color: $base-color;
			}
		}
	}
</style>
