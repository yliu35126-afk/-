<template>
	<div class="ns-register-wrap" :style="{background: backgroundColor}" v-loading="loadingAd">
	<div class="el-row-wrap el-row-wrap-register" style="position: relative;">
		<el-row>
			<el-col>
				<el-carousel height="500px" class="ns-register-bg" @change="handleChange">
					<el-carousel-item  v-for="item in adList" :key="item.adv_id">
						<el-image style="height:500px; width: 100%;" :src="$img(item.adv_image)" fit="cover"
							@click="$router.pushToTab(item.adv_url.url)" />
					</el-carousel-item>
				</el-carousel>
			</el-col >
			<el-col :span="11" class="ns-register-form" style="position: absolute;right: 360px; z-index: 999;">
				<div class="grid-content bg-purple">
					<el-tabs v-model="activeName" @tab-click="handleClick">
						<el-tab-pane label="用户注册" name="first">
							<el-form v-if="activeName == 'first'" :model="registerForm" ref="registerRef" :rules="registerRules">
								<el-form-item prop="username">
									<el-input v-model="registerForm.username" placeholder="请输入用户名">
										<template slot="prepend">
											<i class="iconfont iconzhanghao"></i>
										</template>
									</el-input>
								</el-form-item>
								<el-form-item prop="password">
									<el-input type="password" v-model="registerForm.password" autocomplete="off"
										placeholder="请输入密码">
										<template slot="prepend">
											<i class="iconfont iconmima"></i>
										</template>
									</el-input>
								</el-form-item>
								<el-form-item prop="code">
									<el-input v-model="registerForm.code" autocomplete="off" placeholder="请输入验证码"
										maxlength="4">
										<template slot="prepend">
											<i class="iconfont iconyanzhengma"></i>
										</template>
										<template slot="append">
											<img :src="captcha.img" mode class="captcha" @click="getCode" />
										</template>
									</el-input>
								</el-form-item>
								<el-form-item>
									<el-row>
										<el-col :span="12">
											<div class="xy-wrap">
												<div class="iconfont" @click="check" :class="ischecked ? 'iconxuanze-duoxuan' : 'iconxuanze'"></div>
												<div class="content">
												阅读并同意
												<b @click.stop="getAggrement">《服务协议》</b>
												</div>
											</div>
										</el-col>
									</el-row>
								</el-form-item>
								<el-form-item>
									<el-button type="primary" class="rule-button" @click="register('registerRef')">
										注册</el-button>
								</el-form-item>
								<el-form-item>
									<el-row>
										<el-col :span="24">
											<div class="bg-purple-light">已有账号，<router-link class="register"
													to="/login">立即登录</router-link>
											</div>
										</el-col>
									</el-row>
								</el-form-item>
								<!-- <el-form-item>
									<el-row>
										<el-col :span="24">
											<div class="bg-purple-light toLogin" @click="toLogin">已有账号，立即登录</div>
										</el-col>
									</el-row>
								</el-form-item> -->
							</el-form>
						</el-tab-pane>
		
						<!-- <el-tab-pane>
							<div class="split-line"></div>
						</el-tab-pane> -->
		
						<el-tab-pane label="手机动态码注册" name="second"
							v-if="registerConfig.register && registerConfig.register.indexOf('mobile') != -1">
							<el-form v-if="activeName == 'second'" :model="registerForm" :rules="mobileRules"
								ref="mobileRuleForm" class="ns-register-mobile">
								<el-form-item prop="mobile">
									<el-input v-model="registerForm.mobile" placeholder="请输入手机号">
										<template slot="prepend">
											<i class="iconfont iconshouji-copy"></i>
										</template>
									</el-input>
								</el-form-item>
								<el-form-item prop="code">
									<el-input v-model="registerForm.code" autocomplete="off" placeholder="请输入验证码"
										maxlength="4">
										<template slot="prepend">
											<i class="iconfont iconyanzhengma"></i>
										</template>
										<template slot="append">
											<img :src="captcha.img" mode class="captcha" @click="getCode" />
										</template>
									</el-input>
								</el-form-item>
		
								<el-form-item prop="dynacode">
									<el-input v-model="registerForm.dynacode" maxlength="4" placeholder="请输入短信动态码">
										<template slot="prepend">
											<i class="iconfont icondongtaima"></i>
										</template>
										<template slot="append">
											<div class="dynacode"
												:class="dynacodeData.seconds == 120 ? 'ns-text-color' : 'ns-text-color-gray'"
												@click="sendMobileCode('mobileRuleForm')">
												{{ dynacodeData.codeText }}
											</div>
										</template>
									</el-input>
								</el-form-item>
								<el-form-item>
									<el-row>
										<el-col :span="12">
											<div class="xy-wrap">
												<div class="iconfont" @click="check" :class="ischecked ? 'iconxuanze-duoxuan' : 'iconxuanze'"></div>
												<div class="content">
												阅读并同意
												<b @click.stop="getAggrement">《服务协议》</b>
												</div>
											</div>
										</el-col>
									</el-row>
								</el-form-item>
								<el-form-item>
									<el-button type="primary" class="rule-button" @click="register('mobileRuleForm')">
										注册</el-button>
								</el-form-item>
										
								<el-form-item>
									<el-row>
										<el-col :span="24">
											<div class="bg-purple-light toLogin" @click="toLogin">已有账号，立即登录</div>
										</el-col>
									</el-row>
								</el-form-item>
								
							</el-form>
						</el-tab-pane>
					</el-tabs>
				</div>
			</el-col>
		</el-row>
		<el-dialog :title="agreement.title" :visible.sync="aggrementVisible" width="60%" :before-close="aggrementClose" :lock-scroll="false" center>
			<div v-html="agreement.content" class="xyContent"></div>
		</el-dialog>
		<!-- <div class="box-card">
			<div class="register-title">用户注册</div>
			<div class="register-account">
				<el-form :model="registerForm" :rules="registerRules" ref="registerRef" label-width="80px" label-position="right" show-message>
					<el-form-item label="用户名" prop="username"><el-input v-model="registerForm.username" placeholder="请输入用户名"></el-input></el-form-item>
					<el-form-item label="密码" prop="password"><el-input v-model="registerForm.password" placeholder="请输入密码" type="password"></el-input></el-form-item>
					<el-form-item label="确认密码" prop="checkPass">
						<el-input v-model="registerForm.checkPass" placeholder="请输入确认密码" type="password"></el-input>
					</el-form-item>
					<el-form-item label="验证码" prop="code">
						<el-input v-model="registerForm.code" placeholder="请输入验证码" maxlength="4">
							<template slot="append">
								<img :src="captcha.img" mode class="captcha" @click="getCode" />
							</template>
						</el-input>
					</el-form-item>
				</el-form>
				<div class="xy" @click="check">
					<div class="xy-wrap">
						<div class="iconfont" :class="ischecked ? 'iconxuanze-duoxuan' : 'iconxuanze'"></div>
						<div class="content">
							阅读并同意
							<b @click.stop="getAggrement">《服务协议》</b>
						</div>
					</div>
					<div class="toLogin" @click="toLogin">已有账号，立即登录</div>
				</div>
				<el-button @click="register">立即注册</el-button>
			</div>
			<el-dialog :title="agreement.title" :visible.sync="aggrementVisible" width="60%" :before-close="aggrementClose" :lock-scroll="false" center>
				<div v-html="agreement.content" class="xyContent"></div>
			</el-dialog>
		</div> -->
	
	</div>
	</div>
