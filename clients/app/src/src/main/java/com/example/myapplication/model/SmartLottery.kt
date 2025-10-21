package com.example.myapplication.model

import com.google.gson.annotations.SerializedName

/**
 * 智能抽奖请求
 */
data class SmartLotteryRequest(
    @SerializedName("device_id")
    val deviceId: String,
    @SerializedName("config_id")
    val configId: String
)

/**
 * 智能抽奖响应数据
 */
data class SmartLotteryData(
    @SerializedName("device_id")
    val deviceId: String,
    @SerializedName("config_id")
    val configId: Int,
    @SerializedName("config_name")
    val configName: String,
    @SerializedName("lottery_amount")
    val lotteryAmount: Double,
    @SerializedName("prize_id")
    val prizeId: Int,
    @SerializedName("prize_name")
    val prizeName: String,
    @SerializedName("prize_type")
    val prizeType: String,
    @SerializedName("prize_price")
    val prizePrice: Double,
    @SerializedName("prize_image")
    val prizeImage: String,
    @SerializedName("original_probability")
    val originalProbability: Double,
    @SerializedName("actual_probability")
    val actualProbability: Double,
    @SerializedName("adjustment_reason")
    val adjustmentReason: String,
    @SerializedName("total_plays_today")
    val totalPlaysToday: Int,
    @SerializedName("total_wins_today")
    val totalWinsToday: Int,
    @SerializedName("win_rate_today")
    val winRateToday: Double,
    @SerializedName("random_value")
    val randomValue: Double
)

/**
 * 智能抽奖响应
 */
data class SmartLotteryResponse(
    val code: Int,
    val msg: String,
    val time: Long,
    val data: SmartLotteryData?
)