<template>
	<div class="header">
		<ns-header-top />
		<ns-header-mid />

		<div class="nav">
			<div class="shop-sort" @mouseover="shopHover" @mouseleave="shopLeave">
				<router-link to="/category">所有商品分类</router-link>
				<i class="iconfont iconweibiaoti35"></i>
			</div>
			<div class="shop-list-content" :class="forceExpand || isShopHover || isIndex ? 'shop-list-active' : 'shadow'">
				<div class="shop-list" 
				v-if="categoryConfig.category ==1"
				 @mouseover="shopHover" @mouseleave="shopLeave">
					<div class="list-item"
					v-for="(item, index) in goodsCategoryTree" :key="index" @mouseover="selectedCategory = item.category_id" @mouseleave="selectedCategory = -1">
						<router-link :to="{ path: '/list', query: { category_id: item.category_id, level: item.level } }" target="_blank"
						>
						<div>
							<img class="category-img"
								v-if="categoryConfig.img == 1"
								:src="$util.img(item.image)" />
								<p class="category-name">{{item.category_name }}</p>
						</div>
						<i class="el-icon-arrow-right" aria-hidden="true"></i>
						</router-link>
					</div>
				</div>
				
				
				<!-- 分类类型2 -->
				<div class="shop-list" :class="forceExpand || isShopHover || isIndex ? 'shop-list-active' : 'shadow'"
				v-else-if="categoryConfig.category ==2"
				 @mouseover="shopHover" @mouseleave="shopLeave">
					<div class="list-item" 
					v-for="(item, index) in goodsCategoryTree" :key="index" @mouseover="selectedCategory = item.category_id" @mouseleave="selectedCategory = -1">
						<router-link :to="{ path: '/list', query: { category_id: item.category_id, level: item.level } }" target="_blank"
						>
						<div>
							<img class="category-img"
								v-if="categoryConfig.img == 1"
								:src="$util.img(item.image)" />
								<p class="category-name">{{ item.category_name }}</p>
								
						</div>
						<i class="el-icon-arrow-right" aria-hidden="true"></i>
						</router-link>
						<div class="cate-part" v-if="item.child_list" :class="{ show: selectedCategory == item.category_id }">
							<div class="cate-part-col1">
								<div class="cate-detail">
									<div class="cate-detail-item" >
										<div class="cate-detail-con">
											<router-link
												v-for="(second_item, second_index) in item.child_list" :key="second_index"
												target="_blank"
												:to="{ path: '/list', query: { category_id: second_item.category_id, level: second_item.level } }"
											>
												<img class="cate-detail-img"
													v-if="categoryConfig.img == 1"
													:src="$util.img(second_item.image)" />
												<p class="category-name">{{second_item.category_name }}</p>
											</router-link>
										</div>
										
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				
				<!-- 分类类型3 -->
				<div class="shop-list" :class="forceExpand || isShopHover || isIndex ? 'shop-list-active' : 'shadow'"
				 v-else-if="categoryConfig.category ==3"
				 @mouseover="shopHover" @mouseleave="shopLeave">
					<div class="list-item" v-for="(item, index) in goodsCategoryTree" :key="index" @mouseover="selectedCategory = item.category_id" @mouseleave="selectedCategory = -1"
					 >
						<router-link :to="{ path: '/list', query: { category_id: item.category_id, level: item.level } }" target="_blank"
						>
							<div class="list-item-left">
								<img class="category-img"
									v-if="categoryConfig.img == 1"
									:src="$util.img(item.image)" />
								<p class="category-name">{{item.category_name }}</p>
							</div>
							<!-- <i class="el-icon-arrow-right" aria-hidden="true" v-if="item.child_list"></i>  -->
						</router-link>
						<div class="item-itm " :class="{'item-itm-img':categoryConfig.img == 1}">
							<router-link v-for="(second_item, second_index) in item.child_list" :key="second_index" :to="{ path: '/list', query: { category_id: second_item.category_id, level: second_item.level } }" target="_blank"
							 v-show="second_index < 3"
							 style="display:inline-block;">
								{{ second_item.short_name?second_item.short_name : second_item.category_name }}
							</router-link>
						</div>
						<div class="cate-part" v-if="item.child_list" :class="{ show: selectedCategory == item.category_id }">
							<div class="cate-part-col1">
								<div class="cate-detail">
									<dl class="cate-detail-item" v-for="(second_item, second_index) in item.child_list" :key="second_index"
									>
										<dt class="cate-detail-tit">
											<router-link target="_blank" :to="{ path: '/list', query: { category_id: second_item.category_id, level: second_item.level } }">
												{{ second_item.category_name }}
											</router-link>
										</dt>
										<dd class="cate-detail-con" v-if="second_item.child_list">
											<router-link 
												v-for="(third_item, third_index) in second_item.child_list"
												:key="third_index"
												target="_blank"
												:to="{ path: '/list', query: { category_id: third_item.category_id, level: third_item.level } }"
											>
											<img class="cate-detail-img"
												v-if="categoryConfig.img == 1"
												:src="$util.img(third_item.image)" />
												<p class="category-name">{{third_item.category_name }}</p>
											</router-link>
										</dd>
									</dl>
								</div>
							</div>
						</div>
					</div>
				</div>
				
			</div>
			
			
			
			
			<nav>
				<ul>
					<li
						v-for="(nav_item, nav_index) in navList"
						:key="nav_index"
						:class="nav_item.url == navSelect ? 'router-link-active' : ''"
						@click="navUrl(nav_item.url, nav_item.is_blank)"
					>
						<span>{{ nav_item.nav_title }}</span>
					</li>
				</ul>
			</nav>
		</div>
	</div>
