package com.example.myapplication.model

import com.google.gson.annotations.SerializedName
import com.google.gson.JsonElement

// 二维码生成请求参数
data class QrCodeGenerateRequest(
    @SerializedName("device_code")
    val deviceCode: String,
    @SerializedName("page")
    val page: String? = null
)

// 二维码生成响应 data 字段
data class QrCodeGenerateData(
    @SerializedName("device_code")
    val deviceCode: String? = null,
    @SerializedName("qrcode_url")
    val qrcodeUrl: String = "",
    @SerializedName("scene")
    val scene: String? = null,
    @SerializedName("expire_time")
    val expireTime: Long? = null
)

// 二维码生成响应整体
data class QrCodeGenerateResponse(
    @SerializedName("code")
    val code: Int,
    @SerializedName("msg")
    val msg: JsonElement?,        // 兼容字符串或对象
    @SerializedName("data")
    val data: QrCodeGenerateData?, // 实际二维码信息在 data 中
    @SerializedName("time")
    val time: String?             // 兼容后端示例中的 time 字段
)
