<template>
	<el-dialog class="service" :visible.sync="showService" width="48%" @close='closeDialog' :append-to-body="true">
		<div class="header-box ns-bg-color">
			<div class="header-logo">
				<img class="header-img" src="../../assets/images/kefupng.png" />
				<span class="serve-name">客服咨询</span>
			</div>
			<span>{{currShop.shop_name}}</span>
			<!-- <div class="outline" v-if="currShop.online==0">
				<div class="block"></div>
				离线
			</div>
			<div class="online" v-else>
				<div class="block"></div>
				在线
			</div> -->
		</div>
		<div class="service-box">
			<div class="service-box-left" v-if="member">
				<div class="people-box">
					<div @click="siteIdMap == item.shop_id?'':selectShop(item)" v-for="(item,index) in sessionList"
						:key="index" :class="{active:siteIdMap == item.shop_id}" class="people-item">
						<div class="shop-info">
							<div class="shop_img"
								:style="{backgroundImage:'url('+$util.img(item.avatar?item.avatar:item.logo)+')'}">
							</div>
							<div class="online-people" v-if="item.online==1"></div>
							<div class="outline-people" v-else></div>
							<div>
								<div class="text-hidden">{{item.shop_name}}
									<div style="font-size: 12px;" class="time">
										{{ $util.timeStampTurnTime(item.add_time) | Time }}</div>
								</div>
								<div class="last_content">
									<div class="msg" v-if="item.content_type==0" v-html="dealMessage(item.content)">
									</div>
									<div class="msg" v-if="item.content_type==1">您有一个商品消息</div>
									<div class="msg" v-if="item.content_type==2">您有一个订单消息</div>
									<div class="msg" v-if="item.content_type==3">您有一个图片消息</div>
									<div class="num" v-if="item.unread">{{item.unread}}</div>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
			<!-- 右半边 -->
			<div class="service-box-right">

				<div class="service-box-right-top test-1">
					<div class="content" id="content">
						<div class="text-center" v-if="contentEmpty">没有聊天记录了</div>
						<div v-else class="time text-center" style="cursor:pointer;" @click="getMessageList">点击加载</div>

						<div v-for="(item,index) in messageList" :key="index">
							<div>
								<div class="onlineStatus" v-if="item.contentType == 'online'">客服在线</div>
								<div class="onlineStatus" v-if="item.contentType == 'noline'">客服不在线</div>
							</div>

							<div v-if="item.isItMe" class="message my-message">
								<div>
									<div class="my-nickname">{{member.nickname}}</div>
									<goodsItem :skuId="item.sku_id" v-if="item.contentType == 'goods'"
										@sendMessage="sendMessage('goods',index)"></goodsItem>
									<orderItem :orderId="item.order_id" v-if="item.contentType == 'order'"
										@sendMessage="sendMessage('order',index)"></orderItem>
									<div v-if="item.contentType == 'string'" class="word-message"
										v-html="dealMessage(item.content)"></div>
									<div v-if="item.contentType == 'image'" class="word-message">
										<el-image :src="$util.img(item.content)"
											:preview-src-list="[$util.img(item.content)]"></el-image>
									</div>
								</div>
								<div class="headimg"
									:style="{backgroundImage:'url('+$util.img(member.headimg?member.headimg:defaultHeadImage)+')'}">
								</div>
							</div>

							<!-- 对方发起的消息 -->
							<div v-else-if="item.isItMe === false" class="message your-message">

								<div class="headimg"
									:style="{backgroundImage:'url('+$util.img(item.logo?item.logo:defaultShopImage)+')'}">
								</div>
								<div>
									<div class="my-nickname">{{item.nickname}} <span>{{item.time}}</span> </div>
									<div v-if="item.contentType == 'image'" class="word-message your-word-message">
										<el-image :src="$util.img(item.content)"
											:preview-src-list="[$util.img(item.content)]"></el-image>
									</div>
									<goodsItem :skuId="item.sku_id" v-if="item.contentType == 'goods'"
										@sendMessage="sendMessage('goods',index)"></goodsItem>
									<orderItem :orderId="item.order_id" v-if="item.contentType == 'order'"
										@sendMessage="sendMessage('order',index)"></orderItem>
									<div v-if="item.contentType == 'string'" class="word-message your-word-message"
										v-html="dealMessage(item.content)">
									</div>
								</div>
							</div>
						</div>
						<div style="height:40px;"></div>
					</div>
				</div>
				<div class="service-box-right-bottom">
					<div class="operation">
						<img class="emjoy" src="../../assets/images/emjoy.png" @click="showEmoji = !showEmoji;" />
						<el-upload :action="uploadActionUrl" :show-file-list="false" :on-success="handleAvatarSuccess"
							class="upload">
							<img class="emjoy" src="../../assets/images/upload.png" @click="showEmoji = false" />
						</el-upload>
					</div>

					<div class="emoji-box" v-if="showEmoji">
						<img @click="sendEmoji(index,item)" v-for="(item,index) in emjoyList" :key="index"
							class="text item" style="cursor:pointer" :src="$util.img(item)" />
					</div>
					<!-- <textarea @keydown.enter="sendMessage()" v-model="message"></textarea> -->
					<div class="input-panel" ref="msgInputContainer" @keydown.enter.exact="sendMessage()"
						contenteditable="true" spellcheck="false" @input="input($event)"></div>
					<div class="num-box">
						{{text_num}}/150
					</div>
					<el-button size="small" class="send-btn" @click="sendMessage()">发送</el-button>
				</div>
			</div>
		</div>
	</el-dialog>
