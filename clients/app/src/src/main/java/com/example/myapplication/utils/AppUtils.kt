package com.example.myapplication.utils

import android.content.Context
import android.widget.Toast

/**
 * 应用工具类
 */
object AppUtils {
    
    /**
     * 显示设备信息Toast
     */
    fun showDeviceInfo(context: Context) {
        val deviceId = DeviceUtils.getDeviceId(context)
        val deviceInfo = DeviceUtils.getDeviceInfo()
        
        val message = """
            设备ID: $deviceId
            品牌: ${deviceInfo["brand"]}
            型号: ${deviceInfo["model"]}
            制造商: ${deviceInfo["manufacturer"]}
            Android版本: ${deviceInfo["android_version"]}
        """.trimIndent()
        
        Toast.makeText(context, message, Toast.LENGTH_LONG).show()
    }
    
    /**
     * 复制设备ID到剪贴板
     */
    fun copyDeviceIdToClipboard(context: Context) {
        val deviceId = DeviceUtils.getDeviceId(context)
        val clipboard = context.getSystemService(Context.CLIPBOARD_SERVICE) as android.content.ClipboardManager
        val clip = android.content.ClipData.newPlainText("设备ID", deviceId)
        clipboard.setPrimaryClip(clip)
        Toast.makeText(context, "设备ID已复制: $deviceId", Toast.LENGTH_SHORT).show()
    }
}
