<template>
	<aside class="main-sidebar clearfix">
		<div class="main-sidebar-body">
			<ul>

				<li @click="$router.push('/cart')">
					<div class="cart">
						<i class="el-icon-shopping-cart-2"></i>
						<span>购物车</span>
						<em v-show="cartCount">{{ cartCount }}</em>
					</div>
				</li>
				<li @click="$router.push('/member/order_list')">
					<el-tooltip class="item" effect="dark" content="我的订单" placement="left">
						<el-button><i class="el-icon-tickets"></i></el-button>
					</el-tooltip>
				</li>
				<li @click="$router.push('/member/footprint')">
					<el-tooltip class="item" effect="dark" content="我的足迹" placement="left">
						<el-button><i class="iconfont iconzuji"></i></el-button>
					</el-tooltip>
				</li>
				<li @click="$router.push('/member/collection')">
					<el-tooltip class="item" effect="dark" content="我的关注" placement="left">
						<el-button><i class="iconfont iconlike"></i></el-button>
					</el-tooltip>
				</li>
				<li @click="$router.push('/member/my_coupon')">
					<el-tooltip class="item" effect="dark" content="我的优惠券" placement="left">
						<el-button><i class="iconfont iconyouhuiquan"></i></el-button>
					</el-tooltip>
				</li>
				<!-- <li class="kefuTip">
					<div class="tip">
						<div class="tip_item">
							<div class="kefu_logo">
								<img src="../../assets/images/kefupng.png" />
							</div>
							<div class="text item" style="cursor:pointer" @click="showServiceFn()">客服</div>
						</div>
					</div>
					<el-button slot="reference">
						<i class="el-icon-phone-outline"></i>
					</el-button>
				</li> -->
			</ul>
			<a class="back-top" :class="{ showBtn: visible }" title="返回顶部" @click="toTop"><i
					class="el-icon-top"></i></a>
		</div>
		<div class="main-sidebar-right">
			<div id="mainSidebarHistoryProduct" class="history-product"></div>
		</div>
		<!--联系客服弹窗-->
		<servicerMessage ref="servicerMessage" class="kefu" :shop="{shop_id:0,logo:siteInfo.logo,shop_name:'平台客服'}">
		</servicerMessage>
	</aside>
</template>

<script>
	import {
		mapGetters
	} from "vuex";
	import {
		shopServiceOpen
	} from "@/api/website.js"
	import servicerMessage from "@/components/message/servicerMessage";
	export default {
		props: {},
		data() {
			return {
				visible: false,
				hackReset: false,
				serverType: 'disable',
				serverThird: ''
			}
		},
		components: {
			servicerMessage
		},
		computed: {
			...mapGetters(["cartCount", "siteInfo", 'member']),
			logined: function() {
				return this.member !== undefined && this.member !== "" && this.member !== {}
			}
		},
		created() {
			this.shopServiceOpen()
		},
		mounted() {
			window.addEventListener("scroll", this.handleScroll)
		},
		beforeDestroy() {
			window.removeEventListener("scroll", this.handleScroll)
		},
		watch: {},
		methods: {
			handleScroll() {
				this.visible = window.pageYOffset > 300
			},
			shopServiceOpen() {
				shopServiceOpen().then((res) => {
					if (res.code == 0) {
						if (res.data.type == 'third') {
							this.serverType = res.data.type
							this.serverThird = res.data.third
						} else if (res.data.type == 'system') {
							this.serverType = res.data.type
							this.serverThird = ''
						}
					}
				})
			},
			toTop() {
				let timer = setInterval(function() {
					let osTop = document.documentElement.scrollTop || document.body.scrollTop
					let ispeed = Math.floor(-osTop / 5)
					document.documentElement.scrollTop = document.body.scrollTop = osTop + ispeed
					this.isTop = true
					if (osTop === 0) {
						clearInterval(timer)
					}
				}, 20)
			},
			// 打开客服弹窗
			showServiceFn() {
				if (this.logined) {
					if (this.serverType == 'third') {
						window.open(this.serverThird, "_blank");
					} else if (this.serverType == 'system') {
						this.hackReset = true;
						this.$refs.servicerMessage.show()
					}
				} else {
					this.$message({
						message: "您还未登录",
						type: "warning"
					})
				}

			},
		},
	}
