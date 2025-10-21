<template>
	<view class="audit-inner">
		<block v-if="shopApplyData">
			<view class="audit-status">
				<text class="color-base-text font-size-toolbar">{{auditInfo['procedure_'+procedure].title}}</text>
				<view class="color-sub">{{auditInfo['procedure_'+procedure].text}}<text class="color-base-text" @click="getApplyMessage">{{auditInfo['procedure_'+procedure].error}}</text></view>
			</view>
			
			<view class="audit-wrap">
				<text class="audit-title">会员信息</text>
				<view class="audit-item">
					<text>会员昵称</text>
					<text>{{shopApplyData.username}}</text>
				</view>
			</view>
			
			<view class="audit-wrap" v-if="shopApplyData.cert_type == 2">
				<text class="audit-title">公司信息</text>
				<view class="audit-item">
					<text>公司名称</text>
					<text>{{shopApplyData.company_name}}</text>
				</view>
				<view class="audit-item">
					<text>联系地址</text>
					<text>{{shopApplyData.company_full_address}}</text>
				</view>
				<view class="audit-item">
					<text>统一社会信用码</text>
					<text>{{shopApplyData.business_licence_number}}</text>
				</view>
				<view class="audit-item">
					<text>营业执照电子版</text>
					<view class="img-wrap" @click="previewImg(shopApplyData.business_licence_number_electronic)">
						<image :src="$util.img(shopApplyData.business_licence_number_electronic)" mode="aspectFit"></image>
					</view>
				</view>
				<view class="audit-item">
					<text>法定经营范围</text>
					<text>{{shopApplyData.business_sphere}}</text>
				</view>
				<view class="audit-item">
					<text>法人姓名</text>
					<text>{{shopApplyData.contacts_name}}</text>
				</view>
				<view class="audit-item">
					<text>法人手机</text>
					<text>{{shopApplyData.contacts_mobile}}</text>
				</view>
				<view class="audit-item">
					<text>法人身份证</text>
					<text>{{shopApplyData.contacts_card_no}}</text>
				</view>
				<view class="audit-item">
					<text>法人身份证正面</text>
					<view class="img-wrap" @click="previewImg(shopApplyData.contacts_card_electronic_2)">
						<image :src="$util.img(shopApplyData.contacts_card_electronic_2)" mode="aspectFit"></image>
					</view>
				</view>
				<view class="audit-item">
					<text>法人身份证反面</text>
					<view class="img-wrap" @click="previewImg(shopApplyData.contacts_card_electronic_3)">
						<image :src="$util.img(shopApplyData.contacts_card_electronic_3)" mode="aspectFit"></image>
					</view>
				</view>
				
			</view>
			
			<view class="audit-wrap"  v-if="shopApplyData.cert_type == 1">
				<text class="audit-title">个人信息</text>
				<view class="audit-item">
					<text>联系人姓名</text>
					<text>{{shopApplyData.contacts_name}}</text>
				</view>
				<view class="audit-item">
					<text>联系人手机</text>
					<text>{{shopApplyData.contacts_mobile}}</text>
				</view>
				<view class="audit-item">
					<text>联系人身份证</text>
					<text>{{shopApplyData.contacts_card_no}}</text>
				</view>
				<view class="audit-item">
					<text>身份证正面</text>
					<view class="img-wrap" @click="previewImg(shopApplyData.contacts_card_electronic_2)">
						<image :src="$util.img(shopApplyData.contacts_card_electronic_2)" mode="aspectFit"></image>
					</view>
				</view>
				<view class="audit-item">
					<text>身份证反面</text>
					<view class="img-wrap" @click="previewImg(shopApplyData.contacts_card_electronic_3)">
						<image :src="$util.img(shopApplyData.contacts_card_electronic_3)" mode="aspectFit"></image>
					</view>
				</view>
			</view>
			
			<view class="audit-wrap">
				<text class="audit-title">银行信息</text>
				<view class="audit-item">
					<text>银行开户名</text>
					<text>{{shopApplyData.bank_account_name}}</text>
				</view>
				<view class="audit-item">
					<text>开户银行账号</text>
					<text>{{shopApplyData.bank_account_number}}</text>
				</view>
				<view class="audit-item">
					<text>开户银行支行名称</text>
					<text>{{shopApplyData.bank_name}}</text>
				</view>
				<view class="audit-item">
					<text>开户银行所在地</text>
					<text>{{shopApplyData.bank_address}}</text>
				</view>
				<view class="audit-item">
					<text>支行联行号</text>
					<text>{{shopApplyData.bank_code}}</text>
				</view>
				
				<block v-if="shopApplyData.bank_type == 1">
					<view class="audit-item">
						<text>结算开户名</text>
						<text>{{shopApplyData.settlement_bank_account_name}}</text>
					</view>
					<view class="audit-item">
						<text>结算银行账号</text>
						<text>{{shopApplyData.settlement_bank_account_number}}</text>
					</view>
					<view class="audit-item">
						<text>结算开户银行支行名称</text>
						<text>{{shopApplyData.settlement_bank_name}}</text>
					</view>
					<view class="audit-item">
						<text>结算开户银行所在地</text>
						<text>{{shopApplyData.settlement_bank_address}}</text>
					</view>
				</block>
				<block v-if="shopApplyData.bank_type == 2">
					<view class="audit-item">
						<text>用户真实姓名</text>
						<text>{{shopApplyData.settlement_bank_account_name}}</text>
					</view>
					<view class="audit-item">
						<text>支付宝账号</text>
						<text>{{shopApplyData.settlement_bank_account_number}}</text>
					</view>
				</block>
			</view>
			
			<view class="audit-wrap">
				<text class="audit-title">店铺信息</text>
				<view class="audit-item">
					<text>店铺名称</text>
					<text>{{shopApplyData.shop_name}}</text>
				</view>
				<view class="audit-item">
					<text>城市分站</text>
					<text>{{cityName}}</text>
				</view>
				<view class="audit-item">
					<text>开店套餐</text>
					<text>{{shopApplyData.group_name}}</text>
				</view>
				<view class="audit-item">
					<text>主营行业</text>
					<text>{{shopApplyData.category_name}}</text>
				</view>
				<view class="audit-item">
					<text>入驻时长</text>
					<text>{{shopApplyData.apply_year}}年</text>
				</view>
			</view>
			
			<view class="audit-wrap" v-if="procedure==3 || procedure==7">
				<text class="audit-title">支付凭证信息</text>
				<view class="audit-item">
					<text>付款凭证</text>
					<view class="img-wrap" @click="previewImg(shopApplyData.paying_money_certificate)">
						<image :src="$util.img(shopApplyData.paying_money_certificate)" mode="aspectFit"></image>
					</view>
				</view>
				<view class="audit-item" v-if="shopApplyData.paying_money_certificate_explain">
					<text>付款凭证说明</text>
					<text>{{shopApplyData.paying_money_certificate_explain}}</text>
				</view>
			</view>
			
			<button class="audit-btn color-base-bg" type="default" v-if="auditInfo['procedure_'+procedure].btn" @click="linkSkip">{{auditInfo['procedure_'+procedure].btn}}</button>
		</block>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
