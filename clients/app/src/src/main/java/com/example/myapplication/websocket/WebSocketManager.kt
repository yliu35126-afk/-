package com.example.myapplication.websocket

import android.content.Context
import android.util.Log
import com.example.myapplication.model.LotteryCommand
import com.example.myapplication.model.LotteryResult
import com.example.myapplication.utils.DeviceUtils
import com.google.gson.Gson
import io.socket.client.IO
import io.socket.client.Socket
import io.socket.emitter.Emitter
import org.json.JSONException
import org.json.JSONObject
import java.net.URISyntaxException
import java.util.*
import kotlin.math.min
import kotlin.math.pow

/**
 * WebSocket连接管理器 - 基于标准文档重构版本
 * 实现完整的心跳机制、重连策略和连接质量监控
 * 
 * 架构改进:
 * - Timer-based 心跳替代 Handler 实现
 * - 指数退避重连算法
 * - GameCallback 接口替代 WebSocketListener
 * - 连接质量监控和状态管理
 */
class WebSocketManager private constructor() {
    
    companion object {
        private const val TAG = "WebSocketManager"
        // 由 NetworkConfig 动态解析（通过 init 赋值）
        private var SERVER_URL: String = com.example.myapplication.network.NetworkConfig.socketIoHttpUrl()
        private const val HEARTBEAT_INTERVAL = 30000L // 30秒心跳间隔
        private const val MAX_RECONNECT_ATTEMPTS = 5
        private const val BASE_RECONNECT_DELAY = 1000L // 基础重连延迟1秒
        
        @Volatile
        private var INSTANCE: WebSocketManager? = null
        
        fun getInstance(): WebSocketManager {
            return INSTANCE ?: synchronized(this) {
                INSTANCE ?: WebSocketManager().also { INSTANCE = it }
            }
        }
    }
    
    // 核心组件
    private var socket: Socket? = null
    private val gson = Gson()
    private var gameCallback: GameCallback? = null
    private var context: Context? = null
    private var deviceId: String? = null
    
    // 心跳系统 - 使用Timer替代Handler
    private var heartbeatTimer: Timer? = null
    private var heartbeatTask: TimerTask? = null
    
    // 重连系统
    private var reconnectAttempts = 0
    private var reconnectTimer: Timer? = null
    
    // 连接质量监控
    private var lastHeartbeatTime = 0L
    private var connectionQuality = "良好"
    
    /**
     * 游戏回调接口 - 替代原WebSocketListener
     * 提供更明确的游戏事件处理
     */
    interface GameCallback {
        fun onConnected()
        fun onDisconnected()
        fun onLotteryCommand(command: LotteryCommand)
        fun onError(error: String)
        fun onDeviceRegistered(success: Boolean, message: String)
        fun onUserJoined(userInfo: JSONObject)  // 更名为onUserJoined
        fun onLotteryStarted()  // 新增：抽奖开始事件
        fun onLotteryResult(result: LotteryResult)  // 新增：抽奖结果事件
    }

    // 兼容字符串/数字的安全整型解析，防止NumberFormat异常
    private fun jsonGetIntSafe(obj: JSONObject, key: String): Int {
        return try {
            if (!obj.has(key)) return 0
            val v = obj.get(key)
            when (v) {
                is Number -> v.toInt()
                is String -> v.toIntOrNull() ?: 0
                else -> 0
            }
        } catch (_: Exception) {
            0
        }
    }

    // 安全调用封装，避免事件回调未捕获异常导致APP崩溃
    private fun safeInvoke(actionName: String, action: () -> Unit) {
        try {
            action()
        } catch (t: Throwable) {
            Log.e(TAG, "事件处理异常: $actionName", t)
            gameCallback?.onError("$actionName 异常: ${t.message}")
        }
    }
    
