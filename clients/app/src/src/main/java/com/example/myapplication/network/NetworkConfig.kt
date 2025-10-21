package com.example.myapplication.network

import android.os.Build
import android.util.Log
import java.net.URI
import com.example.myapplication.BuildConfig

/**
 * 网络配置与环境识别
 * - 自动识别模拟器，设置正确的本地联调地址
 * - 提供 BASE_URL 与 WebSocket URL 统一来源
 */
object NetworkConfig {

    private const val TAG = "NetworkConfig"

    /**
     * 返回基础 BASE_URL（带尾部斜杠）
     * 模拟器: 使用 10.0.2.2 映射主机
     * 真机/盒子: 默认 127.0.0.1（可通过反向代理或局域网调试时手动替换）
     */
    fun baseUrl(): String {
        // 统一从 BuildConfig 读取，避免环境识别导致的路径偏差
        val url = BuildConfig.BACKEND_BASE_URL
        Log.d(TAG, "BASE_URL resolved: $url")
        return url
    }

    /**
     * 提供用于静态资源/图片的基础域名（带尾部斜杠）
     * 依据 BACKEND_BASE_URL 解析出 scheme://host[:port]/
     * 避免将 API 前缀（例如 index.php/api/）误用于图片路径，导致 404
     */
    fun imageBaseUrl(): String {
        return try {
            val uri = URI(baseUrl())
            val scheme = uri.scheme ?: "http"
            val host = uri.host ?: "127.0.0.1"
            val portPart = if (uri.port != -1) ":${uri.port}" else ""
            "$scheme://$host$portPart/"
        } catch (e: Exception) {
            // 解析失败时回退到 localhost
            "http://127.0.0.1:8000/"
        }
    }

    /**
     * 解析出主机名（不含端口）
     */
    fun host(): String {
        val uri = URI(baseUrl())
        return uri.host ?: "127.0.0.1"
    }

    /**
     * 返回 WebSocket 的基础地址（Socket.IO 使用 http 基址）
     * 文档要求: ws://localhost:3001 -> 对应 http://host:3001 给 IO.socket
     */
    fun socketIoHttpUrl(): String {
        val configured = BuildConfig.SOCKET_BASE_URL
        val url = if (!configured.isNullOrBlank()) {
            configured
        } else {
            val host = host()
            "http://$host:3001"
        }
        Log.d(TAG, "Socket.IO URL resolved: $url")
        return url
    }

    /**
     * 备用 BASE_URL（入口路径回退）
     * 将 /index.php/api/ 与 /api/ 互相切换，用于 404 回退重试
     */
    fun alternateBaseUrl(): String {
        val primary = baseUrl()
        return if (primary.contains("/index.php/api/")) {
            primary.replace("/index.php/api/", "/api/")
        } else if (primary.contains("/api/")) {
            primary.replace("/api/", "/index.php/api/")
        } else {
            // 不含明确前缀时，默认追加 /index.php/api/
            val tail = if (primary.endsWith("/")) "" else "/"
            primary + tail + "index.php/api/"
        }
    }

    /**
     * 第二层备用 BASE_URL（入口路径回退到 /api.php/）
     * 在反向代理仅暴露 api.php 的情况下避免 404
     */
    fun fallbackApiPhpBaseUrl(): String {
        val primary = baseUrl()
        return when {
            primary.contains("/index.php/api/") -> primary.replace("/index.php/api/", "/api.php/")
            primary.contains("/api/") -> primary.replace("/api/", "/api.php/")
            primary.contains("/index.php/") -> primary.replace("/index.php/", "/api.php/")
            else -> {
                val tail = if (primary.endsWith("/")) "" else "/"
                primary + tail + "api.php/"
            }
        }
    }

    fun socketPath(): String {
        val path = BuildConfig.SOCKET_PATH
        return if (path.isNullOrBlank()) "/socket.io/" else path
    }

    /**
     * 是否运行在模拟器
     */
    fun isEmulator(): Boolean {
        val fingerprint = Build.FINGERPRINT
        val model = Build.MODEL
        val product = Build.PRODUCT
        val manufacturer = Build.MANUFACTURER
        return (fingerprint.startsWith("generic") || fingerprint.startsWith("unknown")
                || model.contains("google_sdk") || model.contains("Emulator") || model.contains("Android SDK built for x86")
                || manufacturer.contains("Genymotion")
                || product.contains("sdk") || product.contains("google_sdk") || product.contains("sdk_x86") || product.contains("vbox86p"))
    }
}