export default {
	data() {
		return {
			auditInfo:
			{
				procedure_2: {
					title:'待审核',
					text: '信息审核中，请稍等 ~'
				},
				procedure_3: {
					title:'待审核',
					text: '支付凭证审核中，请稍等 ~'
				},
				procedure_4: {
					title:'审核失败',
					text: '认证信息审核失败，',
					error: '查看原因',
					btn: '重新填写'
				},
				procedure_5: {
					title:'审核通过',
					text: '恭喜您开店成功，快点击进入店铺，进行体验吧！',
					btn: '进入店铺'
				},
				procedure_6: {
					title:'审核通过',
					text: '审核通过，请填写支付凭证',
					btn: '填写支付凭证'
				},
				procedure_7: {
					title:'审核失败',
					text: '支付信息审核失败，',
					error: '查看原因',
					btn :'重新提交'
				},
			},
			procedure: '',
			shopApplyData:'',
			applyMessage: '' ,//认证失败错误提示
			cityName: ''//城市分站
		};
	},
	onShow() {
		this.getColseType();
	},
	mounted() {
		if (this.$refs.loadingCover)
			this.$refs.loadingCover.hide();
	},
	methods: {
		getColseType() {
			this.$api.sendRequest({
				url: '/shopapi/apply/index',
				success: res => {
					if (res.code == 0 && res.data) {
						this.shopApplyData = res.data.shop_apply_info;
						for(let key in res.data.web_city){
							if(res.data.web_city[key].site_id == this.shopApplyData.website_id){
								this.cityName = res.data.web_city[key].site_area_name;
							}
						}
						
						this.procedure = res.data.procedure;
						if (res.data.shop_apply_info) this.applyMessage = res.data.shop_apply_info.apply_message;
						
						if (this.$refs.loadingCover) this.$refs.loadingCover.hide();
					}
				}
			});
		},
		getApplyMessage() {
			uni.showModal({
				title: '认证失败原因',
				content: this.applyMessage,
				showCancel: false
			});
		},
		linkSkip() {
			if (this.procedure == 4) {
				this.$util.redirectTo('/pages/apply/openinfo');
			} else if (this.procedure == 6 || this.procedure == 7) {
				this.$util.redirectTo('/pages/apply/payinfo');
			} else if (this.procedure == 5) {
				this.goShop();
			}
		},
		goShop() {
			this.$api.sendRequest({
				url: '/shopapi/apply/simulatedLogin',
				success: res => {
					if (res.code == 0) {
						uni.setStorageSync('token', res.data.token);
						uni.setStorageSync('site_id', res.data.site_id);
						this.$util.redirectTo('/pages/index/index', {}, 'redirectTo');
					} else {
						this.$util.showToast({
							title: res.message
						});
					}
				}
			});
		},
		previewImg(pic_path) {
			let paths = [this.$util.img(pic_path)];
			uni.previewImage({
				current: 0,
				urls: paths
			});
		}
	}
};
</script>

<style lang="scss">
.audit-inner {
	overflow: hidden;
	padding-bottom: 170rpx;
	.audit-status{
		margin-top: 20rpx;
		padding: 40rpx;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		background-color: #fff;
	}
	.audit-wrap{
		overflow: hidden;
		.audit-title{
			display: block;
			margin-top: 20rpx;
			margin-left: 30rpx;
			height: 80rpx;
			line-height: 80rpx;
			color: $color-sub;
		}
		.audit-item{
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 0 30rpx;
			min-height: 100rpx;
			background-color: #fff;
			border-bottom: 2rpx solid $color-line;
			&:last-of-type{
				border-bottom: none;
			}
			text:last-of-type{
				overflow: hidden;
				max-width: 450rpx;
			}
		}
		.img-wrap{
			margin: 20rpx 0;
			width: 300rpx;
			height: 140rpx;
			border: 2rpx dashed $color-line;
			image{
				width: 300rpx;
				height: 140rpx;
			}
		}
		
	}
	.audit-btn{
		position: fixed;
		left: 25px;
		right: 25px;
		bottom: 25px;
		display: block;
		color: #fff;
		z-index: 5;
		border: none;
	}
}
</style>
