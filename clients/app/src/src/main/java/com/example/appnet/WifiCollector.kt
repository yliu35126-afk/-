package com.example.appnet

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.net.wifi.ScanResult
import android.net.wifi.WifiManager
import androidx.core.content.ContextCompat

data class WifiAccessPoint(
    val macAddress: String,
    val signalStrength: Int
)

object WifiCollector {
    fun collect(context: Context): List<WifiAccessPoint> {
        val wifiManager = context.applicationContext.getSystemService(Context.WIFI_SERVICE) as WifiManager
        val hasPermFine = ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
        val hasPermCoarse = ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED
        if (!hasPermFine && !hasPermCoarse) {
            return emptyList()
        }
        return try {
            val results: List<ScanResult> = wifiManager.scanResults ?: emptyList()
            results.mapNotNull { sr ->
                val bssid = sr.BSSID ?: return@mapNotNull null
                WifiAccessPoint(macAddress = bssid, signalStrength = sr.level)
            }
        } catch (e: Throwable) {
            emptyList()
        }
    }
}