</template>

<script>
import { getRegisiterAggrement, register, registerConfig } from '@/api/auth/register';
import { mobileCode } from '@/api/auth/login';
import { adList, captcha } from '@/api/website';
export default {
	name: 'register',
	components: {},
	
	data() {
		var isMobile = (rule, value, callback) => {
			if (!value) {
				return callback(new Error("手机号不能为空"))
			} else {
				const reg = /^1[3|4|5|6|7|8|9][0-9]{9}$/
		
				if (reg.test(value)) {
					callback()
				} else {
					callback(new Error("请输入正确的手机号"))
				}
			}
		};
		// var checkPassValidata = (rule, value, callback) => {
		// 	if (value === '') {
		// 		callback(new Error('请再次输入密码'));
		// 	} else if (value !== this.registerForm.password) {
		// 		callback(new Error('两次输入密码不一致!'));
		// 	} else {
		// 		callback();
		// 	}
		// };
		let self = this;
		var passwordValidata = function(rule, value, callback) {
			let regConfig = self.registerConfig;
			if (!value) {
				return callback(new Error('请输入密码'));
			} else {
				if (regConfig.pwd_len > 0) {
					if (value.length < regConfig.pwd_len) {
						return callback(new Error('密码长度不能小于' + regConfig.pwd_len + '位'));
					} else {
						callback();
					}
				} else {
					if (regConfig.pwd_complexity != '') {
						let passwordErrorMsg = '密码需包含',
							reg = '';
						if (regConfig.pwd_complexity.indexOf('number') != -1) {
							reg += '(?=.*?[0-9])';
							passwordErrorMsg += '数字';
						} else if (regConfig.pwd_complexity.indexOf('letter') != -1) {
							reg += '(?=.*?[a-z])';
							passwordErrorMsg += '、小写字母';
						} else if (regConfig.pwd_complexity.indexOf('upper_case') != -1) {
							reg += '(?=.*?[A-Z])';
							passwordErrorMsg += '、大写字母';
						} else if (regConfig.pwd_complexity.indexOf('symbol') != -1) {
							reg += '(?=.*?[#?!@$%^&*-])';
							passwordErrorMsg += '、特殊字符';
						} else {
							reg += '';
							passwordErrorMsg += '';
						}

						if (reg.test(value)) {
							return callback(new Error(passwordErrorMsg));
						} else {
							callback();
						}
					}
				}
			}
		};
		return {
			activeName: "first", // tab切换
			registerForm: {
				username: '',
				password: '',
				checkPass: '',
				code: '',
				dynacode: "",
			},
			registerRules: {
				username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
				password: [
					{
						required: true,
						validator: passwordValidata,
						trigger: 'blur'
					}
				],
				// checkPass: [{ required: true, validator: checkPassValidata, trigger: 'blur' }],
				code: [{ required: true, 
						message: '请输入验证码',
						trigger: 'blur' }],
				dynacode: [{
					required: true,
					message: "请输入短信动态码",
					trigger: "blur"
				}]
			},
			mobileRules: {
				mobile: [{
					required: true,
					validator: isMobile,
					trigger: "blur"
				}],
				vercode: [{
					required: true,
					message: "请输入验证码",
					trigger: "blur"
				}],
				dynacode: [{
					required: true,
					message: "请输入短信动态码",
					trigger: "blur"
				}]
			},
			codeRules: {
				mobile: [{
					required: true,
					validator: isMobile,
					trigger: "blur"
				}],
				vercode: [{
					required: true,
					message: "请输入验证码",
					trigger: "blur"
				}]
			},
			ischecked: false,
			agreement: '',
			aggrementVisible: false,
			captcha: {
				// 验证码
				id: '',
				img: ''
			},
			dynacodeData: {
				seconds: 120,
				timer: null,
				codeText: "获取动态码",
				isSend: false
			}, // 动态码
			adList: [],
			loadingAd: true,
			registerConfig: {},
			backgroundColor: ''
		};
	},
	created() {
		this.getCode();
		this.getAdList();
		this.regisiterAggrement();
		this.getRegisterConfig();
		
		let that = this;
		document.onkeypress = function(e) {
			var keycode = document.all ? event.keyCode : e.which;
			if (keycode == 13) {
				if (that.activeName == "first") {
					that.accountLogin('ruleForm'); // 登录方法名
				} else {
					that.mobileLogin('mobileRuleForm'); // 登录方法名
				}
		
				return false;
			}
		};
	},
	watch: {
		"dynacodeData.seconds": {
			handler(newValue, oldValue) {
				if (newValue == 0) {
					clearInterval(this.dynacodeData.timer)
					this.dynacodeData = {
						seconds: 120,
						timer: null,
						codeText: "获取动态码",
						isSend: false
					}
				}
			},
			immediate: true,
			deep: true
		}
	},
	methods: {
		getAdList() {
			adList({
					keyword: "NS_PC_REGISTER"
				})
				.then(res => {
					if (res.code == 0 && res.data.adv_list) {
						this.adList = res.data.adv_list
						for (let i = 0; i < this.adList.length; i++) {
							if (this.adList[i].adv_url) this.adList[i].adv_url = JSON.parse(this.adList[i].adv_url)
						}
						this.backgroundColor = this.adList[0].background
					}
		
					this.loadingAd = false
				})
				.catch(err => {
					this.loadingAd = false
				})
		},
		handleChange(curr, pre) {
			this.backgroundColor = this.adList[curr].background
		},
		handleClick(tab, event) {
			if (this.activeName == "first") {
				this.registerMode = "account"
			} else {
				this.registerMode = "mobile"
			}
		},
		
		check() {
			this.ischecked = !this.ischecked;
		},
		toLogin() {
			this.$router.push('/login');
		},
		
		//  获取注册配置
		getRegisterConfig() {
			registerConfig()
				.then(res => {
					if (res.code >= 0) {
						this.registerConfig = res.data.value;
						if (this.registerConfig.register == '') {
							this.$message({
								message: '平台未启用注册',
								type: 'warning',
								duration: 2000,
								onClose: () => {
									this.$router.push('/');
								}
							});
						}
					}
				})
				.catch(err => {
					console.log(err.message)
				});
		},
		// 注册
		register(formName) {
			this.$refs[formName].validate(valid => {
				if (valid) {
					if (!this.ischecked) {
						return this.$message({
							message: '请先阅读协议并勾选',
							type: 'warning'
						});
					}
					var data = {
						username: this.registerForm.username.trim(),
						password: this.registerForm.password
					};
					if (this.captcha.id != '') {
						data.captcha_id = this.captcha.id;
						data.captcha_code = this.registerForm.code;
					}
					this.$store
						.dispatch('member/register_token', data)
						.then(res => {
							if (res.code >= 0) {
								this.$router.push('/member/index');
							}
						})
						.catch(err => {
							this.$message.error(err.message);
							this.getCode();
						});
				} else {
					return false;
				}
			});
		},
		aggrementClose() {
			this.aggrementVisible = false;
		},
		// 获取协议
		regisiterAggrement() {
			getRegisiterAggrement()
				.then(res => {
					if (res.code >= 0) {
						this.agreement = res.data;
					}
				})
				.catch(err => {
					console.log(err.message)
				});
		},
		getAggrement() {
			this.aggrementVisible = true;
		},
		// 获取验证码
		getCode() {
			captcha({ captcha_id: 'this.captcha.id' })
				.then(res => {
					if (res.code >= 0) {
						this.captcha = res.data;
						this.captcha.img = this.captcha.img.replace(/\r\n/g, '');
					}
				})
				.catch(err => {
					this.$message.error(err.message);
				});
		},
		
		/**
		 * 发送手机动态码
		 */
		sendMobileCode(formName) {
			
			if (this.dynacodeData.seconds != 120) return
			this.$refs[formName].clearValidate("dynacode")
		
			this.$refs[formName].validateField("mobile", valid => {
				if (valid) {
					return false
				}
			})
			this.$refs[formName].validateField("code", valid => {
				if (!valid) {
					mobileCode({
							mobile: this.registerForm.mobile,
							captcha_id: this.captcha.id,
							captcha_code: this.registerForm.code
						})
						.then(res => {
							if (res.code >= 0) {
								this.registerForm.key = res.data.key
								if (this.dynacodeData.seconds == 120 && this.dynacodeData.timer == null) {
									this.dynacodeData.timer = setInterval(() => {
										this.dynacodeData.seconds--
										this.dynacodeData.codeText = this.dynacodeData.seconds + "s后可重新获取"
									}, 1000)
								}
							}
						})
						.catch(err => {
							this.$message.error(err.message)
						})
				} else {
					return false
				}
			})
		}
	}
};
</script>
<style lang="scss" scoped>
// .register {
// 	width: 100%;
// 	height: 100%;
// 	display: flex;
// 	justify-content: center;
// 	align-items: center;
// 	margin: 20px 0;
// }
// .box-card {
// 	width: 500px;
// 	margin: 0 auto;
// 	display: flex;
// 	background-color: #ffffff;
// 	padding: 0 30px 30px 30px;
// 	flex-direction: column;