    /**
     * 初始化WebSocket连接 - 使用GameCallback接口
     */
    fun init(context: Context, callback: GameCallback) {
        this.context = context
        this.gameCallback = callback
        this.deviceId = getDeviceId(context)
        this.reconnectAttempts = 0
        // 重新解析一次，确保与当前环境一致
        SERVER_URL = com.example.myapplication.network.NetworkConfig.socketIoHttpUrl()
        
        Log.d(TAG, "=== WebSocket管理器初始化 (重构版) ===")
        Log.d(TAG, "设备ID: $deviceId")
        Log.d(TAG, "服务器地址: $SERVER_URL")
        Log.d(TAG, "Socket.IO路径: ${com.example.myapplication.network.NetworkConfig.socketPath()}")
        Log.d(TAG, "心跳间隔: ${HEARTBEAT_INTERVAL}ms")
        Log.d(TAG, "最大重连次数: $MAX_RECONNECT_ATTEMPTS")
    }
    
    /**
     * 连接WebSocket - 重构版本带重连逻辑
     */
    fun connect() {
        if (socket?.connected() == true) {
            Log.d(TAG, "WebSocket已连接，跳过重复连接")
            return
        }
        
        try {
            // 断开现有连接
            disconnect()
            
            // 配置Socket.IO选项：优先WebSocket，允许回退到polling，提升复杂网络下的连通性
            val options = IO.Options().apply {
                transports = arrayOf("websocket", "polling")
                timeout = 30000
                reconnection = true
                forceNew = true
                // 与服务端/Nginx一致的 Socket.IO 代理路径
                path = com.example.myapplication.network.NetworkConfig.socketPath()
            }
            
            socket = IO.socket(SERVER_URL, options)
            setupListeners()
            socket?.connect()
            
            Log.d(TAG, "🔌 正在连接到: $SERVER_URL (重构版)")
            Log.d(TAG, "📊 重连尝试: $reconnectAttempts/$MAX_RECONNECT_ATTEMPTS")
            
        } catch (e: URISyntaxException) {
            Log.e(TAG, "❌ WebSocket URI错误", e)
            gameCallback?.onError("连接地址错误: ${e.message}")
            scheduleReconnect()
        } catch (e: Exception) {
            Log.e(TAG, "❌ WebSocket初始化失败", e)
            gameCallback?.onError("初始化失败: ${e.message}")
            scheduleReconnect()
        }
    }
    
    /**
     * 获取设备ID - 与REST API保持完全一致
     */
    private fun getDeviceId(context: Context): String {
        // 使用与REST API完全相同的DeviceUtils方法
        return DeviceUtils.getDeviceId(context)
    }
    
    /**
     * 指数退避重连算法
     */
    private fun scheduleReconnect() {
        if (reconnectAttempts >= MAX_RECONNECT_ATTEMPTS) {
            Log.e(TAG, "⚠️ 达到最大重连次数，停止重连")
            gameCallback?.onError("连接失败，已达到最大重连次数")
            return
        }
        
        reconnectAttempts++
        val delay = (BASE_RECONNECT_DELAY * 2.0.pow(reconnectAttempts.toDouble())).toLong()
        val actualDelay = min(delay, 30000L) // 最大延迟30秒
        
        Log.d(TAG, "🔄 第${reconnectAttempts}次重连将在${actualDelay}ms后开始")
        
        reconnectTimer = Timer()
        reconnectTimer?.schedule(object : TimerTask() {
            override fun run() {
                Log.d(TAG, "🔄 执行第${reconnectAttempts}次重连")
                connect()
            }
        }, actualDelay)
    }

    /**
     * 设备注册 - 使用与REST API完全一致的设备ID
     */
    private fun registerDevice() {
        context?.let { ctx ->
            val deviceIdForRegistration = deviceId ?: getDeviceId(ctx)
            
            try {
                val data = JSONObject().apply {
                    put("device_id", deviceIdForRegistration)
                    put("device_type", "android")
                    put("platform", "Android")
                }
                socket?.emit("device_register", data)
                Log.d(TAG, "📱 发送设备注册: $deviceIdForRegistration")
            } catch (e: JSONException) {
                Log.e(TAG, "❌ 设备注册数据错误", e)
                gameCallback?.onError("设备注册失败: ${e.message}")
            }
        }
    }
    
