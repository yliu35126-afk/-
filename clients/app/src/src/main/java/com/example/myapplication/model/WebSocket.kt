package com.example.myapplication.model

import com.google.gson.annotations.SerializedName

/**
 * 抽奖指令数据模型
 */
data class LotteryCommand(
    @SerializedName("user_id")
    val userId: String,
    
    @SerializedName("device_id")
    val deviceId: String,
    
    @SerializedName("lottery_type")
    val lotteryType: Int, // 1=投币, 2=扫码, 3=免费
    
    @SerializedName("user_name")
    val userName: String? = null, // 可选
    
    @SerializedName("user_phone") 
    val userPhone: String? = null, // 可选
    
    @SerializedName("order_id")
    val orderId: Int,
    
    @SerializedName("order_amount")
    val orderAmount: Double,
    
    @SerializedName("config_id")
    val configId: String, // 🔥 新增：分润配置ID
    
    @SerializedName("timestamp")
    val timestamp: String
)

/**
 * 奖品信息
 */
data class PrizeInfo(
    @SerializedName("id")
    val id: Int,
    
    @SerializedName("name")
    val name: String,
    
    @SerializedName("image")
    val image: String
)

/**
 * 抽奖结果数据模型
 */
data class LotteryResult(
    @SerializedName("user_id")
    val userId: String,
    
    @SerializedName("result")
    val result: String, // "win" | "lose" | "error"
    
    @SerializedName("prize_info")
    val prizeInfo: PrizeInfo? = null, // 中奖时必填
    
    @SerializedName("lottery_record_id")
    val lotteryRecordId: String? = null // 可选
)

/**
 * 心跳数据
 */
data class HeartbeatData(
    @SerializedName("timestamp")
    val timestamp: Long = System.currentTimeMillis(),
    
    @SerializedName("device_id")
    val deviceId: String
)