</template>

<script>
	import Config from "@/utils/config"
	import goodsItem from "./goodsItem"
	import orderItem from "./orderItem"
	import {
		sendMessage,
		bindServicer,
		messageList,
		hasServicers,
		sessionList,
		closeMessage,
		readMessage,
		currStore
	} from "@/api/message"
	import {
		mapGetters
	} from "vuex"
	import emjoy from "@/utils/emjoy"
	export default {
		components: {
			goodsItem,
			orderItem
		},
		props: {
			skuId: {
				default: 0
			},
			orderId: {
				default: 0
			},
			shop: {
				type: Object,
				default: () => {
					return {
						shop_name: '',
						shop_id: '',
						logo: ''
					}
				}
			}
		},
		data() {
			return {
				isFirstInit: true,
				showEmoji: false, //是否显示表情
				emjoyList: emjoy.emjoyList,
				showService: false,
				search_text: "",
				websock: null,
				messageList: [], //聊天记录
				servicerId: 0, //客服id
				message: "", //发送内容
				image: "", //发送内容
				sendSwitch: true, //防止重复发送
				page: 1, //数据页码
				total: 0, //数据总数
				getSwitch: true, //防止重复拉去列表，节流
				contentEmpty: false, //是否有聊天记录
				sessionList: [], //店铺列表
				currShop: { //当前店铺
					shop_name: "",
					site_logo: ""
				},
				canSend: true,
				siteIdMap: 0,
				timeoutObj: null,
				uploadActionUrl: Config.baseUrl + '/api/upload/chatimg', //表情图标
				text_num: 0, //当前字数
				is_first: true, //第一次进入模拟点击
				serve_info: {
					name: '',
					logo: ''
				} //客服的个人信息
			}
		},
		filters: {
			Time: function(time) {
				var date2 = new Date();
				var year1 = parseInt(time.substr(0, 4));
				var month1 = parseInt(time.substr(5, 2));
				var day1 = parseInt(time.substr(8, 2));
				var year2 = date2.getFullYear();
				var month2 = date2.getMonth() + 1;
				var day2 = date2.getDate();
				var date1 = new Date(year1, month1 - 1, day1);
				var date2 = new Date(year2, month2 - 1, day2);
				var timeX = date1.getTime() - date2.getTime();
				if (Math.abs(timeX) < 24 * 60 * 60 * 1000) {
					return "今天" + time.substr(11, 5);
				} else if (timeX > 0 && timeX < 48 * 60 * 60 * 1000) {
					return "明天" + time.substr(11, 5);
				} else if (timeX < 0 && timeX > 0 - 48 * 60 * 60 * 1000) {
					return "昨天" + time.substr(11, 5);
				} else {
					return time.substr(5, 16);
					//					let last_time = time.substr(0, 10);
					//					last_time = last_time.replace(/-/g, '/');
					//					return last_time
				}
			}
		},


		created() {
			this.siteIdMap = this.shop.shop_id
		},
		watch: {
			showService(curr) {
				if (!curr && this.websock) {
					this.websock.close()
				}
			},
			groupId(val) {
				this.group_id = this.groupId;
			},
			servicerId(val) {
				// this.getSessionList();
				// this.getMessageList();
			}

		},
		mounted() {

		},
		computed: {
			...mapGetters(["token", "defaultHeadImage", "defaultShopImage", "defaultGoodsImage", "addonIsExit",
				"locationRegion",
				"member"
			])
		},
		// 如果之前有连接，切断链接
		beforeDestroy() {
			clearInterval(this.timeoutObj)
			closeMessage().then((res) => {
				if (res.code == 0 && this.websock) {
					this.websock.close()
				}
			})
		},
		methods: {
			// 处理消息中的数据
			dealMessage(val) {
				return emjoy.stringToEmjoy(val)
			},

			// 点击表情，添加表情
			sendEmoji(index, item) {
				this.showEmoji = false
				this.$refs.msgInputContainer.focus()
				const imgSrc = this.$util.img(item)
				const imgTag = `<img src="${imgSrc}" width="20" height="20">`;
				document.execCommand("insertHTML", false, imgTag);
			},

			// 展示弹窗
			show() {
				this.is_first = true
				this.siteIdMap = this.shop.shop_id;
				console.log('新版本')
				// console.log(this.shop, 'shop')
				// console.log(this.is_first, 'this.is_first')
				if (this.is_first) {
					this.currShop = this.shop;
				}

				this.showService = true
				// this.getSessionList();
				// this.getMessageList();
				this.initWebSocket();
			},


			// 初始化页数
			initData() {
				this.page = 1
				this.messageList.length = 0
				this.contentEmpty = false
			},


			// 选择联系人
			selectShop(item) {
				console.log('切换联系人')
				this.initData() // 初始化页数
				this.siteIdMap = item.shop_id;
				this.currShop = item;
				this.servicerId = item.servicer_id
				// 消除未读数
				this.sessionList.forEach(ites => {
					if (ites.shop_id == item.shop_id) {
						ites.unread = 0
						this.isRead(ites)
					}
				})
				this.messageList = [];
				this.closeDialog(); //关闭旧链接
				this.initWebSocket(); //打开新衔接
				this.getMessageList() //获取聊天信息
				this.text_num = 0 //重置文字数量
				this.$refs.msgInputContainer.innerHTML = '' //文本重置
			},
			//标识已读未读
			isRead(item) {
				console.log(item, 'item')
				readMessage({
					site_id: item.shop_id
				}).then(res => {
					console.log(res, 'res')
				}).catch(err => {})
			},
			// 获取联系人
			getSessionList() {
				console.log('获取联系人')
				sessionList({
						page_size: 30,
						page: 1
					})
					.then((res) => {
						this.sessionList = res.data
						// 如果是新加的链接，加入一个到联系人列表中
						let _item = {
							shop_name: this.shop.shop_name,
							shop_id: this.shop.shop_id,
							logo: this.shop.logo
						}
						var curr = this.sessionList.find((item) => {
							return item.shop_id == this.currShop.shop_id;
						})
						if (!curr) {
							this.sessionList.unshift(_item)
							this.test_online()
						}

						// 如果是第一次进入，模拟点击一下
						if (this.is_first) {
							this.is_first = false
							this.sessionList.forEach(item => {
								if (item.shop_id == this.currShop.shop_id) {
									this.selectShop(item)
								}
							})
						}

						// 如果当前有链接，链接的未读数处理
						// console.log(this.sessionList, '联系人列表')
						// console.log(this.siteIdMap, 'siteIdMap')

						this.sessionList.forEach(item => {
							if (this.siteIdMap == item.shop_id) {
								item.unread = 0
								this.isRead(item)
							}
						})


					})
					.catch((err) => {})
			},

			// 当前店铺的客服是否在线
			test_online() {
				// console.log(this.currShop.shop_id)
				hasServicers({
					site_id: this.currShop.shop_id
				}).then((res) => {
					// console.log(res, 'res')
					if (res.data.online_count > 0) {
						this.currShop.online = 1
						this.sessionList.forEach(item => {
							if (item.shop_id == this.currShop.shop_id) item.online = 1
						})
					} else {
						this.currShop.online = 0
					}
				})
			},

			// 滚动到底部
			scrollBottom() {
				var div = document.getElementById("content")
				setTimeout(() => {
					div.scrollTop = div.scrollHeight;
				}, 0)
			},

			// 获取聊天记录
			getMessageList() {
				messageList({
					page: this.page,
					limit: 4,
					servicer_id: this.servicerId,
					site_id: this.siteIdMap
				}).then((res) => {
					const {
						code,
						data,
						messge
					} = res
					if (code == 0) {
						let messageList = []
						let arr = res.data.list
						// console.log(arr, 'arr')
						// 处理聊天内容的类型
						arr.forEach((item) => {
							let obj = {}
							if (item.content_type == 0) {
								obj.content = item.type == 0 ? item.consumer_say : item.servicer_say
								obj.isItMe = item.type == 0 ? true : false //自己的消息还是对面发来的消息
								obj.contentType = "string"
								obj.nickname = item.nickname
								obj.shop_name = item.shop_name
								obj.time = item.create_day + '  ' + item.create_time
								if (item.avatar) {
									obj.logo = item.avatar
								} else {
									obj.logo = item.logo
								}
							} else if (item.content_type == 1) {
								obj.isItMe = item.type == 0 ? true : false
								obj.contentType = "goods"
								obj.sku_id = item.goods_sku_id
								obj.nickname = item.nickname
								obj.shop_name = item.shop_name
								obj.time = item.create_day + '  ' + item.create_time
								if (item.avatar) {
									obj.logo = item.avatar
								} else {
									obj.logo = item.logo
								}
							} else if (item.content_type == 2) {
								obj.isItMe = item.type == 0 ? true : false
								obj.contentType = "order"
								obj.order_id = item.order_id
								obj.nickname = item.nickname
								obj.shop_name = item.shop_name
								obj.time = item.create_day + '  ' + item.create_time
								if (item.avatar) {
									obj.logo = item.avatar
								} else {
									obj.logo = item.logo
								}
							} else if (item.content_type == 3) {
								obj.isItMe = item.type == 0 ? true : false
								obj.contentType = "image"
								obj.content = item.type == 0 ? item.consumer_say : item.servicer_say
								obj.nickname = item.nickname
								obj.shop_name = item.shop_name
								obj.time = item.create_day + '  ' + item.create_time
								if (item.avatar) {
									obj.logo = item.avatar
								} else {
									obj.logo = item.logo
								}
							}
							messageList.push(obj)
						})

						this.messageList = messageList.concat(!this.contentEmpty ? this.messageList : [])
						console.log(this.messageList, 'this.messageList')
						if (this.page == 1) {
							if (this.skuId) this.joinNewFakeMessage("mine", "goods")
							if (this.orderId) this.joinNewFakeMessage("mine", "order")
							this.scrollBottom()
						}
						if (this.page >= res.data.page_count) {
							this.contentEmpty = true
						} else {
							this.page += 1
						}

						// 等待数据完全处理完，滚动到底部位置
						// setTimeout(() => {
						// 	this.scrollBottom();
						// }, 500)
					}
				})
			},

			// 加入虚假数据
			joinNewFakeMessage(isItMe = "mine", contentType = "string") {
				// console.log('加入虚假数据')
				let data = {
					isItMe: isItMe == "mine" ? true : false,
					contentType: contentType
				}

				if (contentType == "string") {
					data.content = this.message
				}
				if (contentType == "goods") {
					data.sku_id = this.skuId
					data.canSend = true
				}
				if (contentType == "order") {
					data.order_id = this.orderId
					data.canSend = true
				}
				if (contentType == "image") {
					data.content = this.image
				}

				this.messageList.unshift(data)
				this.scrollBottom()
			},
			// 加入评论信息
			joinMessage(isItMe = "mine", contentType = "string") {
				// console.log('加入评论信息')
				let data = {
					isItMe: isItMe == "mine" ? true : false,
					contentType: contentType
				}
				if (contentType == "string") {
					data.content = this.message
				}
				if (contentType == "goods") {
					data.sku_id = this.skuId
					data.canSend = false
				}
				if (contentType == "order") {
					data.order_id = this.orderId
					data.canSend = false
				}
				if (contentType == "image") {
					data.content = this.image;

				}

				this.messageList.push(data);
				this.scrollBottom();
			},


			// 输入文字时
			input(event) {
				let str = this.$refs.msgInputContainer.innerHTML
				var a = str.replace(/<\s?img.*?src\s*=\s*[\"|\']?(.*?)[\"|\']\s.*?>/ig, '0');
				this.text_num = a.length
				if (this.text_num >= 150) {
					this.$message({
						message: "最多一次可以发送150个字~",
						type: "warning"
					})
				}
			},


			// 发送消息
			sendMessage(type = "string", index = -1) {
				if (index != -1) this.messageList.splice(index, 1)
				if (!this.sendSwitch) return
				this.sendSwitch = false
				let data = {
					servicer_id: this.servicerId,
					site_id: this.siteIdMap
				}
				let _content = this.$refs.msgInputContainer.innerHTML
				let _message = _content.replace(/<div>/g, '');
				let _message2 = _message.replace(/<\/div>/g, '');
				let _message3 = _message2.replace(/<br>/g, '');
				this.message = _message3


				if (type == "string") {
					if (!this.message.trim()) {
						this.sendSwitch = true;
						this.$message({
							message: "不能发送空白内容",
							type: "warning"
						})
						return
					}
					Object.assign(data, {
						message: this.message,
						content_type: 0
					})
				}
				if (type == "goods") {
					Object.assign(data, {
						goods_id: this.skuId,
						content_type: 1
					})
				}
				if (type == "order") {
					Object.assign(data, {
						order_id: this.orderId,
						content_type: 2
					})
				}
				if (type == "image") {
					Object.assign(data, {
						message: this.image,
						content_type: 3
					})
				}
				this.joinMessage("mine", type)
				sendMessage(data)
					.then((res) => {
						this.sendSwitch = true
						this.message = ""
						this.text_num = 0
						this.$refs.msgInputContainer.innerHTML = ''
					})
				this.scrollBottom();
			},
			// 初始化socket
			initWebSocket() {
				//初始化weosocket
				// console.log(Config.webSocket)
				const wsuri = Config.webSocket
				this.websock = new WebSocket(wsuri)
				this.websock.onmessage = this.websocketonmessage
				this.websock.onopen = this.websocketonopen
				this.websock.onerror = this.websocketonerror
				this.websock.onclose = this.websocketclose

			},
			websocketonopen() {
				//连接建立之后执行send方法发送数据
				console.log("连接建立")
			},
			websocketonerror() {
				//连接建立失败重连
				this.initWebSocket()
			},


			// 接受消息
			websocketonmessage(e) {
				//数据接收
				// console.log(e, 'e')
				let data = JSON.parse(e.data),
					that = this
				console.log(data, 'data')
				this.pingInterval = Config.pingInterval

				if (data.type == "close" && this.websock) {
					that.websock.close()
				}
				this.getSessionList();
				clearInterval(this.timeoutObj)
				this.reset()
				// 如果是第一次绑定
				if (data.type == "init") {
					this.messageList = [];
					bindServicer({
							client_id: data.data.client_id,
							site_id: this.siteIdMap
						})
						.then((res) => {
							const {
								code,
								data,
								message
							} = res
							var obj = {};
							// console.log(res, 'bindData')
							if (code == 0) {
								this.servicerId = data.servicer_id
								this.serve_info.name = data.nickname
								this.serve_info.logo = data.avatar
								obj = {
									contentType: "online"
								}

								if (this.isFirstInit) {
									this.initData()
									// this.getMessageList()
								}
								this.isFirstInit = false
							} else {
								obj = {
									contentType: "noline"
								}
							}

							this.messageList.unshift(obj);
						})
						.catch((err) => {
							var obj = {
								contentType: "noline"
							}
							// this.getMessageList();
							this.messageList.unshift(obj);
						})
				} else if (data.type == "connect") {
					if (data.data.shop_id == this.siteIdMap) {
						// 绑定
						if (data.data.shop_id) {
							this.servicerId = data.servicer_id
							var obj = {
								contentType: "online"
							}
							this.messageList.unshift(obj);
						} else {
							this.servicerId = 0
							var obj = {
								contentType: "noline"
							}
							this.messageList.unshift(obj);
						}
					}
				} else {
					if (data.data.shop_id == this.siteIdMap) {
						let contentData = {
							isItMe: false
						}
						if (data.type == "string") {
							contentData.content = data.data.servicer_say;
							contentData.time = data.data.create_day + data.data.create_time;
							contentData.logo = data.data.avatar;
							contentData.nickname = data.data.nickname;
							contentData.contentType = 'string'
						} else if (data.type == "order") {
							contentData.order_id = data.data.order_id;
							contentData.time = data.data.create_day + data.data.create_time;
							contentData.logo = data.data.avatar;
							contentData.nickname = data.data.nickname;
							contentData.contentType = 'order'
						} else if (data.type == "goodssku") {
							contentData.sku_id = data.data.goods_sku_id;
							contentData.time = data.data.create_day + data.data.create_time;
							contentData.logo = data.data.avatar;
							contentData.nickname = data.data.nickname;
							contentData.contentType = 'goods'
						} else if (data.type == "image") {
							contentData.content = data.data.servicer_say;
							contentData.time = data.data.create_day + data.data.create_time;
							contentData.logo = data.data.avatar;
							contentData.nickname = data.data.nickname;
							contentData.contentType = 'image';
						}
						this.messageList.push(contentData);
						// this.sessionList()
					}
				}
				this.scrollBottom();
			},
			// 检测心跳reset
			reset() {
				console.log("检测心跳")
				console.log(this.timeoutObj, 'this.timeoutObj')
				clearInterval(this.timeoutObj)
				this.start() // 启动心跳
			},
			// 启动心跳 start
			start() {
				if (Config.pingInterval == 0) return;
				console.log("启动心跳")
				this.timeoutObj = setInterval(() => {
					console.log('检测...')
					this.websock.send("ping")
				}, this.pingInterval)
			},
			websocketsend(Data) {
				//数据发送
				this.websock.send(Data)
			},


			websocketclose(e) {
				//关闭
				console.log("断开连接", e)

			},

			closeDialog() {
				clearInterval(this.timeoutObj)
				console.log('关闭链接')
				this.websock.close()
				let data = {
					servicer_id: this.servicerId,
					site_id: this.siteIdMap
				}
				// console.log(this.websock, '关闭连接')
				if (this.servicerId && this.websock) {
					closeMessage(data).then((res) => {
						// console.log(res, 'res')
						if (res.code == 0) {
							clearInterval(this.timeoutObj)

						}
					})
				}
			},
			handleAvatarSuccess(res, file) {
				this.image = res.data.pic_path;
				this.sendMessage('image');
			}
		}
	}
</script>

<style>
	.kefu .el-dialog__header {
		padding: 0 !important;
	}

	.kefu .el-dialog__headerbtn {
		top: 8px !important;
	}

	.kefu .el-dialog__body {
		padding: 0 !important;
		min-width: 800px;
	}

	.kefu .el-dialog {
		min-width: 860px !important;
	}
</style>

<style lang="scss" scoped="">
	html,
	body {
		overflow-y: scroll;
	}

	html,
	body {
		overflow: scroll;
		min-height: 101%;
	}

	html {
		overflow: -moz-scrollbars-vertical;
	}

	.service {
		min-width: 800px;

		.header-box {
			color: white;
			align-items: center;
			padding: 10px 15px;
			border-bottom: 1px solid #eee;
			font-size: 15px;
			padding-left: 260px;
			display: flex;
			align-items: center;
			justify-content: center;

			.header-logo {
				position: absolute;
				top: 10px;
				left: 15px;
				display: flex;
				align-items: center;

				.header-img {
					margin-right: 10px;
					background-color: white;
					border-radius: 50%;
					width: 26px;
					height: 26px;
				}
			}

			.online {
				line-height: 1.3;
				margin-left: 5px;
				padding: 2px 6px;
				background-color: white;
				border-radius: 10px;
				font-size: 10px;
				display: flex;
				color: #000;
				align-items: center;

				.block {
					margin-right: 4px;
					border-radius: 50%;
					width: 6px;
					height: 6px;
					background-color: rgba(#70ed3a, 1);
				}
			}

			.outline {
				line-height: 1.3;
				margin-left: 5px;
				padding: 2px 6px;
				background-color: white;
				border-radius: 10px;
				font-size: 10px;
				display: flex;
				color: #000;
				align-items: center;

				.block {
					margin-right: 4px;
					border-radius: 50%;
					width: 6px;
					height: 6px;
					background-color: rgba(#b2b2b2, 1);
				}
			}

		}
	}

	.operation {
		display: flex;
		align-items: center;
		padding: 10px 24px;

		img {
			margin-right: 10px;
		}

		.emjoy {
			width: 20px;
			height: 20px;
		}
	}

	.service-box {
		min-width: 800px;
		display: flex;

		.service-box-left {
			width: 250px;
			// height: 630px;

			// .curr_peo {
			// 	display: flex;
			// 	align-items: center;
			// 	padding: 20px 24px;
			// 	background-color: #F3F3F3;
			// 	background-image: linear-gradient(to right, #F3F3F3, #F3F3F3);

			// 	img {
			// 		margin-right: 10px;
			// 		height: 60px;
			// 		width: 60px;
			// 		min-width: 60px;
			// 		border-radius: 10px;
			// 	}

			// 	.curr_peo_name {
			// 		color: #000;
			// 		font-size: 16px;
			// 		overflow: hidden;
			// 		text-overflow: ellipsis;
			// 		white-space: nowrap;

			// 	}
			// }

			.service-box-left-top {
				background-color: $base-color;
				padding: 10px;

				.member-info {
					display: flex;

					.headimg {
						width: 50px;
						height: 50px;

						img {
							width: 100%;
							height: 100%;
						}
					}

					.member-nickname {
						flex: 1;

						div {
							color: #fff;
						}
					}
				}

				.search-box {
					display: flex;
					background-color: #fff;
					align-items: center;
					height: 24px;
					border-radius: 4px;
					padding: 0 5px;

					input {
						padding: 0;
						outline: none;
						border: 0;
						margin-left: 5px;
						height: 100%;
						line-height: 24px;
					}
				}
			}

			.people-box {
				height: 590px;
				overflow-y: auto;

				.people-item {
					cursor: pointer;
					display: flex;
					align-items: center;
					justify-content: space-between;
					padding: 15px 24px;
					border-bottom: 1px solid #f1f1f1;
					justify-content: center;

					.shop-info {

						position: relative;
						display: flex;
						align-items: center;
						overflow: hidden;
						flex: 1;

						.shop_img {
							width: 35px;
							height: 35px;
							max-width: 35px;
							margin-right: 10px;
							background-size: contain;
							background-position: center;
							background-repeat: no-repeat;
							border-radius: 50%;
						}

						>div {
							flex: 1;
							overflow: hidden;

							div {
								color: #333;
								display: flex;
								justify-content: space-between;
								align-items: center;
								overflow: hidden;

								.time {
									color: #A7ACB4;
								}

								&.last_content {
									overflow: hidden;

									.msg {
										color: #96999C;
										overflow: hidden;
										text-overflow: ellipsis;
										white-space: nowrap;
										flex: 1;
										display: block;
										font-size: 12px;

										img {
											width: 5px !important;
											height: 5px !important;
										}
									}

									.num {
										color: #fff;
										top: 0;
										background-color: #FF4544;
										height: 14px;
										line-height: 14px;
										padding: 0 3.5px;
										border-radius: 20px;
										font-size: 12px;
										text-align: center;
										justify-content: center;
									}
								}
							}
						}

						.online-people {
							position: absolute;
							bottom: 0;
							left: 26px;
							border-radius: 50%;
							background-color: #70ed3a;
							width: 10px;
							height: 10px;
						}

						.outline-people {
							position: absolute;
							bottom: 0;
							left: 26px;
							border-radius: 50%;
							background-color: #b2b2b2;
							width: 10px;
							height: 10px;
						}
					}

					&.active {
						background-color: #edf3fa;
						border-bottom: 1px solid #edf3fa;

						.shop-info {
							.shop_img {
								width: 35px;
								height: 35px;
							}
						}
					}
				}
			}



			.people-box::-webkit-scrollbar {
				/*滚动条整体样式*/

				width: 5px;
				/*高宽分别对应横竖滚动条的尺寸*/

				height: 1px;

			}

			.people-box::-webkit-scrollbar-thumb {
				/*滚动条里面小方块*/

				border-radius: 20px;

				-webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);

				background: #BFBFBF;

			}

			.people-box::-webkit-scrollbar-track {
				/*滚动条里面轨道*/

				-webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1);

				border-radius: 10px;

				background: white;

			}




		}

		.service-box-right {
			flex: 1;
			overflow: hidden;
			// .curr_company {
			// 	display: flex;
			// 	align-items: center;
			// 	justify-content: center;
			// 	padding: 20px 24px;
			// 	background-color: #F3F3F3;
			// 	color: #333;

			// 	img {
			// 		height: 60px;
			// 		width: 60px;
			// 		min-width: 60px;
			// 		margin-right: 20px;
			// 		border-radius: 10px;
			// 	}

			// .curr_company_name {
			// 	font-size: 16px;

			// }

			// 	>div>div {
			// 		&:last-child {
			// 			color: #000;
			// 		}
			// 	}
			// }

			.service-box-right-top {
				height: 450px;
				border-left: 1px solid #eee;
				overflow-y: auto;
				width: 102%;

				.shop-name {
					display: flex;
					height: 60px;
					align-items: center;
					// border-bottom: 1px solid #eee;

					img {
						width: 40px;
						height: 40px;
						margin-right: 5px;
						margin-left: 10px;
					}
				}

				.content {
					height: 100%;
					overflow-y: auto;
					display: flex;
					flex-direction: column;

					&>div {
						margin: 10px 0 0;

						.onlineStatus {
							margin: auto
						}
					}

					.last-tip {
						color: #a1a6af;
						position: relative;

						&::before {
							content: "";
							position: absolute;
							top: 50%;
							transform: translateY(-50%);
							width: 70px;
							height: 1px;
							background: #a1a6af;
							left: -80px;
						}

						&::after {
							content: "";
							position: absolute;
							top: 50%;
							transform: translateY(-50%);
							width: 70px;
							height: 1px;
							background: #a1a6af;
							right: -80px;
						}
					}

					.time {
						color: #a1a6af;
					}

					.message {
						display: flex;
						justify-content: flex-end;
						align-items: flex-start;

						.my-nickname {
							text-align: right;
							color: #909399;

							span {
								padding-left: 10px;
								font-size: 10px;
							}
						}

						.headimg {
							width: 32px;
							height: 32px;
							margin: 5px 20px 0 20px;
							background-size: contain;
							background-position: center;
							background-repeat: no-repeat;
							border-radius: 50%;
						}

						&.your-message {
							justify-content: flex-start;

							.my-nickname {
								text-align: left;
							}
						}
					}

					.word-message {
						background-color: #89BCFF;
						color: #333;
						position: relative;
						padding: 10px;
						border-radius: 4px;
						max-width: 450px;
						width: fit-content;

						::v-deep img {
							max-width: 150px;
						}

						::v-deep .el-image-viewer__close {
							color: #fff;
						}

						&::after {
							content: "";
							position: absolute;
							width: 10px;
							height: 10px;
							background-color: #89BCFF;
							top: 15px;
							right: -5px;
							transform: rotateZ(45deg);
						}

						&.your-word-message {
							background-color: #F0F0F0;
							color: #333;

							&::after {
								left: -5px;
								background-color: #F0F0F0;
								transform: rotateZ(45deg);
							}

							img {
								max-width: 150px;
							}
						}
					}
				}
			}

			.service-box-right-top::-webkit-scrollbar {
				width: 10px;
			}

			.service-box-right-top::-webkit-scrollbar-thumb {
				border-radius: 10px;
				-webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
				background: #535353;
			}

			.service-box-right-top::-webkit-scrollbar-track {
				-webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
				border-radius: 10px;
				background: #EDEDED;
			}

			.service-box-right-bottom {
				border-top: 1px solid #eee;
				border-left: 1px solid #eee;
				height: calc(100% - 450px);
				position: relative;

				.num-box {
					position: absolute;
					bottom: 13px;
					right: 68px;
					text-align: right;
					width: 60px;
					color: #909399;
				}

				.send-btn {
					position: absolute;
					bottom: 10px;
					right: 10px;
					background-color: #FF4649;
					color: #fff
				}

				.emoji-btn {
					position: absolute;
					bottom: 10px;
					left: 10px;
				}
			}
		}
	}



	.input-panel {
		width: 100%;
		height: 57px;
		margin-bottom: 3px;
		padding: 0px 24px;
		outline: none;
		box-sizing: border-box;
		line-height: 1.3;
		overflow-y: scroll;
	}

	.input-panel::-webkit-scrollbar {
		display: none;
	}

	.service {
		::v-deep .el-dialog__header {
			padding: 0;
		}

		::v-deep .el-dialog__headerbtn {
			top: 17px !important;
		}

		::v-deep .el-dialog__body {
			height: 640px;
		}

		::v-deep .el-dialog__headerbtn .el-dialog__close {
			color: white;
		}
	}

	.text-center {
		text-align: center;
	}

	.text-hidden {
		text-overflow: ellipsis;
		overflow: hidden;
		white-space: nowrap;
	}

	.text-hidden-two-row {
		text-overflow: -o-ellipsis-lastline;
		overflow: hidden;
		text-overflow: ellipsis;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		line-clamp: 2;
		-webkit-box-orient: vertical;
	}

	.emoji-box {
		background-color: #fff;
		z-index: 9999;
		position: absolute;
		height: 90px;
		overflow: hidden scroll;
		padding-left: 14px;
		top: -90px;

		img {
			margin: 5px;
		}
	}

	.emoji-box::-webkit-scrollbar {
		/*滚动条整体样式*/
		width: 5px;
	}

	.emoji-box::-webkit-scrollbar-thumb {
		/*滚动条里面小方块*/
		border-radius: 20px;
		// -webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.2);
		background: white;
	}

	.emoji-box::-webkit-scrollbar-track {
		/*滚动条里面轨道*/
		// -webkit-box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1);
		border-radius: 10px;
		background: white;
	}

	.onlineStatus {
		background-color: #6b6c6d;
		text-align: center;
		width: 90px;
		margin: auto;
		font-size: 12px;
		color: #fff;
		border-radius: 6px;
		line-height: 22px;
	}

	::v-deep .upload {
		line-height: 1;

		::v-deep .el-upload {
			line-height: 1;
		}
	}
</style>