    /**
     * 设置事件监听器 - 重构版本使用GameCallback
     */
    private fun setupListeners() {
        socket?.apply {
            // 1. 连接成功
            on(Socket.EVENT_CONNECT, Emitter.Listener {
                Log.d(TAG, "✅ WebSocket连接成功 - 重构版")
                reconnectAttempts = 0 // 重置重连计数
                cancelReconnectTimer()
                
                gameCallback?.onConnected()
                registerDevice()
                
                // 立即开始心跳 + 3秒后备份启动
                startHeartbeat()
                Timer().schedule(object : TimerTask() {
                    override fun run() {
                        if (socket?.connected() == true && heartbeatTimer == null) {
                            Log.d(TAG, "💓 3秒后备份心跳启动")
                            startHeartbeat()
                        }
                    }
                }, 3000)
            })
            
            // 2. 设备注册响应
            on("device_register_response", Emitter.Listener { args ->
                try {
                    if (args.isNotEmpty()) {
                        val data = args[0] as JSONObject
                        val success = data.getBoolean("success")
                        val message = data.optString("message", "设备注册完成")
                        
                        Log.d(TAG, if (success) "✅ 设备注册成功" else "❌ 设备注册失败: $message")
                        gameCallback?.onDeviceRegistered(success, message)
                    }
                } catch (e: Exception) {
                    Log.e(TAG, "❌ 解析设备注册响应失败", e)
                    gameCallback?.onError("设备注册响应解析失败: ${e.message}")
                }
            })
            
            // 3. 接收抽奖指令 - 同时监听lottery_command和lottery_start事件
            on("lottery_command", Emitter.Listener { args ->
                try {
                    if (args.isNotEmpty()) {
                        val data = when (val raw = args[0]) {
                            is JSONObject -> raw
                            is String -> JSONObject(raw)
                            else -> JSONObject(raw.toString())
                        }
                        Log.d(TAG, "🎯 收到抽奖指令(lottery_command): $data")

                        val command = LotteryCommand(
                            userId = jsonGetString(data, "user_id"),
                            deviceId = jsonGetString(data, "device_id"),
                            lotteryType = jsonGetIntSafe(data, "lottery_type"),
                            userName = if (data.has("user_name") && !data.isNull("user_name")) data.getString("user_name") else null,
                            userPhone = if (data.has("user_phone") && !data.isNull("user_phone")) data.getString("user_phone") else null,
                            orderId = jsonGetIntSafe(data, "order_id"),
                            orderAmount = jsonGetDouble(data, "order_amount"),
                            configId = jsonGetString(data, "config_id"),
                            timestamp = jsonGetString(data, "timestamp")
                        )
                        safeInvoke("onLotteryCommand") { gameCallback?.onLotteryCommand(command) }
                        safeInvoke("onLotteryStarted") { gameCallback?.onLotteryStarted() } // 触发抽奖开始事件
                    }
                } catch (e: Exception) {
                    Log.e(TAG, "❌ 解析抽奖指令失败", e)
                    gameCallback?.onError("抽奖指令解析失败: ${e.message}")
                }
            })
            
            // 3.1 接收lottery_start事件 - 新增监听器
            on("lottery_start", Emitter.Listener { args ->
                try {
                    if (args.isNotEmpty()) {
                        val data = when (val raw = args[0]) {
                            is JSONObject -> raw
                            is String -> JSONObject(raw)
                            else -> JSONObject(raw.toString())
                        }
                        Log.d(TAG, "🎯 收到抽奖启动指令(lottery_start): $data")

                        val command = LotteryCommand(
                            userId = jsonGetString(data, "user_id"),
                            deviceId = jsonGetString(data, "device_id"),
                            lotteryType = jsonGetIntSafe(data, "lottery_type"),
                            userName = if (data.has("user_name") && !data.isNull("user_name")) data.getString("user_name") else null,
                            userPhone = if (data.has("user_phone") && !data.isNull("user_phone")) data.getString("user_phone") else null,
                            orderId = jsonGetIntSafe(data, "order_id"),
                            orderAmount = jsonGetDouble(data, "order_amount"),
                            configId = jsonGetString(data, "config_id"),
                            timestamp = jsonGetString(data, "timestamp")
                        )
                        safeInvoke("onLotteryCommand") { gameCallback?.onLotteryCommand(command) }
                        safeInvoke("onLotteryStarted") { gameCallback?.onLotteryStarted() } // 触发抽奖开始事件
                    }
                } catch (e: Exception) {
                    Log.e(TAG, "❌ 解析lottery_start指令失败", e)
                    gameCallback?.onError("lottery_start指令解析失败: ${e.message}")
                }
            })
            
            // 4. 用户连接通知
            on("user_connected", Emitter.Listener { args ->
                try {
                    if (args.isNotEmpty()) {
                        val userInfo = args[0] as JSONObject
                        Log.d(TAG, "👤 用户加入游戏: $userInfo")
                        gameCallback?.onUserJoined(userInfo) // 使用新方法名
                    }
                } catch (e: Exception) {
                    Log.e(TAG, "❌ 解析用户连接信息失败", e)
                }
            })
            
            // 5. 连接断开
            on(Socket.EVENT_DISCONNECT, Emitter.Listener { args ->
                val reason = if (args.isNotEmpty()) args[0].toString() else "unknown"
                Log.d(TAG, "📴 WebSocket连接断开: $reason")
                
                gameCallback?.onDisconnected()
                stopHeartbeat()
                scheduleReconnect() // 自动重连
            })
            
            // 6. 连接错误
            on(Socket.EVENT_CONNECT_ERROR, Emitter.Listener { args ->
                val errorDetail = if (args.isNotEmpty()) {
                    when (val error = args[0]) {
                        is Exception -> "异常: ${error.message}"
                        else -> "错误: $error"
                    }
                } else {
                    "未知连接错误"
                }
                Log.e(TAG, "❌ WebSocket连接失败: $errorDetail")
                gameCallback?.onError("连接失败: $errorDetail")
                scheduleReconnect()
            })
            
            // 7. 通用错误处理
            on("error", Emitter.Listener { args ->
                try {
                    if (args.isNotEmpty()) {
                        val error = args[0] as JSONObject
                        Log.e(TAG, "❌ 服务器错误: $error")
                        gameCallback?.onError("服务器错误: ${error.optString("message", "未知错误")}")
                    }
                } catch (e: Exception) {
                    Log.e(TAG, "❌ 错误处理失败", e)
                }
            })
            
            // 8. 心跳响应
            on("heartbeat_response", Emitter.Listener {
                lastHeartbeatTime = System.currentTimeMillis()
                Log.d(TAG, "💓 心跳响应正常")
            })
        }
    }
    