</template>

<script>
import NsHeaderTop from './NsHeaderTop';
import NsHeaderMid from './NsHeaderMid';
import { tree , categoryConfig} from '@/api/goods/goodscategory';
import { navList } from '@/api/website';
export default {
	props: {
		forceExpand: {
			type: Boolean,
			default: false
		}
	},
	data() {
		return {
			isShopHover: false,
			isIndex: false,
			thisRoute: '',
			goodsCategoryTree: [],
			categoryConfig:{},
			selectedCategory: -1,
			navList: [],
			navSelect: '',
			isHide:false
		};
	},
	components: {
		NsHeaderTop,
		NsHeaderMid
	},
	computed: {},
	beforeCreate() {},
	created() {
		this.$store.dispatch('cart/cart_count');
		this.getCategoryConfig();
		// this.getTree();
		this.nav();
		if (this.$route.path == '/' || this.$route.path == '/index') this.isIndex = true;
	},
	mounted() {},
	watch: {
		$route: function(curr) {
			this.initNav(curr.path);
			if (curr.path == '/' || curr.path == '/index') this.isIndex = true;
			else this.isIndex = false;

			if (curr.path == '/list') {
				this.navSelect = '';
			}
		}
	},
	methods: {
		// 获取配置
		getCategoryConfig(){
			categoryConfig({
				
			})
			.then(res => {
				if (res.code == 0 && res.data) {
					this.categoryConfig = res.data;
					this.getTree(res.data);
				}
			})
			.catch(err => {
				this.$message.error(err.message);
			});
		},
		getTree(categoryConfig) {
			tree({
				level: 3,
				template: 2
			})
				.then(res => {
					if (res.code == 0 && res.data) {
						this.goodsCategoryTree = res.data || [];
						for(let i =0;i<this.goodsCategoryTree.length;i++) {
							if(this.goodsCategoryTree[i].child_list > 3) {
								this.isHide = true
							}
						}
					}
				})
				.catch(err => {
					this.$message.error(err.message);
				});
		},
		nav() {
			navList({})
				.then(res => {
					if (res.code == 0 && res.data) {
						this.navList = res.data.list;
						for (let i in this.navList) {
							this.navList[i]['url'] = JSON.parse(this.navList[i]['nav_url']).url;
						}
						this.initNav(this.$route.path);
					}
				})
				.catch(err => {
					this.$message.error(err.message);
				});
		},
		navUrl(url, target) {
			if (!url) return;
			if (url.indexOf('http') == -1) {
				if (target) this.$router.pushToTab({ path: url });
				else this.$router.push({ path: url });
			} else {
				if (target) window.open(url);
				else window.location.href = url;
			}
		},
		initNav(path) {
			for (let i in this.navList) {
				if (this.navList[i]['url'] == path) {
					this.navSelect = path;
					continue;
				}
			}
		},
		
		//鼠标移入显示商品分类
		shopHover() {
			this.isShopHover = true;
		},
		//鼠标移出商品分类隐藏
		shopLeave() {
			this.isShopHover = false;
		}
	}
};
</script>

