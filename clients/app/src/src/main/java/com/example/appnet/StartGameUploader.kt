package com.example.appnet

import android.content.Context
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import org.json.JSONArray
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

object StartGameUploader {
    suspend fun upload(context: Context, baseUrl: String, deviceId: String) : Boolean {
        val wifi = WifiCollector.collect(context)
        val wifiJson = JSONArray()
        wifi.forEach { ap ->
            val obj = JSONObject()
            obj.put("macAddress", ap.macAddress)
            obj.put("signalStrength", ap.signalStrength)
            wifiJson.put(obj)
        }
        val payload = JSONObject()
        payload.put("device_id", deviceId)
        // ip_address 可不传，后端会用请求源IP；仍保留字段以兼容
        payload.put("wifi_access_points", wifiJson)

        return withContext(Dispatchers.IO) {
            try {
                val url = URL("$baseUrl/index.php/api/device/startgame")
                val conn = (url.openConnection() as HttpURLConnection).apply {
                    requestMethod = "POST"
                    setRequestProperty("Content-Type", "application/json;charset=UTF-8")
                    doOutput = true
                    connectTimeout = 8000
                    readTimeout = 8000
                }
                conn.outputStream.use { os ->
                    os.write(payload.toString().toByteArray(Charsets.UTF_8))
                }
                val code = conn.responseCode
                code in 200..299
            } catch (e: Throwable) {
                false
            }
        }
    }
}