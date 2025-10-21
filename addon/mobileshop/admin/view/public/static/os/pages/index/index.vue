<template>
	<view>
		<!-- 店铺认证 -->
		<view class="cret_now" v-if="data.shop_info.is_reopen && (data.shop_info.is_reopen == 2 || data.shop_info.is_reopen == 3)">
			<view class="tip iconfont icongantanhao color-base-text"></view>
			<view class="text">
				<view v-if="data.shop_info.is_reopen == 2">店铺即将到期，请尽快续费</view>
				<view v-else-if="data.shop_info.is_reopen == 3">{{ data.shop_info.shop_status == 0 ? '店铺已暂停服务，无法正常营业' : '店铺已经过期，请尽快续费' }}</view>
				<view class="color-base-text">
					店铺剩余{{ data.shop_info.expires_date }}天到期
					<text v-if="data.shop_info.is_reopen == 3">（已到期）</text>
				</view>
			</view>
			<view class="cret_btn color-base-bg" @click="getProcedureState">{{ data.shop_info.cert_id == 0 ? '立即认证' : data.is_reopen == 1 ? '立即续费' : '立即续费' }}</view>
		</view>
		<view class="shop" :style="{ backgroundImage: 'url(' + $util.img('upload/uniapp/shop_uniapp/shop_bg.png') + ')' }">
			<!-- 店铺基本信息 -->
			<view class="shop_base_info" @click="$util.redirectTo('/pages/my/shop/config')">
				<view class="shop_img">
					<image
						:src="data.shop_info.logo ? $util.img(data.shop_info.logo) : $util.img($util.getDefaultImage().default_headimg)"
						@error="imgError()"
						mode="aspectFit"
					></image>
				</view>
				<view class="shop_info">
					<view class="shop_title">
						<text class="title">{{ data.shop_info.site_name }}</text>
						<text class="tag">{{ data.shop_info.group_name }}</text>
						<text class="tag" @click="toCert">{{ data.shop_info.cert_id == 0 ? '未认证' : '已认证' }}</text>
					</view>
					<view class="shop_other_info">
						<text class="margin-right">主营行业：{{ data.shop_info.category_name }}</text>
						<text>店铺状态：{{ data.shop_info.shop_status == 1 ? '正常' : '关闭' }}</text>
					</view>
					<view class="shop_other_info">
						<text>到期时间：{{ data.shop_info.expire_time == 0 ? '永久' : $util.timeStampTurnTime(data.shop_info.expire_time) }}</text>
					</view>
				</view>
				<text class="weixincode iconfont iconrichscan_icon" @click.stop="$util.redirectTo('/pages/verify/index')"></text>
			</view>
			<!-- 数据概况 -->
			<view class="trading_statistics margin_none">
				<view class="title">
					<view class="title_left">数据概况</view>
					<view class="title_right color-base-border">
						<text @click="transactionChange('stat_day')" :class="{ active: transaction_statistics == 'stat_day' }">今日</text>
						<text @click="transactionChange('stat_yesterday')" :class="{ active: transaction_statistics == 'stat_yesterday' }">昨日</text>
						<text @click="transactionChange('shop_stat_sum')" :class="{ active: transaction_statistics == 'shop_stat_sum' }">总计</text>
					</view>
				</view>
				<view class="content">
					<view>
						<view class="color-tip">订单数</view>
						<view class="num">{{ data[transaction_statistics].order_pay_count }}</view>
					</view>
					<view>
						<view class="color-tip">销售额（元）</view>
						<view class="num">{{ data[transaction_statistics].order_total }}</view>
					</view>
					<view>
						<view class="color-tip">店铺收藏数</view>
						<view class="num">{{ data[transaction_statistics].collect_shop }}</view>
					</view>
					<!-- <view>
						<view class="color-tip">浏览量</view>
						<view class="num">{{ data[transaction_statistics].visit_count }}</view>
					</view> -->
				</view>
			</view>
			<!-- 公告 -->
			<view class="notice" v-if="data.notice_list.length > 0">
				<text class="iconfont icongonggao color-base-text font-size-sub"></text>
				<swiper class="swiper" autoplay="true" vertical="true">
					<swiper-item v-for="(item, index) in data.notice_list" :key="index" class="swiperitem" @click="toNoticeDetail(item.id)">
						<view class="title">{{ item.title }}</view>
						<!-- <view class="time">{{ $util.timeStampTurnTime(item.create_time, 1) }}</view> -->
					</swiper-item>
				</swiper>
				<view class="more color-base-text" @click="$util.redirectTo('/pages/notice/list')">更多</view>
			</view>
			<!-- 待处理事项 -->
			<view class="trading_statistics padding">
				<view class="grid margin-top order">
					<uni-grid :column="5" :showBorder="!1">
						<uni-grid-item>
							<view @click="pendingLink('/pages/order/list', 'order_id', 0)" class="grid_item">
								<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/wating_pay.png')" mode="aspectFit" />
								<text class="num" v-if="data.num_data.waitpay > 0">{{ data.num_data.waitpay }}</text>
								<view class="text">待支付</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="pendingLink('/pages/order/list', 'order_id', 1)" class="grid_item">
								<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/wating_send.png')" mode="aspectFit" />
								<text class="num" v-if="data.num_data.waitsend > 0">{{ data.num_data.waitsend }}</text>
								<view class="text">待发货</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="pendingLink('/pages/order/list', 'order_id', 'refunding')" class="grid_item">
								<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/return_money.png')" mode="aspectFit" />
								<text class="num" v-if="data.num_data.refund > 0">{{ data.num_data.refund }}</text>
								<view class="text">退款中</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="pendingLink('/pages/goods/list', 'status', '2')" class="grid_item">
								<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/stock_warn.png')" mode="aspectFit" />
								<text class="num" v-if="data.num_data.wait_audit_count > 0">{{ data.num_data.wait_audit_count }}</text>
								<view class="text">待审核</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="pendingLink('/pages/goods/list', 'status', '4')" class="grid_item">
								<image class="image" :src="$util.img('upload/uniapp/shop_uniapp/index/xiajia.png')" mode="aspectFit" />
								<text class="num" v-if="data.num_data.audit_refuse_count > 0">{{ data.num_data.audit_refuse_count }}</text>
								<view class="text">违规下架</view>
							</view>
						</uni-grid-item>
					</uni-grid>
				</view>
			</view>
			<!-- 常用功能 -->
			<view class="trading_statistics padding">
				<view class="grid">
					<uni-grid :column="4" :showBorder="!1">
						<uni-grid-item>
							<view @click="$util.redirectTo('/pages/goods/edit/index')" class="grid_item">
								<image class="image50" :src="$util.img('upload/uniapp/shop_uniapp/index/manage_good_send.png')" mode="aspectFit" />
								<view class="text">商品发布</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="$util.redirectTo('/pages/order/list')" class="grid_item">
								<image class="image50" :src="$util.img('upload/uniapp/shop_uniapp/index/manage_order.png')" mode="aspectFit" />
								<view class="text">订单查询</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="$util.redirectTo('/pages/property/dashboard/index')" class="grid_item">
								<image class="image50" :src="$util.img('upload/uniapp/shop_uniapp/index/finance_survey.png')" mode="aspectFit" />
								<view class="text">财务状况</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="$util.redirectTo('/pages/statistics/transaction')" class="grid_item">
								<image class="image50" :src="$util.img('upload/uniapp/shop_uniapp/index/tongji_jiaoyi.png')" mode="aspectFit" />
								<view class="text">交易分析</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="$util.redirectTo('/pages/property/settlement/list')" class="grid_item">
								<image class="image50" :src="$util.img('upload/uniapp/shop_uniapp/my/store.png')" mode="aspectFit" />
								<view class="text">店铺结算</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="$util.redirectTo('/pages/member/list')" class="grid_item">
								<image class="image50" :src="$util.img('upload/uniapp/shop_uniapp/index/set_member.png')" mode="aspectFit" />
								<view class="text">会员管理</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="$util.redirectTo('/pages/my/shop/contact')" class="grid_item">
								<image class="image50" :src="$util.img('upload/uniapp/shop_uniapp/index/set_address.png')" mode="aspectFit" />
								<view class="text">联系地址</view>
							</view>
						</uni-grid-item>
						<uni-grid-item>
							<view @click="$util.redirectTo('/pages/index/all_menu')" class="grid_item">
								<image class="image50" :src="$util.img('upload/uniapp/shop_uniapp/index/more.png')" mode="aspectFit" />
								<view class="text">全部</view>
							</view>
						</uni-grid-item>
					</uni-grid>
				</view>
			</view>
			<!-- 常用功能 -->
			<block v-for="(item, index) in arr" :key="index">
				<view class="trading_statistics padding">
					<view class="title">
						<view class="title_left">
							{{ item.title }}
							<text v-if="item.opts.unit">({{ item.opts.unit }})</text>
							<text class=" total color-base-text">{{ total_money[item.id] }}</text>
						</view>
						<picker :value="pickerCurr[item.id]" @change="pickerChange(item.id, $event)" :range="picker" range-key="date_text">
							<view class="select ">
								{{ picker[pickerCurr[item.id]].date_text }}
								<text class="iconfont iconiconangledown"></text>
							</view>
						</picker>
					</view>
					<view class="ucharts">
						<view @click="refCurr = item.id">
							<uCharts
								:scroll="item.opts.enableScroll"
								:show="canvas"
								:canvasId="item.id"
								:chartType="item.chartType"
								:extraType="item.extraType"
								:cWidth="cWidth"
								:cHeight="cHeight"
								:opts="item.opts"
								:ref="item.id"
							/>
						</view>
					</view>
				</view>
			</block>
		</view>

		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import uniGrid from '@/components/uni-grid/uni-grid.vue';
import uniGridItem from '@/components/uni-grid-item/uni-grid-item.vue';
import uCharts from '@/components/u-charts/u-charts.vue';
import index from './js/index.js';
export default {
	mixins: [index],
	components: {
		uniGrid,
		uniGridItem,
		uCharts
	}
};
</script>

<style lang="scss">
@import './css/index.scss';
</style>
