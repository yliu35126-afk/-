@file:Suppress("DEPRECATION", "DEPRECATION_ERROR")
package com.example.myapplication.websocket

import android.content.Context
import android.util.Log
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import okhttp3.*
import java.util.concurrent.TimeUnit

/**
 * WebSocket连接专项测试器
 * 用于深度测试WebSocket连接
 */
class WebSocketConnectionTester(private val context: Context) {
    
    companion object {
        private const val TAG = "WSConnectionTester"
        private const val BASE_URL = "yiqilishi.com.cn"
    }
    
    fun runComprehensiveTest() {
        Log.d(TAG, "=== 综合WebSocket连接测试 ===")
        
        CoroutineScope(Dispatchers.IO).launch {
            try {
                testHttpsConnectivity()
                testWebSocketHandshake()
                testSocketIOPolling()
            } catch (e: Exception) {
                Log.e(TAG, "测试过程中发生错误", e)
            }
        }
    }
    
    private suspend fun testHttpsConnectivity() {
        withContext(Dispatchers.IO) {
            Log.d(TAG, "1. 测试HTTPS基础连接...")
            
            val client = OkHttpClient.Builder()
                .connectTimeout(10, TimeUnit.SECONDS)
                .readTimeout(15, TimeUnit.SECONDS)
                .build()
            
            val testUrls = listOf(
                "https://$BASE_URL/",
                "https://$BASE_URL/im/",
                "https://$BASE_URL/im/socket.io/"
            )
            
            for (url in testUrls) {
                try {
                    val request = Request.Builder()
                        .url(url)
                        .addHeader("User-Agent", "WebSocketConnectionTester/1.0")
                        .build()
                    
                    val response = client.newCall(request).execute()
                    Log.d(TAG, "✓ HTTPS连接成功: $url (状态码: ${response.code()})")
                    response.close()
                } catch (e: Exception) {
                    Log.e(TAG, "✗ HTTPS连接失败: $url", e)
                }
            }
        }
    }
    
    private suspend fun testWebSocketHandshake() {
        withContext(Dispatchers.IO) {
            Log.d(TAG, "2. 测试WebSocket握手...")
            
            val client = OkHttpClient.Builder()
                .connectTimeout(15, TimeUnit.SECONDS)
                .build()
            
            val wsUrls = listOf(
                "wss://$BASE_URL/im/socket.io/",
                "wss://$BASE_URL/socket.io/"
            )
            
            for (wsUrl in wsUrls) {
                try {
                    val request = Request.Builder()
                        .url(wsUrl)
                        .addHeader("Origin", "https://$BASE_URL")
                        .build()
                    
                    val webSocket = client.newWebSocket(request, object : WebSocketListener() {
                        override fun onOpen(webSocket: WebSocket, response: Response) {
                            Log.d(TAG, "✓ WebSocket握手成功: $wsUrl")
                            webSocket.close(1000, "测试完成")
                        }
                        
                        override fun onFailure(webSocket: WebSocket, t: Throwable, response: Response?) {
                            Log.e(TAG, "✗ WebSocket握手失败: $wsUrl", t)
                        }
                        
                        override fun onClosed(webSocket: WebSocket, code: Int, reason: String) {
                            Log.d(TAG, "WebSocket已关闭: $wsUrl")
                        }
                    })
                    
                    Thread.sleep(3000) // 等待连接结果
                } catch (e: Exception) {
                    Log.e(TAG, "WebSocket测试异常: $wsUrl", e)
                }
            }
        }
    }
    
    @Suppress("DEPRECATION")
    private suspend fun testSocketIOPolling() {
        withContext(Dispatchers.IO) {
            Log.d(TAG, "3. 测试Socket.IO轮询...")
            
            val client = OkHttpClient.Builder()
                .connectTimeout(10, TimeUnit.SECONDS)
                .readTimeout(15, TimeUnit.SECONDS)
                .build()
            
            val pollingUrls = listOf(
                "https://$BASE_URL/im/socket.io/?EIO=4&transport=polling",
                "https://$BASE_URL/socket.io/?EIO=4&transport=polling",
                "https://$BASE_URL/im/socket.io/?EIO=3&transport=polling",
                "https://$BASE_URL/socket.io/?EIO=3&transport=polling"
            )
            
            for (url in pollingUrls) {
                try {
                    val request = Request.Builder()
                        .url(url)
                        .addHeader("Origin", "https://$BASE_URL")
                        .addHeader("Accept", "*/*")
                        .build()
                    
                    val response = client.newCall(request).execute()
                    val body = response.body()?.string()
                    
                    Log.d(TAG, "Socket.IO轮询测试: $url")
                    Log.d(TAG, "状态码: ${response.code()}")
                    Log.d(TAG, "响应: ${body?.take(100)}...")
                    
                    if (response.code() == 200 && body?.contains("sid") == true) {
                        Log.d(TAG, "✓ Socket.IO轮询成功")
                    } else {
                        Log.w(TAG, "✗ Socket.IO轮询可能有问题")
                    }
                    
                    response.close()
                } catch (e: Exception) {
                    Log.e(TAG, "Socket.IO轮询失败: $url", e)
                }
            }
        }
    }
}
