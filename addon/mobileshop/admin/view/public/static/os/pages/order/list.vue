<template>
	<view class="order-list-wrap">
		<view class="nav-wrap">
			<text class="selected color-base-bg color-base-border">订单列表</text>
			<text class="color-base-border" @click="goRefundOrderList()">退款维权</text>
		</view>
		<view class="search-wrap" v-if="orderCondition.order_label_list.length > 0">
			<view class="search-input-inner">
				<text class="search-input-icon iconfont iconsousuo" @click="searchOrder()"></text>
				<input
					class="search-input-text font-size-tag"
					maxlength="50"
					v-model="formData.search"
					:placeholder="'请输入' + orderCondition.order_label_list[orderConditionCurr.order_label_list].name"
					@confirm="searchOrder()"
				/>
			</view>
		</view>
		<view class="tab-block">
			<scroll-view scroll-x="true" class="scroll tab-wrap" :scroll-into-view="'tab' + orderConditionCurr.order_status_list" scroll-with-animation="true">
				<!-- #ifdef MP -->
				<view class="tab-item" @click="tabChange(0)" :class="{'active color-base-text color-base-bg-before':orderConditionCurr.order_status_list == 0,iphone:!$util.isAndroid()}" id="tab0">
					<text>全部</text>
				</view>
				<block v-for="(item, index) in orderCondition.order_status_list" :key="index">
					<view
						class="tab-item"
						@click="tabChange(index + 1)"
						:class="{'active color-base-text color-base-bg-before':index + 1 == orderConditionCurr.order_status_list,iphone:!$util.isAndroid()}"
						:id="'tab' + (index + 1)"
					>
						<text>{{ item.name }}</text>
					</view>
				</block>
				<!-- #endif -->
				<!-- #ifndef MP -->
				<view class="tab-item" @click="tabChange(0)" :class="{'active color-base-text color-base-bg-before':orderConditionCurr.order_status_list == 0}" id="tab0">
					<text>全部</text>
				</view>
				<block v-for="(item, index) in orderCondition.order_status_list" :key="index">
					<view
						class="tab-item"
						@click="tabChange(index + 1)"
						:class="{'active color-base-text color-base-bg-before':index + 1 == orderConditionCurr.order_status_list}"
						:id="'tab' + (index + 1)"
					>
						<text>{{ item.name }}</text>
					</view>
				</block>
				<!-- #endif -->
				
			</scroll-view>
			<view class="choose" @click="showScreen = true">
				<text>筛选</text>
				<text class="iconfont iconshaixuan color-tip"></text>
			</view>
		</view>

		<!-- 筛选弹出框 -->
		<uni-drawer :visible="showScreen" mode="right" @close="showScreen = false" class="screen-wrap">
			<view class="title color-tip">筛选</view>
			<scroll-view scroll-y="true">
				<view class="item-wrap" v-if="orderCondition.order_label_list.length > 0">
					<view class="label">
						<text>搜索方式</text>
						<view class="more">
							<picker @change="bindPickerChange" :value="orderConditionCurr.order_label_list" :range="orderCondition.order_label_list" range-key="name">
								<view class="uni-input">{{ orderCondition.order_label_list[orderConditionCurr.order_label_list].name }}</view>
							</picker>
							<text class="iconfont iconright"></text>
						</view>
					</view>
					<view class="value-wrap">
						<input class="uni-input" :placeholder="'请输入' + orderCondition.order_label_list[orderConditionCurr.order_label_list].name" v-model="formData.search" />
					</view>
				</view>
				<view class="item-wrap">
					<view class="label">
						<text>下单时间</text>
						<view class="more">
							<uni-tag :inverted="true" text="近7天" @click="dateSelect(7)" />
							<uni-tag :inverted="true" text="近30天" @click="dateSelect(30)" />
						</view>
					</view>
					<view class="value-wrap">
						<picker mode="date" @change="bindTimeStartChange" class="picker margin-right" :value="formData.start_time">
							<view class="uni-input">{{ formData.start_time ? formData.start_time : '开始时间' }}</view>
						</picker>
						<view class="h-line"></view>
						<picker mode="date" @change="bindTimeEndChange" class="picker margin-left" :value="formData.end_time">
							<view class="uni-input">{{ formData.end_time ? formData.end_time : '结束时间' }}</view>
						</picker>
					</view>
				</view>
				<view class="item-wrap">
					<view class="label"><text>订单类型</text></view>
					<view class="list">
						<block v-for="(item, index) in orderCondition.order_type_list" :key="index">
							<uni-tag
								:inverted="true"
								:text="item.name"
								:type="index == orderConditionCurr.order_type_list ? 'primary' : 'default'"
								@click="uTag(index, 'order_type_list', 'order_type')"
							/>
						</block>
					</view>
				</view>
				<view class="item-wrap">
					<view class="label"><text>订单状态</text></view>
					<view class="list">
						<uni-tag
							:inverted="true"
							text="全部"
							:type="orderConditionCurr.order_status_list == 0 ? 'primary' : 'default'"
							@click="uTag(0, 'order_status_list', 'order_status')"
						/>
						<block v-for="(item, index) in orderCondition.order_status_list" :key="index">
							<uni-tag
								:inverted="true"
								:text="item.name"
								:type="index + 1 == orderConditionCurr.order_status_list ? 'primary' : 'default'"
								@click="uTag(index + 1, 'order_status_list', 'order_status')"
							/>
						</block>
					</view>
				</view>
				<view class="item-wrap">
					<view class="label"><text>营销类型</text></view>
					<view class="list">
						<uni-tag
							:inverted="true"
							text="全部"
							:type="orderConditionCurr.promotion_type == 0 ? 'primary' : 'default'"
							@click="uTag(0, 'promotion_type', 'promotion_type')"
						/>
						<block v-for="(item, index) in orderCondition.promotion_type" :key="index">
							<uni-tag
								:inverted="true"
								:text="item.name"
								:type="index + 1 == orderConditionCurr.promotion_type ? 'primary' : 'default'"
								@click="uTag(index + 1, 'promotion_type', 'promotion_type')"
							/>
						</block>
					</view>
				</view>
				<view class="item-wrap">
					<view class="label"><text>付款方式</text></view>
					<view class="list">
						<uni-tag :inverted="true" text="全部" :type="orderConditionCurr.pay_type_list == 0 ? 'primary' : 'default'" @click="uTag(0, 'pay_type_list', 'pay_type')" />
						<block v-for="(item, index) in orderCondition.pay_type_list" :key="index">
							<uni-tag
								:inverted="true"
								:text="item.name"
								:type="orderConditionCurr.pay_type_list == index + 1 ? 'primary' : 'default'"
								@click="uTag(index + 1, 'pay_type_list', 'pay_type')"
							/>
						</block>
					</view>
				</view>
				<view class="item-wrap">
					<view class="label"><text>订单来源</text></view>
					<view class="list">
						<uni-tag
							:inverted="true"
							text="全部"
							:type="orderConditionCurr.order_from_list == 0 ? 'primary' : 'default'"
							@click="uTag(0, 'order_from_list', 'order_from')"
						/>
						<block v-for="(item, index) in orderCondition.order_from_list" :key="index">
							<uni-tag
								:inverted="true"
								:text="item.name"
								:type="orderConditionCurr.order_from_list == index + 1 ? 'primary' : 'default'"
								@click="uTag(index + 1, 'order_from_list', 'order_from')"
							/>
						</block>
					</view>
				</view>
				<view class="item-wrap">
					<view class="label"><text>结算状态</text></view>
					<view class="list">
						<block v-for="(item, index) in settlement_list" :key="index">
							<uni-tag
								:inverted="true"
								:text="item.name"
								:type="orderConditionCurr.settlement_list == index ? 'primary' : 'default'"
								@click="uTag(index, 'settlement_list', 'settlement_state')"
							/>
						</block>
					</view>
				</view>
			</scroll-view>
			<view class="footer">
				<button type="default" @click="resetData">重置</button>
				<button type="primary" @click="screenData">确定</button>
			</view>
		</uni-drawer>

		<mescroll-uni class="list-wrap" @getData="getListData" top="340" ref="mescroll" :size="8">
			<block slot="list">
				<block v-if="orderList.length > 0">
					<view class="order-item" v-for="(item, index) in orderList" :key="index" @click="toDetail(item)">
						<view class="head">
							<!-- 订单号: -->
							<text class="order-no">{{ item.order_no }}</text>
							<text class="pay-type-name color-base-text" v-if="item.pay_type_name">{{ item.pay_type_name }}</text>
							<text class="order-status">{{ item.order_status_name }}</text>
							<text class="promotion-type-name" v-if="item.promotion_type_name">
								{{ item.promotion_type_name }}
								<template v-if="item.promotion_status_name">
									({{ item.promotion_status_name }})
								</template>
							</text>
						</view>
						<view class="goods-item" v-for="(goods, goodsIndex) in item.order_goods" :key="goodsIndex">
							<view class="goods-img"><image :src="$util.img(goods.sku_image, { size: 'mid' })" mode="widthFix" @error="imgError(index, goodsIndex)"></image></view>
							<view class="info-wrap">
								<view class="name-wrap">{{ goods.goods_name }}</view>
								<view class="spec-wrap" v-if="goods.sku_spec_format">
									<block v-for="(x, i) in goods.sku_spec_format" :key="i">{{ x.spec_value_name }} {{ i < goods.sku_spec_format.length - 1 ? '; ' : '' }}</block>
								</view>
								<view class="more-wrap">
									<view class="goods-class">{{ goods.goods_class_name }}</view>
									<text class="present-label color-base-bg" v-if="goods.is_present == 1">赠品</text>
									<view class="price-wrap">
										<view class="price">
											<text class="unit">￥</text>
											<text>{{ goods.price }}</text>
										</view>
										<view class="num">x{{ goods.num }}</view>
									</view>
								</view>
							</view>
							<view class="action-wrap" v-if="goods.refund_status != 0" @click.stop="goRefund(goods.order_goods_id)">
								<button type="primary" size="mini">{{ goods.refund_status_name }}</button>
							</view>
						</view>
						<view class="total-wrap">
							<text class="create-time">下单时间：{{ $util.timeStampTurnTime(item.create_time) }}</text>
							<text class="label">合计：</text>
							<view class="price color-base-text">
								<text class="unit">￥</text>
								<text>{{ item.order_money }}</text>
							</view>
						</view>
						<view class="member-wrap">
							<view v-if="item.order_type != 4">
								<text class="nick-name" v-if="item.nickname">{{ item.nickname }}</text>
								<text class="name">{{ item.name }}</text>
								<view class="mobile-wrap" @click.stop="makePhoneCall(item.mobile)">
									<text class="iconfont icondianhua color-base-text"></text>
									<text class="mobile color-base-text">{{ item.mobile }}</text>
								</view>
								<view class="address">{{ item.full_address }} {{ item.address }}</view>
							</view>
							<view v-else>{{ item.nickname }} {{ item.mobile }}</view>
						</view>
						<view class="remark-wrap color-base-bg-light color-base-text" v-if="item.remark">卖家备注：{{ item.remark }}</view>
						<view class="order-action-wrap">
							<button type="primary" size="mini" plain @click.stop="orderRemark(item)">备注</button>
							<button
								type="primary"
								size="mini"
								v-for="(actionItem, actionIndex) in item.order_status_action.action"
								:key="actionIndex"
								@click.stop="orderAction(actionItem.action, item.order_id)"
							>
								{{ actionItem.title }}
							</button>
							<button v-if="item.order_status == 0" type="primary" size="mini" @click.sotp="offlinePay(item.order_id)">线下支付</button>
							<button v-if="item.order_type == 2 && item.order_status == 2" type="primary" size="mini" @click.stop="storeOrderTakedelivery(item.order_id)">
								提货
							</button>
						</view>
					</view>
				</block>
				<ns-empty v-else text="暂无订单数据"></ns-empty>
			</block>
		</mescroll-uni>
		<ns-order-remark ref="orderRemark" :order="order"></ns-order-remark>
		<loading-cover ref="loadingCover"></loading-cover>
	</view>
</template>

<script>
import uniDrawer from '@/components/uni-drawer/uni-drawer.vue';
import nsOrderRemark from '@/components/ns-order-remark/ns-order-remark';
import uniTag from '@/components/uni-tag/uni-tag.vue';
import list from './js/list.js';
import action from './js/action.js';
export default {
	components: {
		uniDrawer,
		uniTag,
		nsOrderRemark
	},
	mixins: [list, action]
};
</script>

<style lang="scss">
@import './css/list.scss';
</style>
