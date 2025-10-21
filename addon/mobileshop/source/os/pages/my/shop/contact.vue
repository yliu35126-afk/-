<template>
	<view>
		<view class="container-wrap">
			<view class="item-wrap">
				<view class="form-wrap">
					<view class="label">
						<text class="required color-base-text">*</text>
						<text>联系人姓名</text>
					</view>
					<input class="uni-input" placeholder="请输入联系人姓名" v-model="shopInfo.name" maxlength="100" />
				</view>
				<view class="form-wrap">
					<view class="label">
						<text class="required color-base-text">*</text>
						<text>联系人手机号</text>
					</view>
					<input class="uni-input" placeholder="请输入联系人手机号" type="number" v-model="shopInfo.mobile" maxlength="100" />
				</view>
				<view class="form-wrap">
					<text class="label">联系人电话</text>
					<input class="uni-input" placeholder="请输入联系人电话" v-model="shopInfo.telephone" maxlength="100" />
				</view>
				<view class="form-wrap">
					<text class="label">联系地址</text>
					<input class="uni-input" placeholder="请选择省市区" v-model="shopInfo.full_address" maxlength="100" disabled="" @click="selectAddress" />
				</view>
				<view class="form-wrap">
					<text class="label">详细地址</text>
					<input class="uni-input" placeholder="请输入详细地址" v-model="shopInfo.address" maxlength="100" />
				</view>
				<view class="form-wrap">
					<text class="label">QQ号</text>
					<input class="uni-input" placeholder="请输入QQ号" v-model="shopInfo.qq" maxlength="100" />
				</view>
				<view class="form-wrap">
					<text class="label">阿里旺旺</text>
					<input class="uni-input" placeholder="请输入阿里旺旺" v-model="shopInfo.ww" maxlength="100" />
				</view>
				<view class="form-wrap">
					<text class="label">邮箱</text>
					<input class="uni-input" placeholder="请输入邮箱" v-model="shopInfo.email" maxlength="100" />
				</view>
				<view class="form-wrap" @click="openWeek()">
					<text class="label">工作日</text>
					<text class="value">{{ shopInfo.work_week ? (work_weekText ? work_weekText : '已选择') : '请选择工作日' }}</text>
					<text class="iconfont iconright color-tip"></text>
				</view>
				<view class="form-wrap">
					<text class="label">营业时间</text>
					<view class="time-change">
						<picker mode="time" @change="bindStartDateChange" class="padding-left padding-right" :value="timeHourMinute(shopInfo.start_time)">
							<view class="uni-input">{{ shopInfo.start_time ? timeHourMinute(shopInfo.start_time) : '开始时间' }}</view>
						</picker>
						-
						<picker mode="time" @change="bindEndDateChange" class="padding-left padding-right" :value="timeHourMinute(shopInfo.end_time)">
							<view class="uni-input">{{ shopInfo.end_time ? timeHourMinute(shopInfo.end_time) : '结束时间' }}</view>
						</picker>
					</view>
				</view>
			</view>
		</view>
		<view class="footer-wrap"><button type="primary" @click="save()">保存</button></view>
		<uni-popup ref="openWeek" type="bottom">
			<view class="week-list iphone-safe-area" @touchmove.prevent.stop>
				<view class="title">选择工作日</view>
				<view class="flex" @click="selectedChange(item.value)" v-for="(item, index) in week_list" :key="index">
					<view class="flex-left">{{ item.name }}</view>
					<view class="flex-right"><text class="iconfont iconyuan_checked" :class="{ 'color-base-text': item.is_select, 'color-tip': !item.is_select }"></text></view>
				</view>
				<button class="btn margin-top" type="primary" @click="selectedChangeBtn">确定</button>
			</view>
		</uni-popup>
	</view>
</template>

<script>
import contact from '../js/contact.js';
export default {
	data() {
		return {};
	},
	mixins: [contact]
};
</script>

<style lang="scss">
@import '../css/edit.scss';
.week-list {
	padding-bottom: 30rpx;
	.title {
		text-align: center;
		padding: $padding;
		font-size: $font-size-sub;
	}
	.flex {
		display: flex;
		margin: 0 $margin-both;
		padding: $padding;
		border-bottom: 1px solid $color-line;
		&:nth-last-of-type(1){
			border-bottom: 0 !important;
		}
		.flex-left {
			flex: 1;
		}
	}
	
}
</style>
