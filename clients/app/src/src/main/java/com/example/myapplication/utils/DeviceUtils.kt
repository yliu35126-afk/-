package com.example.myapplication.utils

import android.annotation.SuppressLint
import android.content.Context
import android.os.Build
import android.provider.Settings
import android.telephony.TelephonyManager
import androidx.core.content.ContextCompat
import java.security.MessageDigest
import java.util.*

/**
 * 设备信息工具类
 */
object DeviceUtils {
    
    /**
     * 获取设备唯一标识码
     * 使用Android ID生成固定的设备ID
     */
    @SuppressLint("HardwareIds")
    fun getDeviceId(context: Context): String {
        return try {
            android.util.Log.d("DeviceUtils", "开始获取设备Android ID")
            
            // 获取Android ID（在同一台设备上固定不变）
            val androidId = Settings.Secure.getString(
                context.contentResolver,
                Settings.Secure.ANDROID_ID
            )
            
            android.util.Log.d("DeviceUtils", "Android ID获取结果: ${if (androidId.isNullOrBlank()) "空" else "非空(${androidId.length}位)"}")
            
            if (!androidId.isNullOrBlank() && androidId != "9774d56d682e549c") {
                val deviceCode = "DEV_${androidId.uppercase()}"
                android.util.Log.d("DeviceUtils", "生成设备码: $deviceCode")
                deviceCode
            } else {
                android.util.Log.w("DeviceUtils", "Android ID无效")
                "DEV_ANDROID_ID_INVALID"
            }
            
        } catch (e: Exception) {
            android.util.Log.e("DeviceUtils", "获取Android ID异常: ${e.message}", e)
            "DEV_ANDROID_ID_ERROR"
        }
    }
    
    /**
     * 获取设备基本信息
     */
    fun getDeviceInfo(): Map<String, String> {
        return mapOf(
            "brand" to Build.BRAND,
            "model" to Build.MODEL,
            "manufacturer" to Build.MANUFACTURER,
            "device" to Build.DEVICE,
            "product" to Build.PRODUCT,
            "android_version" to Build.VERSION.RELEASE,
            "sdk_version" to Build.VERSION.SDK_INT.toString()
        )
    }
    
    /**
     * 获取安装位置信息（模拟）
     */
    fun getInstallLocation(): String {
        // 这里可以根据实际需求获取GPS位置或用户输入
        return "Android设备"
    }
    
    /**
     * 获取商户信息（可配置）
     */
    fun getMerchantInfo(): String {
        return "抽奖应用商户"
    }
}