    // -------- JSON 兼容工具函数（处理字符串/数字类型混用） --------
    private fun jsonGetString(obj: JSONObject, key: String): String {
        val v = obj.opt(key)
        return when (v) {
            is String -> v
            is Number -> v.toString()
            JSONObject.NULL -> ""
            else -> v?.toString() ?: ""
        }
    }

    private fun jsonGetInt(obj: JSONObject, key: String): Int {
        val v = obj.opt(key)
        return when (v) {
            is Number -> (v as Number).toInt()
            is String -> v.toIntOrNull() ?: 0
            else -> 0
        }
    }

    private fun jsonGetDouble(obj: JSONObject, key: String): Double {
        val v = obj.opt(key)
        return when (v) {
            is Number -> (v as Number).toDouble()
            is String -> v.toDoubleOrNull() ?: 0.0
            else -> 0.0
        }
    }

    /**
     * 发送抽奖结果 - 重构版本
     */
    fun sendLotteryResult(result: LotteryResult) {
        socket?.let { socket ->
            if (socket.connected()) {
                try {
                    val data = JSONObject().apply {
                        put("user_id", result.userId)
                        put("result", result.result)
                        
                        if (result.result == "win" && result.prizeInfo != null) {
                            val prizeInfo = JSONObject().apply {
                                put("id", result.prizeInfo.id)
                                put("name", result.prizeInfo.name)
                                put("image", result.prizeInfo.image)
                            }
                            put("prize_info", prizeInfo)
                        }
                        
                        result.lotteryRecordId?.let { recordId ->
                            put("lottery_record_id", recordId)
                        }
                    }
                    
                    socket.emit("lottery_result", data)
                    Log.d(TAG, "🎯 发送抽奖结果: $data")
                    
                    // 触发抽奖结果事件
                    gameCallback?.onLotteryResult(result)
                    
                } catch (e: JSONException) {
                    Log.e(TAG, "❌ 构建抽奖结果数据失败", e)
                    gameCallback?.onError("发送抽奖结果失败: ${e.message}")
                }
            } else {
                Log.w(TAG, "⚠️ WebSocket未连接，无法发送抽奖结果")
                gameCallback?.onError("WebSocket未连接")
            }
        }
    }
    