// 	.register-title {
// 		border-bottom: 1px solid #f1f1f1;
// 		text-align: left;
// 		margin-bottom: 20px;
// 		font-size: 16px;
// 		color: $base-color;
// 		padding: 10px 0;
// 	}
// 	.register-account {
// 		width: 100%;
// 		text-align: center;
// 	}
// 	.code {
// 		width: 80%;
// 		text-align: left;
// 	}
// 	.el-form {
// 		margin: 0 30px;
// 		.captcha {
// 			vertical-align: top;
// 			max-width: inherit;
// 			max-height: 38px;
// 			line-height: 38px;
// 			cursor: pointer;
// 		}
// 	}
// 	.xyContent {
// 		height: 600px;
// 		overflow-y: scroll;
// 	}
// 	.xy {
// 		margin-left: 110px;
// 		display: flex;
// 		justify-content: space-between;
// 		align-items: center;
// 		text-align: left;
// 		margin-right: 30px;
// 		.toLogin {
// 			cursor: pointer;
// 		}
// 		
// 		.iconxuanze-duoxuan {
// 			color: $base-color;
// 		}
// 	}
// 	.el-button {
// 		margin-top: 20px;
// 		background-color: $base-color;
// 		color: #ffffff;
// 		width: calc(100% - 60px);
// 	}
// }

