<template>
	<div class="goods-info">
		<div class="goods-box">
			<div class="goods-img">
				<img :src="goodsInfo.sku_image ? $util.img(goodsInfo.sku_image) :  $util.img(defaultGoodsImage)" alt />
			</div>
			<div class="goods-desc">
				<div class="text-hidden-two-row">{{goodsInfo.sku_name}}</div>
				<div class="text-hidden-two-row"> <span class="sale-num">库存：{{goodsInfo.stock}}</span> <span class="sale-num">销量：{{goodsInfo.sale_num}}</span>
				</div>
				<div class="price-box">
					<span class="sale-num price-num">¥{{goodsInfo.price}}</span>
					<span @click="jump_shop()">查看商品<i class="el-icon-arrow-right"></i></span>
				</div>
			</div>
		</div>
	</div>
</template>
<script>
	import {
		goodsSkuDetail
	} from "@/api/goods/goods"
	import {
		mapGetters
	} from "vuex"
	export default {
		name: "goods_item",
		props: {
			skuId: 0,
		},
		data() {
			return {
				goodsInfo: {}
			}
		},
		computed: {
			...mapGetters(["defaultGoodsImage"])
		},
		created() {
			if (!this.skuId) return;
			this.getGoodsInfo();
		},
		methods: {
			sendMessage() {
				this.$emit("sendMessage")
			},
			jump_shop() {
				this.$router.pushToTab('/sku-' + this.goodsInfo.sku_id);
			},
			getGoodsInfo() {
				console.log(this.skuId, '商品ID')
				goodsSkuDetail({
					sku_id: this.skuId
				}).then((res) => {
					console.log(res, 'res')
					if (res.code >= 0) {
						this.goodsInfo = res.data.goods_sku_detail
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
				font-size: 15px;

				.price {
					color: #999999;
				}

				.sale-num {
					font-size: 13px;
					margin-right: 6px;
				}

				.price-num {
					color: #F94460;
					font-size: 15px;
				}

				.price-box {
					display: flex;
					align-items: flex-end;
					justify-content: space-between;

					span:last-child {
						font-size: 14px;
						cursor: pointer;
						color: $base-color;
					}
				}
			}
		}
	}
</style>
