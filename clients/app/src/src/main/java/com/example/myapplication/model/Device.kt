package com.example.myapplication.model

import com.google.gson.annotations.SerializedName

/**
 * 设备注册请求参数
 */
data class DeviceRegisterRequest(
    @SerializedName("device_id")
    val deviceId: String,
    
    @SerializedName("install_location")
    val installLocation: String? = null,
    
    @SerializedName("merchant")
    val merchant: String? = null,
    
    @SerializedName("purchase_info")
    val purchaseInfo: String? = null,
    
    @SerializedName("is_debug")
    val isDebug: Int = 0 // 0=正式模式, 1=调试模式
)

/**
 * 设备注册响应数据
 */
data class DeviceRegisterData(
    @SerializedName("device_id")
    val deviceId: String,
    
    @SerializedName("status")
    val status: String, // "created" 或 "updated"
    
    @SerializedName("message")
    val message: String
)

/**
 * 设备注册响应
 */
data class DeviceRegisterResponse(
    @SerializedName("code")
    val code: Int,
    
    @SerializedName("msg")
    val msg: String,
    
    @SerializedName("time")
    val time: Long,
    
    @SerializedName("data")
    val data: DeviceRegisterData
)