.iconxuanze-duoxuan {
			color: $base-color;
		}
.xy-wrap {
			display: flex;
			align-items: center;
			font-size: $ns-font-size-base;
			cursor: pointer;
			.iconfont {
				display: flex;
				align-content: center;
			}
			.toLogin {
				cursor: pointer;
			}
			.content {
				margin-left: 3px;
				b {
				color: $base-color;
				}
			}
		}

.ns-register-wrap {
		width: 100%;
		height: 500px;
		min-width: $width;

		.el-row-wrap-register {
			// width: 1200px;
			margin: 0 auto;

			.ns-register-bg {
				// margin-top: 40px;
				el-image {
					height: 500px;
					width: 100%;
					height: 100%;
					object-fit: cover;
				}
			}

			.ns-register-form {
				width: 400px;
				margin-left: 50px;
				background: #ffffff;
				margin-top: 50px;

				.el-form {
					.captcha {
						vertical-align: top;
						max-width: inherit;
						max-height: 38px;
						line-height: 38px;
						cursor: pointer;
					}

					.dynacode {
						cursor: pointer;
					}

					[class*=' el-icon-'],
					[class^='el-icon-'] {
						font-size: 16px;
					}
				}

				.grid-content {
					padding: 10px 20px;
				}

				.el-form-item__error {
					padding-left: 50px;
				}

				button {
					width: 100%;
				}

				.ns-forget-pass {
					text-align: right;
				}

				i {
					font-size: 18px;
				}

				.bg-purple-light {
					display: flex;
					justify-content: flex-end;
					align-items: center;

					.register {
						color: #fd274a;
					}
				}
			}
		}
	}

	.rule-button {
		background-color: #fd274a;
	}

	.split-line {
		width: 200px;
		height: 50px;
		padding: 10px 200px;
		background: #f00;
	}
</style>

<style lang="scss">
	.ns-register-form {
		.el-form-item__error {
			/* 错误提示信息 */
			padding-left: 57px;
		}

		// .el-tabs__active-bar,
		// .el-tabs__nav-wrap::after {
		// 	/* 清除tab标签底部横线 */
		// 	height: 0;
		// }

		/* 立即注册 */
		.el-form-item__content {
			line-height: 20px;
		}
	}
</style>
