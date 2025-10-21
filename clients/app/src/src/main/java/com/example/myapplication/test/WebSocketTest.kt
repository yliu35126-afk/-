package com.example.myapplication.test

import android.content.Context
import android.util.Log
import com.example.myapplication.model.LotteryCommand
import com.example.myapplication.model.LotteryResult
import com.example.myapplication.websocket.WebSocketManager
import org.json.JSONObject

/**
 * WebSocket测试工具 - 重构版本适配GameCallback接口
 */
class WebSocketTest {
    
    companion object {
        private const val TAG = "WebSocketTest"
        
        /**
         * 测试WebSocket连接 - 使用重构后的GameCallback
         */
        fun testWebSocketConnection(context: Context) {
            Log.d(TAG, "🧪 开始测试WebSocket连接 (重构版)...")
            
            val webSocketManager = WebSocketManager.getInstance()
            
            val testCallback = object : WebSocketManager.GameCallback {
                override fun onConnected() {
                    Log.d(TAG, "✅ WebSocket连接测试成功！")
                    Log.d(TAG, "📊 连接统计: ${webSocketManager.getConnectionStats()}")
                }
                
                override fun onDisconnected() {
                    Log.d(TAG, "📴 WebSocket连接断开")
                }
                
                override fun onLotteryCommand(command: LotteryCommand) {
                    Log.d(TAG, "🎯 收到测试抽奖指令: $command")
                }
                
                override fun onError(error: String) {
                    Log.e(TAG, "❌ WebSocket连接测试失败: $error")
                }
                
                override fun onDeviceRegistered(success: Boolean, message: String) {
                    Log.d(TAG, "📱 设备注册测试结果: $success - $message")
                }
                
                override fun onUserJoined(userInfo: JSONObject) {
                    Log.d(TAG, "👤 测试用户加入: $userInfo")
                }
                
                override fun onLotteryStarted() {
                    Log.d(TAG, "🎯 测试抽奖开始事件")
                }
                
                override fun onLotteryResult(result: LotteryResult) {
                    Log.d(TAG, "🎯 测试抽奖结果事件: ${result.result}")
                }
            }
            
            webSocketManager.init(context, testCallback)
            webSocketManager.connect()
        }
        
        /**
         * 模拟抽奖指令测试
         */
        fun simulateLotteryCommand(): LotteryCommand {
            return LotteryCommand(
                userId = "test_user_001",
                deviceId = "TEST_DEVICE_123",
                lotteryType = 2, // 扫码抽奖
                userName = "测试用户",
                userPhone = "13800138000",
                orderId = 1001,
                orderAmount = 0.5,
                configId = "test_config_1",
                timestamp = "2025-09-17T12:00:00.000Z"
            )
        }
        
        /**
         * 检查WebSocket状态 - 重构版本增强
         */
        fun checkWebSocketStatus(): String {
            val webSocketManager = WebSocketManager.getInstance()
            val stats = webSocketManager.getConnectionStats()
            return "🔍 WebSocket状态检查:\n" +
                   "状态: ${webSocketManager.getConnectionStatus()}\n" +
                   "连接: ${if (webSocketManager.isConnected()) "✅ 已连接" else "❌ 未连接"}\n" +
                   "重连次数: ${stats["reconnect_attempts"]}\n" +
                   "连接质量: ${stats["connection_quality"]}\n" +
                   "设备ID: ${stats["device_id"]}"
        }
        
        /**
         * 测试心跳机制
         */
        fun testHeartbeat(context: Context) {
            Log.d(TAG, "💓 测试心跳机制...")
            val webSocketManager = WebSocketManager.getInstance()
            
            if (webSocketManager.isConnected()) {
                Log.d(TAG, "💓 当前连接正常，心跳应正在运行")
            } else {
                Log.d(TAG, "💓 当前未连接，启动连接测试心跳")
                testWebSocketConnection(context)
            }
        }
    }
}
