package com.example.myapplication.utils

import android.media.AudioDeviceInfo
import android.media.AudioManager
import android.os.Build
import android.content.Context

/**
 * 根据当前音频输出路由，返回一个用于音画对齐的建议延时（毫秒）。
 * - 蓝牙 A2DP/SCO：增加约120ms
 * - 有线耳机：增加约40ms
 * - 扬声器/其它：0ms
 */
object AudioRouteUtils {
    fun computeRouteDelayMs(context: Context): Long {
        return try {
            val am = context.getSystemService(Context.AUDIO_SERVICE) as AudioManager
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                val devices = am.getDevices(AudioManager.GET_DEVICES_OUTPUTS)
                var hasBt = false
                var hasWired = false
                devices.forEach { d ->
                    when (d.type) {
                        AudioDeviceInfo.TYPE_BLUETOOTH_A2DP,
                        AudioDeviceInfo.TYPE_BLUETOOTH_SCO -> hasBt = true
                        AudioDeviceInfo.TYPE_WIRED_HEADPHONES,
                        AudioDeviceInfo.TYPE_WIRED_HEADSET -> hasWired = true
                    }
                }
                when {
                    hasBt -> 120L
                    hasWired -> 40L
                    else -> 0L
                }
            } else {
                // 旧设备：使用已废弃API兜底判断
                val bt = am.isBluetoothScoOn || am.isBluetoothA2dpOn
                val wired = am.isWiredHeadsetOn
                when {
                    bt -> 120L
                    wired -> 40L
                    else -> 0L
                }
            }
        } catch (_: Exception) { 0L }
    }
}