</script>

<style scoped lang="scss">
	.main-sidebar {
		width: 340px;
		height: 100%;
		position: fixed;
		top: 0;
		right: -300px;
		z-index: 400;

		.main-sidebar-body {
			width: 40px;
			height: 100%;
			float: left;
			background-color: #333333;

			ul {
				position: absolute;
				top: 50%;
				-webkit-transform: translateY(-50%);
				transform: translateY(-50%);

				li {
					position: relative;

					.cart {
						height: auto;
						line-height: 20px;
						padding: 11px 0;
						text-align: center;
						cursor: pointer;

						span {
							display: block;
							padding: 5px 9px;
							text-align: center;
						}

						em {
							min-width: 17px;
							height: 15px;
							line-height: 15px;
							display: inline-block;
							padding: 0 2px;
							color: #ffffff;
							font-size: 10px;
							font-style: normal;
							text-align: center;
							border-radius: 8px;
							background-color: $base-color;
						}

						&:hover em {
							color: $base-color;
							background-color: #fff;
						}
					}
				}
			}

			a,
			.cart,
			.el-button {
				width: 40px;
				height: 40px;
				line-height: 40px;
				display: block;
				margin-bottom: 10px;
				color: #ffffff;
				text-align: center;
				-webkit-transition: background-color 0.3s;
				transition: background-color 0.3s;
				padding: 0;
				border: none;
				background: transparent;

				&:hover {
					background-color: $base-color;
				}
			}

			.back-top {
				display: none;
				margin-bottom: 0;
				position: absolute;
				bottom: 0;
				border-top: 1px solid #888888;
			}

			.showBtn {
				display: inline;
				opacity: 1;
				cursor: pointer;
			}

			i {
				font-size: 16px;
			}
		}
	}

	.kefuTip .tip {
		display: none;
	}

	.kefuTip:hover .tip {
		display: block;
		position: absolute;
		right: 40px;
		top: 50%;
		transform: translate(0, -50%);

		.tip_item {
			border-top-left-radius: 118px;
			border-top-right-radius: 118px;
			margin-right: 13px;
			width: 100px;
			background: #FF4649;
			color: #fff;
			padding-bottom: 1px;

			&::after {
				content: "";
				position: absolute;
				right: 0px;
				top: 50%;
				transform: translate(-8px, -50%) rotate(45deg);
				height: 10px;
				width: 10px;
				background-color: #ff4649;
			}
		}

		.kefu_logo {
			width: 78px;

			margin: 0 auto 10px;
			border-radius: 50%;

			img {
				margin-top: 14px;
				background: linear-gradient(to top right, #e4e4e4, #FFF);
				border-radius: 50%;
				width: 100%;
				height: 78px
			}
		}

		.text {
			padding: 0 !important;
			background: #fff;
			margin: 0 10px 10px;
			color: #FF4649;
			border-radius: 3px;
			text-align: center;
			line-height: 30px;

		}
	}
	.cart{
		span {
			display: block;
			padding: 5px 9px;
			text-align: center;
		}
		
		em {
			min-width: 17px;
			height: 15px;
			line-height: 15px;
			display: inline-block;
			padding: 0 2px;
			color: #ffffff;
			font-size: 10px;
			font-style: normal;
			text-align: center;
			border-radius: 4px;
			background-color: $base-color;
		}
		
		&:hover em {
			color: $base-color;
			background-color: #fff;
		}
	}

	a,
	.cart,
	.el-button {
		width: 40px;
		height: 40px;
		line-height: 40px;
		display: block;
		margin-bottom: 10px;
		color: #ffffff;
		text-align: center;
		-webkit-transition: background-color 0.3s;
		transition: background-color 0.3s;
		padding: 0;
		border: none;
		background: transparent;

		&:hover {
			background-color: $base-color;
		}
	}

	.back-top {
		display: none;
		margin-bottom: 0;
		position: absolute;
		bottom: 0;
		border-top: 1px solid #888888;
	}

	.showBtn {
		display: inline;
		opacity: 1;
		cursor: pointer;
	}

	i {
		font-size: 16px;
	}
</style>