<style scoped lang="scss">
.header {
	width: 100%;
	height: 180px;
	background-color: #fff;

	.shadow {
		box-shadow: -1px 3px 12px -1px rgba(0, 0, 0, 0.3);
	}
	.border {
		border: 1px solid #f5f5f5;
	}

	.nav {
		width: 1210px;
		height: 41px;
		margin: 0 auto;
		position: relative;
		.shop-sort {
			width: 210px;
			height: 41px;
			color: #fff;
			background-color: $base-color;
			float: left;
			padding: 10px 10px 11px 10px;
			box-sizing: border-box;
			a {
				font-size: 14px;
				line-height: 20px;
				float: left;
				color: #fff;
			}
			i {
				font-size: 14px;
				float: right;
			}
		}
		.shop-list-content{
			width: 210px;
			height: 500px;
			position: absolute;
			left: 0;
			top: 41px;
			background-color: #333;
			display: none;
			padding: 0;
			box-sizing: border-box;
			font-size: $ns-font-size-base;
			z-index: 10;
			color:  #FFFFFF;
			&::-webkit-scrollbar{
				display: none;
			}
			// overflow: hidden;
			.shop-list {
				width: 210px;
				height: 500px;
				overflow-y: auto;
				overflow-x: hidden;
				// box-shadow: -1px 3px 12px -1px rgba(0, 0, 0, 0.3);
				&::-webkit-scrollbar{
					display: none;
				}
				a:hover {
					color: $base-color;
				}
				.list-item {
					padding-left: 12px;
					box-sizing: border-box;
					// min-height:50px;
					// height: 45px;
					// line-height: 50px;
					padding:13px;
					a {
						display: flex;
						justify-content: space-between;
						align-items: center;
						white-space: nowrap;
						overflow: hidden;
						text-overflow: ellipsis;
						// width: 95%;
						// display: block;
						color:  #FFFFFF;
						>div{
							display: flex;
							align-items: center;
						}
					}
					&:hover {
						background-color: #000;
						-webkit-transition: 0.2s ease-in-out;
						-moz-transition: -webkit-transform 0.2s ease-in-out;
						-moz-transition: 0.2s ease-in-out;
						transition: 0.2s ease-in-out;
						a:hover {
								color: #FD274A;
						}
					}
					span:hover {
						color: #FD274A;
					}
					.category-img{
						width:17px;
						height: 17px;
						margin-right: 10px;
					}
					.category-name{
						overflow:hidden;
						text-overflow:ellipsis;
						white-space:nowrap;
					}
					.item-itm {
						font-size: 14px;
						line-height: 15px; 
						height: 28px;
						overflow: hidden;
						
						a{
							margin-top: 5px;
							margin-right: 10px;
							color:#BFBFBF;
							&:hover{
								color: #FD274A !important;
							}
						}
						
						&.item-itm-img{
							margin-left: 27px;
						}
					}
					.cate-part {
						display: none;
						position: absolute;
						left: 210px;
						top: 0;
						z-index: auto;
						// width: 998px;
						width: 760px;
						height: 498px;
						overflow-y: auto;
						border: 1px solid #f7f7f7;
						background-color: #fff;
						-webkit-box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
						-moz-box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
						box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
						-webkit-transition: top 0.25s ease;
						-o-transition: top 0.25s ease;
						-moz-transition: top 0.25s ease;
						transition: top 0.25s ease;
						&.show {
							display: block;
						}
						&::-webkit-scrollbar{
							display: none;
						}
					}
					.cate-part-col1 {
						float: left;
						width: 100%;
						.cate-detail-item {
							position: relative;
							min-height: 36px;
							padding-left: 20px;
							&:last-child{
								margin-bottom: 30px;
							}
							.cate-detail-tit {
								margin-top:30px;
								margin-right: 20px;
								white-space: nowrap;
								text-overflow: ellipsis;
								font-weight: 700;
								a {
									display: block;
									color: #333333;
								}
								a:hover{
									color: #FD274A;
								}
								
							}
							.cate-detail-con {
								overflow: hidden;
								padding: 6px 0 6px 17px;
								display: flex;
								flex-wrap: wrap;
								// border-top: 1px dashed #eee;
								a {
									justify-content: start;
									font-size: 12px;
									height:30px;
								    /* float: left; */
								    margin: 4px 40px 4px 0;
								    padding: 0 10px;
								    white-space: nowrap;
								    line-height: 30px;
								    color: #666;
									width: calc((100% - 120px) / 4);
									display: flex;
									box-sizing: border-box;
									margin-top: 20px;
									.cate-detail-img{
										width:30px;
										height: 30px;
										margin-right: 10px;
									}
									&:nth-child(4n+4){
										margin-right: 0;
									}
								}
								a:hover{
									color: #FD274A;
								}
							}
							&:first-child .cate-detail-con {
								border-top: none;
							}
						}
					}
			
					// .sub-class-right {
					// 	display: block;
					// 	width: 240px;
					// 	height: 439px;
					// 	float: right;
					// 	border-left: solid 1px #e6e6e6;
					// 	overflow: hidden;
					// 	.adv-promotions {
					// 		display: block;
					// 		height: 441px;
					// 		margin: -1px 0;
					// 		a {
					// 			background: #fff;
					// 			display: block;
					// 			width: 240px;
					// 			height: 146px;
					// 			border-top: solid 1px #e6e6e6;
					// 			img {
					// 				background: #d3d3d3;
					// 				width: 240px;
					// 				height: 146px;
					// 			}
					// 		}
					// 	}
					// }
				}
			}
		}
		
		.shop-list-active {
			display: block;
		}
		nav {
			width: 934px;
			height: 36px;
			float: left;
			font-size: 14px;
			ul {
				margin: 0;
				padding: 0;
				width: 100%;
				height: 100%;
				li {
					cursor: pointer;
					list-style: none;
					float: left;
					padding: 8px 24px;
					a {
						&:hover {
							color: $base-color;
						}
					}
				}
				li:hover {
					color: $base-color;
					font-weight: bold;
				}
				.router-link-active {
					color: $base-color;
					font-weight: bold;
					border-bottom: 2px solid  #FD274A;
				}
			}
		}
	}
}
</style>