    /**
     * Timer-based心跳检测 - 替代Handler实现
     */
    private fun startHeartbeat() {
        stopHeartbeat()
        
        heartbeatTimer = Timer()
        heartbeatTask = object : TimerTask() {
            override fun run() {
                if (socket?.connected() == true) {
                    try {
                        val heartbeatData = JSONObject().apply {
                            put("device_id", deviceId)
                            put("timestamp", System.currentTimeMillis())
                            put("connection_quality", connectionQuality)
                        }
                        
                        socket?.emit("heartbeat", heartbeatData)
                        Log.d(TAG, "💓 发送心跳 (${connectionQuality})")
                        
                        // 检查连接质量
                        checkConnectionQuality()
                        
                    } catch (e: Exception) {
                        Log.e(TAG, "❌ 心跳发送失败", e)
                    }
                } else {
                    Log.w(TAG, "⚠️ 连接已断开，停止心跳")
                    stopHeartbeat()
                }
            }
        }
        
        heartbeatTimer?.scheduleAtFixedRate(heartbeatTask, 0, HEARTBEAT_INTERVAL)
        Log.d(TAG, "💓 Timer心跳已启动，间隔: ${HEARTBEAT_INTERVAL}ms")
    }
    
    /**
     * 停止Timer心跳
     */
    private fun stopHeartbeat() {
        heartbeatTask?.cancel()
        heartbeatTimer?.cancel()
        heartbeatTimer = null
        heartbeatTask = null
        Log.d(TAG, "💓 Timer心跳已停止")
    }
    
    /**
     * 检查连接质量
     */
    private fun checkConnectionQuality() {
        val currentTime = System.currentTimeMillis()
        if (lastHeartbeatTime > 0) {
            val timeSinceLastHeartbeat = currentTime - lastHeartbeatTime
            connectionQuality = when {
                timeSinceLastHeartbeat < 5000 -> "良好"
                timeSinceLastHeartbeat < 10000 -> "一般" 
                else -> "较差"
            }
        }
    }
    
    /**
     * 取消重连定时器
     */
    private fun cancelReconnectTimer() {
        reconnectTimer?.cancel()
        reconnectTimer = null
    }
    
    /**
     * 断开WebSocket连接 - 重构版本
     */
    fun disconnect() {
        Log.d(TAG, "🔌 断开WebSocket连接 (重构版)")
        
        stopHeartbeat()
        cancelReconnectTimer()
        
        socket?.disconnect()
        socket = null
        
        reconnectAttempts = 0
        lastHeartbeatTime = 0L
        connectionQuality = "良好"
    }
    
    /**
     * 检查连接状态
     */
    fun isConnected(): Boolean {
        return socket?.connected() ?: false
    }
    
    /**
     * 获取连接状态描述 - 增强版本
     */
    fun getConnectionStatus(): String {
        return when {
            socket == null -> "未初始化"
            socket!!.connected() -> "已连接 ($connectionQuality)"
            reconnectAttempts > 0 -> "重连中 ($reconnectAttempts/$MAX_RECONNECT_ATTEMPTS)"
            else -> "未连接"
        }
    }
    
    /**
     * 获取连接统计信息
     */
    fun getConnectionStats(): Map<String, Any> {
        return mapOf(
            "connected" to isConnected(),
            "reconnect_attempts" to reconnectAttempts,
            "connection_quality" to connectionQuality,
            "last_heartbeat" to lastHeartbeatTime,
            "device_id" to (deviceId ?: "未知")
        )
    }
}
