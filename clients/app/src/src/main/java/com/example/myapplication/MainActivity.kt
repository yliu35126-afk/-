package com.example.myapplication

import android.Manifest
import android.animation.AnimatorSet
import android.animation.ObjectAnimator
import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.content.pm.PackageManager
import android.media.MediaPlayer
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.util.Log
import android.view.View
import android.view.ViewGroup
import android.view.WindowManager
import android.view.animation.AccelerateDecelerateInterpolator
import android.widget.Button
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.core.text.HtmlCompat
import androidx.appcompat.app.AppCompatActivity
import androidx.cardview.widget.CardView
import androidx.core.app.ActivityCompat
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.LinearLayoutManager
import com.example.myapplication.lottery.LotteryProcessor
import com.example.myapplication.model.DeviceInitData
import com.example.myapplication.model.DeviceRegisterData
import com.example.myapplication.model.LotteryCommand
import com.example.myapplication.model.LotteryResult
import com.example.myapplication.model.Prize
import com.example.myapplication.repository.DeviceRepository
import com.example.myapplication.repository.PrizeRepository
import com.example.myapplication.repository.QrCodeRepository
// import com.example.myapplication.test.ApiTest
import com.example.myapplication.utils.DeviceUtils
import com.example.myapplication.utils.AppUtils
import com.example.myapplication.utils.ImageLoader
import com.example.myapplication.utils.NetworkUtils
import com.example.myapplication.utils.AudioFocusHelper
import com.example.myapplication.utils.AudioRouteUtils
import com.example.myapplication.utils.InvalidIndexMonitor
import com.example.myapplication.websocket.WebSocketConnectionTester
import com.example.myapplication.websocket.WebSocketManager
import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.*
import kotlin.math.*
import kotlin.random.Random
import com.example.myapplication.BuildConfig
import com.example.myapplication.Defaults

class MainActivity : AppCompatActivity(), WebSocketManager.GameCallback {
    
    private lateinit var lotteryWheel: LotteryWheelView
    private lateinit var winnerOverlay: View
    private lateinit var winPrizeImage: ImageView
    private lateinit var winPrizeName: TextView
    private lateinit var winCountdown: TextView
    
    // Loading相关视图
    private lateinit var loadingOverlay: View
    private lateinit var loadingText: TextView
    private lateinit var loadingProgress: TextView
    
    private var isRunning = false
    private var activeIndex = -1
    private val handler = Handler(Looper.getMainLooper())
    private var spinTimer: Runnable? = null
    
    // 音效播放器
    private var startSound: MediaPlayer? = null
    private var spinSound: MediaPlayer? = null
    private var endSound: MediaPlayer? = null
    private var soundManager: SoundManager? = null
    private lateinit var audioFocusHelper: AudioFocusHelper
    
    // 对应Vue项目的16个奖品 - 现在从API获取
    private val prizeList = mutableListOf<Prize>()
    private val recordsList = mutableListOf<WinRecord>()
    private lateinit var recordsAdapter: WinRecordsAdapter
    
    // 当前抽奖信息（用于动画结束后发送结果）
    private var currentLotteryCommand: LotteryCommand? = null
    private var currentLotteryResult: LotteryResult? = null
    
    // API数据仓库
    private val prizeRepository = PrizeRepository()
    private val deviceRepository = DeviceRepository()
    private val qrCodeRepository = QrCodeRepository()
    
    // WebSocket和抽奖逻辑
    private lateinit var webSocketManager: WebSocketManager
    private val lotteryProcessor = LotteryProcessor()
    
    // 测试用变量
    private var currentConfigId = "26"  // 默认配置ID：从数据库获取的实际分润配置ID
    private val testConfigIds = listOf("26", "32")  // 使用数据库中实际的分润配置ID列表
    private var currentConfigIndex = 0  // 默认从26开始（索引0）
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        // 设置真正的沉浸式全屏模式
        window.decorView.systemUiVisibility = (
            View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
            or View.SYSTEM_UI_FLAG_LAYOUT_STABLE
            or View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
            or View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
            or View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
            or View.SYSTEM_UI_FLAG_FULLSCREEN
        )
        
        // 隐藏顶部标题栏
        supportActionBar?.hide()
        
        // 保持屏幕常亮（适合抽奖场景）
        window.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
        
        setContentView(R.layout.activity_main)
        
        initViews()
        initAudioPlayers()
        
        // 1. 直接进入启动流程：注册设备、加载奖品、二维码等（不再进行任何条件判断或弹窗）
        continueStartupProcess()
        
        // 2. 初始化WebSocket连接
        initWebSocket()
    }
    
    private fun initViews() {
        lotteryWheel = findViewById(R.id.lotteryWheel)
        winnerOverlay = findViewById(R.id.winnerOverlay)
        winPrizeImage = findViewById(R.id.winPrizeImage)
        winPrizeName = findViewById(R.id.winPrizeName)
        winCountdown = findViewById(R.id.winCountdown)
        
        // Loading相关视图
        loadingOverlay = findViewById(R.id.loadingOverlay)
        loadingText = findViewById(R.id.loadingText)
        loadingProgress = findViewById(R.id.loadingProgress)
        
        // 初始化内测提示
//        initBetaTip()
        
        // 设置遮罩点击关闭
        winnerOverlay.setOnClickListener { closeWinner() }
        
        // 长按抽奖轮盘触发调试抽奖（调试入口）
        lotteryWheel.setOnLongClickListener {
            val debugCommand = LotteryCommand(
                userId = "DEBUG_USER_${System.currentTimeMillis()}",
                deviceId = DeviceUtils.getDeviceId(this),
                lotteryType = 3,
                userName = "Debug User",
                orderId = 1,
                orderAmount = 29.9,
                configId = currentConfigId,
                timestamp = System.currentTimeMillis().toString()
            )
            processRemoteLottery(debugCommand)
            true
        }
        
        // 设置系统UI可见性变化监听器，确保始终保持全屏
        window.decorView.setOnSystemUiVisibilityChangeListener { visibility ->
            if (visibility and View.SYSTEM_UI_FLAG_FULLSCREEN == 0) {
                // 系统栏重新出现，再次隐藏
                window.decorView.systemUiVisibility = (
                    View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
                    or View.SYSTEM_UI_FLAG_LAYOUT_STABLE
                    or View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
                    or View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
                    or View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
                    or View.SYSTEM_UI_FLAG_FULLSCREEN
                )
            }
        }
        
        // 初始化测试按钮（已隐藏）
//        initTestButtons()
//        // 调试用：长按轮盘触发本地抽奖
//        lotteryWheel.setOnLongClickListener {
//            val debugCommand = LotteryCommand(
//                userId = "DEBUG_USER_${System.currentTimeMillis()}",
//                userName = "Debug User",
//                deviceId = "DEV_9C2C8132EF800168", // 使用你指定的设备ID
//                configId = currentConfigId,
//                lotteryType = 3,
//                orderId = 1,
//                orderAmount = 1.2,
//                timestamp = "111111111"
//            )
//            processRemoteLottery(debugCommand)
//            true
//
//        }
    }
    
    /**
     * 初始化测试按钮（已隐藏）
     */
    private fun initTestButtons() {
        // 功能已隐藏，不再初始化切换配置按钮
        /*
        val btnSwitchConfig = findViewById<Button>(R.id.btnSwitchConfig)
        val configIdDisplay = findViewById<TextView>(R.id.configIdDisplay)
        
        // 更新显示
        updateConfigDisplay()
        
        // 设置按钮点击事件
        btnSwitchConfig.setOnClickListener {
            // 切换到下一个配置ID
            currentConfigIndex = (currentConfigIndex + 1) % testConfigIds.size
            currentConfigId = testConfigIds[currentConfigIndex]
            updateConfigDisplay()
            
            // 立即加载新配置的奖品
            loadTestConfig()
        }
        */
    }
    
    /**
     * 更新配置ID显示（已隐藏）
     */
    private fun updateConfigDisplay() {
        // 功能已隐藏，不再更新配置显示
        /*
        val configIdDisplay = findViewById<TextView>(R.id.configIdDisplay)
        configIdDisplay.text = "配置ID: ${currentConfigId ?: \"5\"}"
        */
    }
    
    /**
     * 加载测试配置的奖品（已隐藏）
     */
    private fun loadTestConfig() {
        // 功能已隐藏，不再支持动态切换配置
        /*
        Log.d("MainActivity", "测试：切换到配置ID = $currentConfigId")
        
        // 显示loading
        showLoading()
        
        // 清空当前奖品
        prizeList.clear() 
        lotteryWheel.setPrizeImages(emptyList())
        
        // 只获取奖品数据，不进入抽奖流程（传null作为command）
        val deviceId = DeviceUtils.getDeviceId(this)
        loadProfitPrizesFromApi(deviceId, currentConfigId, null)
        
        Toast.makeText(this, "正在加载配置ID: $currentConfigId 的奖品...", Toast.LENGTH_SHORT).show()
        */
    }

    private fun initAudioPlayers() {
        try {
            // 统一使用三段音乐管理，移除旧的三音效播放器
            audioFocusHelper = AudioFocusHelper(this)
            soundManager = SoundManager(this).apply {
                init()
                onOutroComplete = { audioFocusHelper.abandonFocus() }
            }
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }
    
    /**
     * 初始化内测提示蒙版
     */
    private fun initBetaTip() {
        val betaTip = findViewById<TextView>(R.id.betaVersionTip)
        
        // 创建柔和的闪烁动画效果
        val alphaAnimation = ObjectAnimator.ofFloat(betaTip, "alpha", 0.7f, 1.0f).apply {
            duration = 2000 // 2秒一个周期
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.REVERSE
            interpolator = AccelerateDecelerateInterpolator()
        }
        
        // 添加轻微的缩放动画
        val scaleXAnimation = ObjectAnimator.ofFloat(betaTip, "scaleX", 0.98f, 1.02f).apply {
            duration = 2000
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.REVERSE
            interpolator = AccelerateDecelerateInterpolator()
        }
        
        val scaleYAnimation = ObjectAnimator.ofFloat(betaTip, "scaleY", 0.98f, 1.02f).apply {
            duration = 2000
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.REVERSE
            interpolator = AccelerateDecelerateInterpolator()
        }
        
        // 组合动画
        val animatorSet = AnimatorSet().apply {
            playTogether(alphaAnimation, scaleXAnimation, scaleYAnimation)
        }
        
        // 启动动画
        animatorSet.start()
        
        // 点击可临时隐藏提示（5秒后重新显示）
        betaTip.setOnClickListener {
            betaTip.visibility = View.GONE
            animatorSet.cancel()
            
            Toast.makeText(this, "内测提示已隐藏，5秒后重新显示", Toast.LENGTH_SHORT).show()
            
            // 5秒后重新显示
            handler.postDelayed({
                betaTip.visibility = View.VISIBLE
                animatorSet.start()
            }, 5000)
        }
        
        Log.d("MainActivity", "内测提示已初始化 - 包含闪烁和缩放动画")
    }
    
    /**
     * 启动时检查设备初始化状态
     */
    private fun checkDeviceInitStatusOnStartup() {
        val deviceId = DeviceUtils.getDeviceId(this)
        Log.d("MainActivity", "启动时检查设备初始化状态，设备ID: $deviceId")
        
        deviceRepository.initDevice(
            deviceId = deviceId,
            onSuccess = { initData ->
                safeRunOnUiThread {
                    Log.d("MainActivity", "设备初始化状态检查成功")
                    Log.d("MainActivity", "设备初始化状态: ${initData.isInitialized}")
                    Log.d("MainActivity", "设备状态: ${initData.status}")
                    
                    // 动态设置分润配置ID：优先使用后端推荐的配置ID，否则使用绑定配置列表的第一个
                    val dynamicConfigId = when {
                        initData.recommendedConfigId != null && initData.recommendedConfigId > 0 -> {
                            Log.d("MainActivity", "使用后端推荐的分润配置ID: ${initData.recommendedConfigId}")
                            initData.recommendedConfigId.toString()
                        }
                        !initData.profitConfigIds.isNullOrEmpty() -> {
                            val firstConfigId = initData.profitConfigIds.first().toString()
                            Log.d("MainActivity", "使用绑定配置列表的第一个ID: $firstConfigId")
                            firstConfigId
                        }
                        else -> {
                            Log.w("MainActivity", "未获取到有效的分润配置ID，保持默认值: $currentConfigId")
                            currentConfigId
                        }
                    }
                    
                    // 更新当前配置ID
                    if (dynamicConfigId != currentConfigId) {
                        currentConfigId = dynamicConfigId
                        Log.d("MainActivity", "分润配置ID已更新为: $currentConfigId")
                        updateConfigDisplay()
                    }
                    
                    // 只有设备未注册或初始化失败时才显示弹窗
                    if (!initData.isInitialized && initData.status == "unregistered") {
                        // 设备未注册，显示欢迎弹窗
                        showDeviceInitStatusDialog(initData)
                    } else {
                        // 设备已注册，直接进入正常使用，不显示弹窗
                        Log.d("MainActivity", "设备已注册，跳过欢迎弹窗")
                    }
                    
                    // 继续执行后续流程：注册设备和加载奖品
                    continueStartupProcess()
                }
            },
            onError = { errorMsg ->
                safeRunOnUiThread {
                    Log.e("MainActivity", "设备初始化状态检查失败: $errorMsg")
                    // 即使初始化检查失败，也继续执行后续流程，不显示弹窗
                    continueStartupProcess()
                }
            }
        )
    }
    
    /**
     * 继续启动流程：注册设备和加载奖品
     */
    private fun continueStartupProcess() {
        Log.d("MainActivity", "继续启动流程：注册设备和加载奖品")
        
        // 1. 注册设备
        registerDeviceOnStartup()
        
        // 2. 加载奖品数据
        loadPrizesFromApi()
        
        // 3. 初始化其他组件
        setupRecords()
        setupWheel()
        
        // 4. 生成并显示支付二维码（优先小程序码，失败回退通用码）
        generateAndSetQRCode()
        
        // 测试API连接（开发阶段） - 生产环境中已注释
        // ApiTest.runAllTests(this)
    }

    /**
     * 应用启动时注册设备
     */
    private fun registerDeviceOnStartup() {
        Toast.makeText(this, "正在注册设备...", Toast.LENGTH_SHORT).show()
        
        deviceRepository.registerDevice(
            context = this,
            onSuccess = { deviceData ->
                safeRunOnUiThread {
                    val message = when (deviceData.status) {
                        "created" -> "设备首次注册成功！"
                        "updated" -> "设备访问记录更新成功！"
                        else -> "设备注册完成！"
                    }
                    Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
                    
                    // 设备注册成功后的回调处理
                    onDeviceRegistered(deviceData)
                }
            },
            onError = { errorMsg ->
                safeRunOnUiThread {
                    Toast.makeText(this, "设备注册失败: $errorMsg", Toast.LENGTH_LONG).show()
                    // 即使注册失败，也继续执行应用流程
                    onDeviceRegistered(null)
                }
            }
        )
    }
    
    /**
     * 设备注册完成后的处理
     */
    private fun onDeviceRegistered(deviceData: DeviceRegisterData?) {
        // 获取当前设备ID并保存到日志
        val currentDeviceId = deviceRepository.getCurrentDeviceId(this)
        Log.d("MainActivity", "当前设备ID: $currentDeviceId")
        
        if (deviceData != null) {
            Log.d("MainActivity", "设备注册状态: ${deviceData.status}")
            Log.d("MainActivity", "服务器消息: ${deviceData.message}")
        }
        
        // 这里可以添加设备注册成功后的其他逻辑
        // 比如更新UI状态、启用某些功能等
    }
    
    /**
     * 初始化WebSocket连接
     */
    private fun initWebSocket() {
        // 使用主要的WebSocket管理器
        webSocketManager = WebSocketManager.getInstance()
        webSocketManager.init(this, this)
        webSocketManager.connect()
        
        // 仅在调试构建下显示提示，线上不打扰用户
        val showWsToasts = BuildConfig.DEBUG
        if (showWsToasts) {
            Toast.makeText(this, "正在连接WebSocket (适配Nginx代理)...", Toast.LENGTH_SHORT).show()
        }
        
        // 10秒后如果连接失败，显示建议
        handler.postDelayed({
            if (!webSocketManager.isConnected()) {
                if (showWsToasts) {
                    Toast.makeText(this, "WebSocket连接超时，请检查服务端配置", Toast.LENGTH_LONG).show()
                }
                Log.d("MainActivity", "WebSocket连接超时，建议检查服务器配置")
            }
        }, 10000)
    }
    
    // ========== WebSocket回调方法 ==========
    
    override fun onConnected() {
        safeRunOnUiThread {
            if (BuildConfig.DEBUG) {
                Toast.makeText(this, "✅ WebSocket连接成功！", Toast.LENGTH_SHORT).show()
            }
            Log.d("MainActivity", "📊 WebSocket连接状态: ${webSocketManager.getConnectionStatus()}")
        }
    }
    
    override fun onDisconnected() {
        safeRunOnUiThread {
            if (BuildConfig.DEBUG) {
                Toast.makeText(this, "📴 WebSocket连接断开", Toast.LENGTH_SHORT).show()
            }
            Log.w("MainActivity", "WebSocket连接断开")
        }
    }
    
    override fun onLotteryCommand(command: LotteryCommand) {
        safeRunOnUiThread {
            Log.d("MainActivity", "🎯 收到抽奖指令: $command")
            Log.d("MainActivity", "  用户ID: ${command.userId}")
            Log.d("MainActivity", "  抽奖类型: ${command.lotteryType}")
            
            val typeDesc = lotteryProcessor.getLotteryTypeDescription(command.lotteryType)
            val userName = command.userName?.takeIf { it.isNotEmpty() } ?: command.userId
            
            Toast.makeText(this, "收到用户${userName}的${typeDesc}请求", Toast.LENGTH_SHORT).show()
            
            // 验证抽奖指令
            if (lotteryProcessor.validateLotteryCommand(command)) {
                Log.d("MainActivity", "抽奖指令验证通过，开始执行抽奖")
                // 执行抽奖逻辑
                processRemoteLottery(command)
            } else {
                Log.w("MainActivity", "无效的抽奖指令: $command")
                Toast.makeText(this, "无效的抽奖指令", Toast.LENGTH_SHORT).show()
            }
        }
    }
    
    override fun onError(error: String) {
        safeRunOnUiThread {
            if (BuildConfig.DEBUG) {
                Toast.makeText(this, "❌ WebSocket错误: $error", Toast.LENGTH_LONG).show()
            }
            Log.e("MainActivity", "❌ WebSocket错误: $error")
        }
    }
    
    override fun onDeviceRegistered(success: Boolean, message: String) {
        safeRunOnUiThread {
            if (success) {
                if (BuildConfig.DEBUG) {
                    Toast.makeText(this, "✅ 设备WebSocket注册成功！", Toast.LENGTH_SHORT).show()
                }
                Log.d("MainActivity", "✅ WebSocket设备注册成功: $message")
            } else {
                if (BuildConfig.DEBUG) {
                    Toast.makeText(this, "设备WebSocket注册失败: $message", Toast.LENGTH_LONG).show()
                }
                Log.e("MainActivity", "WebSocket设备注册失败: $message")
            }
        }
    }
    
    override fun onUserJoined(userInfo: JSONObject) {
        safeRunOnUiThread {
            try {
                val userName = userInfo.optString("user_name", "未知用户")
                val userPhone = userInfo.optString("user_phone", "")
                Log.d("MainActivity", "👤 用户加入游戏: $userName ($userPhone)")
                if (BuildConfig.DEBUG) {
                    Toast.makeText(this, "用户 $userName 加入了游戏", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Log.e("MainActivity", "❌ 解析用户连接信息失败", e)
            }
        }
    }
    
    override fun onLotteryStarted() {
        safeRunOnUiThread {
            Log.d("MainActivity", "🎯 抽奖开始事件")
            // 可以在这里添加抽奖开始的UI反馈
        }
    }
    
    override fun onLotteryResult(result: LotteryResult) {
        safeRunOnUiThread {
            Log.d("MainActivity", "🎯 抽奖结果事件: ${result.result}")
            // 可以在这里添加抽奖结果的额外处理
        }
    }
    
    /**
     * 处理远程抽奖请求
     */
    private fun processRemoteLottery(command: LotteryCommand) {
        if (isRunning) {
            // 如果正在抽奖，拒绝新的抽奖请求
            Toast.makeText(this, "抽奖进行中，请稍候...", Toast.LENGTH_SHORT).show()
            return
        }
        
        Log.d("MainActivity", "收到新的抽奖指令: $command")
        Log.d("MainActivity", "设备ID: ${command.deviceId}, 配置ID: ${command.configId}")
        
        // 保存当前抽奖指令
        currentLotteryCommand = command
        
        // 显示loading
        showLoading()
        
        // 清空当前显示的奖品
        prizeList.clear()
        // 清空奖品显示 - 使用空的资源ID列表
        lotteryWheel.setPrizeImages(emptyList())
        
        // 根据设备ID和配置ID重新获取奖品数据
        loadProfitPrizesFromApi(command.deviceId, command.configId, command)
    }
    
    /**
     * 显示loading界面
     */
    private fun showLoading() {
        loadingOverlay.visibility = View.VISIBLE
        loadingText.text = "正在加载奖品数据..."
        loadingProgress.text = "0/16"
    }
    
    /**
     * 更新loading进度
     */
    private fun updateLoadingProgress(loaded: Int, total: Int) {
        runOnUiThread {
            loadingProgress.text = "$loaded/$total"
            if (loaded < total) {
                loadingText.text = "正在加载奖品图片..."
            } else {
                loadingText.text = "加载完成！"
            }
        }
    }
    
    /**
     * 隐藏loading界面
     */
    private fun hideLoading() {
        runOnUiThread {
            loadingOverlay.visibility = View.GONE
        }
    }
    
    /**
     * 从API加载奖品数据：优先使用后端推荐的配置ID，失败回退默认演示奖池
     */
    private fun loadPrizesFromApi() {
        val deviceId = DeviceUtils.getDeviceId(this)
        showLoading()
        
        deviceRepository.initDevice(
            deviceId = deviceId,
            onSuccess = { initData ->
                safeRunOnUiThread {
                    val recommended = initData.recommendedConfigId ?: 26
                    val configId = recommended.toString()
                    Log.d("MainActivity", "使用推荐配置加载奖品: deviceId=$deviceId, configId=$configId")
                    // 使用分润接口加载奖品，不传command（不进入抽奖流程）
                    loadProfitPrizesFromApi(deviceId, configId, null)
                }
            },
            onError = { errorMsg ->
                safeRunOnUiThread {
                    Log.e("MainActivity", "设备初始化失败: $errorMsg")
                    // 统一接入设备未绑定提示，指导用户在后台绑定修复
                    handleApiErrorWithBindingTip(deviceId, errorMsg)
                    // 停止加载，避免误导性的回退奖池
                    hideLoading()
                }
            }
        )
    }

    /**
     * 清洗并填充奖品数据：
     * - 过滤无效记录（ID<=0、名称空）
     * - 修正 sort 到 0..15
     * - 处理重复 sort：保留首个，丢弃后续并记录日志
     * - 用已有有效奖品复制填充缺失的 sort，确保返回 16 个且 sort 唯一
     */
    private fun sanitizeAndFillPrizesTo16(rawPrizes: List<Prize>): List<Prize> {
        // 1) 基础过滤与修正（避免因可为空字段导致的NPE）
        val filtered = rawPrizes.mapNotNull { p ->
            try {
                if (p.id <= 0) return@mapNotNull null
                if (p.prizeName.isBlank()) return@mapNotNull null
                val clamped = p.sort.coerceIn(0, 15)
                // 使用copy调整sort，supplier为可空已避免NPE
                p.copy(sort = clamped)
            } catch (e: Exception) {
                Log.w("MainActivity", "sanitize: 跳过异常奖品记录: ${e.message}")
                null
            }
        }

        if (filtered.isEmpty()) return emptyList()

        // 2) 按 sort 放入 16 槽位，处理重复
        val slots = arrayOfNulls<Prize>(16)
        filtered.forEach { prize ->
            var targetIdx = prize.sort
            if (slots[targetIdx] == null) {
                slots[targetIdx] = prize
            } else {
                // 将重复的奖品移动到下一个可用槽位，避免丢弃中奖奖品
                var placed = false
                for (i in 0..15) {
                    if (slots[i] == null) {
                        slots[i] = prize.copy(sort = i)
                        Log.w(
                            "MainActivity",
                            "sanitize: 重复sort=${targetIdx}，将 ${prize.prizeName} 重新放置到空槽位 ${i}"
                        )
                        placed = true
                        break
                    }
                }
                if (!placed) {
                    Log.w(
                        "MainActivity",
                        "sanitize: 槽位已满，无法放置重复奖品 ${prize.prizeName}"
                    )
                }
            }
        }

        // 3) 收集可用的模板奖品
        val donors = slots.filterNotNull().ifEmpty { filtered }
        if (donors.isEmpty()) return emptyList()

        // 4) 用模板奖品填充缺失槽位（复制并改 sort）
        var donorIdx = 0
        for (i in 0..15) {
            if (slots[i] == null) {
                val donor = donors[donorIdx % donors.size]
                slots[i] = donor.copy(sort = i)
                donorIdx++
            }
        }

        // 返回唯一且完整的 16 个奖品列表（按 sort 排序）
        return slots.filterNotNull().sortedBy { it.sort }
    }
    
    /**
     * 将API获取的奖品数据设置到视图中（带加载进度）
     */
    private fun setupPrizesInViewWithLoading() {
        // 创建一个数组来按sort排序（sort现在从0开始，直接对应格子索引）
        val sortedPrizeArray = arrayOfNulls<Prize>(16)
        
        // 将奖品按sort字段放到对应位置（sort 0-15 直接对应数组索引 0-15）
        prizeList.forEach { prize ->
            val index = prize.sort  // sort从0开始，直接作为数组索引
            if (index in 0..15) {
                sortedPrizeArray[index] = prize
            }
        }
        
        // 跟踪图片加载进度
        val totalImages = sortedPrizeArray.filterNotNull().size
        var loadedImages = 0
        
        // 为每个格子设置奖品图片
        sortedPrizeArray.forEachIndexed { index, prize ->
            if (prize != null) {
                val iv = lotteryWheel.getPrizeImageViewOrNull(index)
                if (iv != null) {
                    // 使用带回调的图片加载方法
                    ImageLoader.loadPrizeImageWithCallback(
                        imageView = iv,
                        imageUrl = prize.prizeImage,
                        onLoadComplete = {
                            loadedImages++
                            updateLoadingProgress(loadedImages, totalImages)
                            
                            // 所有图片加载完成后隐藏loading
                            if (loadedImages >= totalImages) {
                                handler.postDelayed({
                                    hideLoading()
                                    Toast.makeText(this, "奖品数据加载成功！实际奖品：${totalImages}个", Toast.LENGTH_SHORT).show()
                                }, 500) // 稍微延迟一下，让用户看到100%
                            }
                        }
                    )
                } else {
                    Log.w("MainActivity", "setupPrizesInViewWithLoading: 跳过未就绪或非法格子 index=$index")
                    InvalidIndexMonitor.record("MainActivity", "load_skip_index=$index")
                }
                
                // 设置价格角标（优先显示原价）
                val priceText = when {
                    !prize.originalPrice.isNullOrBlank() -> "¥${prize.originalPrice}"
                    prize.price > 0 -> "¥${String.format("%.2f", prize.price)}"
                    else -> null
                }
                lotteryWheel.setPrizePrice(index, priceText)
                
                // 设置库存状态
                lotteryWheel.setPrizeDepletedStatus(index, prize.isDepleted)
                
                Log.d("MainActivity", "格子$index 设置奖品: ${prize.prizeName} (sort:${prize.sort}) 价格角标: ${priceText} 库存状态: ${if (prize.isDepleted == 1) "已耗尽" else "有库存"}")
            }
        }
        
        // 更新prizeList为正确排序
        prizeList.clear()
        sortedPrizeArray.filterNotNull().forEach { prizeList.add(it) }
        
        // 如果没有图片需要加载，直接隐藏loading
        if (totalImages == 0) {
            hideLoading()
        }
    }
    
    /**
     * 将API获取的奖品数据设置到视图中
     */
    private fun setupPrizesInView() {
        // 创建一个数组来按sort排序（sort现在从0开始，直接对应格子索引）
        val sortedPrizeArray = arrayOfNulls<Prize>(16)
        
        // 将奖品按sort字段放到对应位置（sort 0-15 直接对应数组索引 0-15）
        prizeList.forEach { prize ->
            val index = prize.sort  // sort从0开始，直接作为数组索引
            if (index in 0..15) {
                sortedPrizeArray[index] = prize
            }
        }
        
        // 为每个格子设置奖品图片
        sortedPrizeArray.forEachIndexed { index, prize ->
            if (prize != null) {
                val iv = lotteryWheel.getPrizeImageViewOrNull(index)
                if (iv != null) {
                    ImageLoader.loadPrizeImage(iv, prize.prizeImage)
                } else {
                    Log.w("MainActivity", "setupPrizesInView: 跳过未就绪或非法格子 index=$index")
                    InvalidIndexMonitor.record("MainActivity", "load_skip_index=$index")
                }
                // 设置价格角标（优先显示原价）
                val priceText = when {
                    !prize.originalPrice.isNullOrBlank() -> "¥${prize.originalPrice}"
                    prize.price > 0 -> "¥${String.format("%.2f", prize.price)}"
                    else -> null
                }
                lotteryWheel.setPrizePrice(index, priceText)
                
                // 设置库存状态
                lotteryWheel.setPrizeDepletedStatus(index, prize.isDepleted)
                
                Log.d("MainActivity", "格子$index 设置奖品: ${prize.prizeName} (sort:${prize.sort}) 价格角标: ${priceText} 库存状态: ${if (prize.isDepleted == 1) "已耗尽" else "有库存"}")
            }
        }
        
        // 更新prizeList为正确排序
        prizeList.clear()
        sortedPrizeArray.filterNotNull().forEach { prizeList.add(it) }
    }
    
    // 移除演示模式与默认奖品回退逻辑
    
    /**
     * 根据设备ID和配置ID获取分润奖品数据
     * @param deviceId 设备ID
     * @param configId 分润配置ID
     * @param command 抽奖指令（用于后续处理，如果为null则只加载奖品不执行抽奖）
     */
    private fun loadProfitPrizesFromApi(deviceId: String, configId: String, command: LotteryCommand?) {
        Log.d("MainActivity", "开始加载分润奖品数据: deviceId=$deviceId, configId=$configId")
        
        prizeRepository.getProfitPrizes(
            deviceId = deviceId,
            configId = configId,
            onSuccess = { prizes ->
                safeRunOnUiThread {
                    Log.d("MainActivity", "成功获取分润奖品数据: ${prizes.size}个奖品")
                    
                    // 清空并更新奖品列表
                    prizeList.clear()
                    
                    if (prizes.isNotEmpty()) {
                        // 清洗并填充为 16 个且 sort 唯一
                        val sanitized = sanitizeAndFillPrizesTo16(prizes)
                        prizeList.addAll(sanitized)
                        
                        // 设置奖品到视图中（带loading进度）
                        setupPrizesInViewWithLoading()
                        Log.d(
                            "MainActivity",
                            "分润奖品排序(清洗后): ${prizeList.map { "${it.prizeName}(sort:${it.sort})" }}"
                        )
                        
                        // 如果有抽奖指令，则奖品加载完成后开始执行抽奖逻辑
                        command?.let {
                            // 延迟一下确保loading完全消失后再开始抽奖
                            handler.postDelayed({
                                waitForPrizeViewsReadyThenExecute(it)
                            }, 1000)
                        }
                    } else {
                        // 无奖品数据：不再触发默认设备/配置兜底，直接提示并停止
                        hideLoading()
                        // 统一错误提示工具方法：引导后台绑定修复
                        handleApiErrorWithBindingTip(deviceId, "未获取到分润奖品数据")
                        command?.let { sendLotteryErrorResult(it, "no_prizes") }
                    }
                }
            },
            onError = { errorMsg ->
                safeRunOnUiThread {
                    Log.e("MainActivity", "获取分润奖品失败: $errorMsg")
                    // 统一接入设备未绑定提示，直接引导修复
                    handleApiErrorWithBindingTip(deviceId, errorMsg)
                    // 不再触发默认设备/配置兜底，直接停止并反馈错误
                    hideLoading()
                    command?.let { sendLotteryErrorResult(it, "api_error") }
                }
            }
        )
    }
    
    /**
     * 执行实际的抽奖逻辑（奖品加载完成后调用）
     */
    private fun executeActualLottery(command: LotteryCommand) {
        Log.d("MainActivity", "开始执行抽奖逻辑，奖品数量: ${prizeList.size}")
        // 当奖品为空时，直接终止抽奖并返回错误，避免白屏或越界
        if (prizeList.isEmpty()) {
            Log.w("MainActivity", "抽奖被取消：当前奖品数量为0")
            Toast.makeText(this, "当前无奖品可供抽奖，已取消", Toast.LENGTH_LONG).show()
            sendLotteryErrorResult(command, "no_prizes")
            return
        }
        
        // 调试：打印当前奖品列表信息
        Log.d("MainActivity", "=== 当前分润奖品列表信息 ===")
        prizeList.forEachIndexed { index, prize ->
            Log.d("MainActivity", "奖品[$index]: ID=${prize.id}, Name=${prize.prizeName}, Sort=${prize.sort}")
        }
        Log.d("MainActivity", "=== 奖品列表信息结束 ===")
        
        val userName = command.userName ?: command.userId
        Toast.makeText(this, "${userName}正在抽奖中...", Toast.LENGTH_SHORT).show()
        
        // 使用智能抽奖接口处理抽奖逻辑
        lotteryProcessor.processLottery(
            command = command,
            prizeList = prizeList,
            onSuccess = { result ->
                runOnUiThread {
                    // 保存当前抽奖结果
                    currentLotteryResult = result
                    
                    when (result.result) {
                        "win" -> {
                            // 必中奖：执行转盘动画到指定奖品
                            result.prizeInfo?.let { prizeInfo ->
                                Log.d("MainActivity", "智能抽奖成功: ${prizeInfo.name} (ID: ${prizeInfo.id})")
                                
                                // 找到对应的奖品在转盘中的位置（按ID查找，然后获取其sort作为位置）
                                val selectedPrize = prizeList.find { it.id == prizeInfo.id }
                                if (selectedPrize != null) {
                                    val prizeIndex = selectedPrize.sort // 使用sort作为格子位置
                                    Log.d("MainActivity", "找到奖品位置: $prizeIndex (sort: ${selectedPrize.sort})")
                                    startLottery(prizeIndex) // 执行转盘动画到指定奖品
                                } else {
                                    Log.w("MainActivity", "未找到奖品ID ${prizeInfo.id}，执行随机动画")
                                    startLottery() // 随机转盘动画
                                }
                            }
                        }
                        "error" -> {
                            Toast.makeText(this, "抽奖处理出错，请重试", Toast.LENGTH_SHORT).show()
                            // 发送错误结果
                            webSocketManager.sendLotteryResult(result)
                        }
                    }
                }
            },
            onError = { errorMsg ->
                safeRunOnUiThread {
                    Log.e("MainActivity", "智能抽奖失败: $errorMsg")
                    Toast.makeText(this, "抽奖失败: $errorMsg", Toast.LENGTH_LONG).show()
                    
                    // 抽奖失败时不发送结果给WebSocket，避免用户收到错误提示
                    // 只在本地显示错误信息，不影响用户的抽奖体验
                    Log.w("MainActivity", "抽奖失败，不发送错误结果到WebSocket: $errorMsg")
                }
            }
        )
    }

    /**
     * 等待奖品视图就绪后再执行抽奖，确保“先加载奖品→再执行抽奖”序列
     */
    private fun waitForPrizeViewsReadyThenExecute(command: LotteryCommand) {
        // 若奖品为空，直接返回错误，不等待视图就绪
        if (prizeList.isEmpty()) {
            Log.w("MainActivity", "跳过等待：奖品数量为0")
            sendLotteryErrorResult(command, "no_prizes")
            return
        }
        if (lotteryWheel.prizeViewsReady) {
            executeActualLottery(command)
            return
        }
        var attempts = 0
        val maxAttempts = 10
        fun check() {
            if (lotteryWheel.prizeViewsReady) {
                executeActualLottery(command)
            } else if (attempts++ < maxAttempts) {
                handler.postDelayed({ check() }, 100)
            } else {
                Log.w("MainActivity", "prizeViewsReady 未就绪，兜底执行抽奖")
                executeActualLottery(command)
            }
        }
        check()
    }
    
    /**
     * 发送抽奖错误结果
     */
    private fun sendLotteryErrorResult(command: LotteryCommand, errorType: String) {
        // 抽奖错误时不发送结果给WebSocket，避免用户收到ERROR_记录ID
        // 只在本地记录错误信息，不影响用户的抽奖体验
        Log.e("MainActivity", "抽奖错误但不发送到WebSocket: $errorType")
    }
    
    private fun setupRecords() {
        // 初始化中奖记录，对应Vue项目的records
        val timeFormat = SimpleDateFormat("HH:mm:ss", Locale.getDefault())
        val now = System.currentTimeMillis()
        
        for (i in 0 until 5) {
            val prizeIndex = i % maxOf(prizeList.size, 1)
            val prizeName = if (prizeList.isNotEmpty()) prizeList[prizeIndex].prizeName else "奖品${i + 1}"
            val prizeValue = "¥${(i + 1) * 100}" // 使用模拟价值
            
            recordsList.add(
                WinRecord(
                    user = "用户***${i + 1}",
                    award = arrayOf("一等奖", "二等奖", "三等奖", "四等奖", "参与奖")[i],
                    time = timeFormat.format(Date(now - i * 16000)),
                    result = prizeName,
                    value = prizeValue
                )
            )
        }
        
        recordsAdapter = WinRecordsAdapter(recordsList)
        lotteryWheel.setRecordsAdapter(recordsAdapter)
    }
    
    private fun setupWheel() {
        lotteryWheel.onStartClick = {
            if (prizeList.isEmpty()) {
                Log.w("MainActivity", "用户点击抽奖，但当前奖品数量为0，忽略")
                Toast.makeText(this, "当前无奖品，无法抽奖", Toast.LENGTH_SHORT).show()
            } else {
                startLottery()
            }
        }
    }
    
    /**
     * 开始抽奖动画
     * @param targetIndex 指定的目标奖品位置，-1表示随机
     */
    private fun startLottery(targetIndex: Int = -1) {
        Log.d("MainActivity", "🎰 开始抽奖动画，目标位置: $targetIndex")
        // 防御：无奖品时不允许启动动画，避免后续展示与索引造成崩溃
        if (prizeList.isEmpty()) {
            Log.w("MainActivity", "开始抽奖被拒绝：奖品数量为0")
            Toast.makeText(this, "当前无奖品，无法抽奖", Toast.LENGTH_SHORT).show()
            return
        }
        if (targetIndex >= 0 && targetIndex < prizeList.size) {
            val targetPrize = prizeList.find { it.sort == targetIndex }
            Log.d("MainActivity", "🎯 目标格子 $targetIndex 的奖品: ${targetPrize?.prizeName} (ID: ${targetPrize?.id})")
        }
        if (isRunning) {
            Log.w("MainActivity", "抽奖已在进行中，忽略新的抽奖请求")
            return
        }
        
        runOnce(targetIndex)
    }
    
    private fun runOnce(targetIndex: Int = -1) {
        if (isRunning) {
            Log.w("MainActivity", "转盘已在运行中，忽略新的请求")
            return
        }
        
        Log.d("MainActivity", "🎯 启动转盘动画，目标: $targetIndex")
        
        // 保存目标位置，确保动画结束时使用
        val finalTarget = targetIndex
        
        isRunning = true
        
        // 读取抽奖总时长配置（用于后续间隔生成）
        val configuredDurationMs = try { BuildConfig.LOTTERY_DURATION_MS } catch (e: Exception) { 6000 }
        
        // 对应Vue项目的抽奖逻辑，但缩短时长增强体验
        val total = 16
        val target = if (targetIndex >= 0 && targetIndex < total) {
            targetIndex // 使用指定的目标位置
        } else {
            Random.nextInt(total) // 随机目标位置
        }
        
        // 使用构建常量控制抽奖总时长（单位ms），默认5秒（已在上方取得 configuredDurationMs）
        // 放宽时长上限，支持12秒及更长的配置
        val desiredDuration = configuredDurationMs.coerceIn(3000, 20000).toLong()
        val baseCycles = if (desiredDuration <= 6000L) 3 else 4
        
        // 计算从当前位置到目标位置需要的步数
        val currentPos = activeIndex
        // 因为每次spin()会先执行 activeIndex = (activeIndex + 1) % total
        // 所以从-1开始，第一步会变成0
        val nextPos = (currentPos + 1) % total
        val targetSteps = if (target >= nextPos) {
            target - nextPos
        } else {
            total - nextPos + target
        }
        val steps = baseCycles * total + targetSteps
        
        Log.d("MainActivity", "🎯 转盘计算：target=$target, currentPos=$currentPos, nextPos=$nextPos, total=$total, baseCycles=$baseCycles")
        Log.d("MainActivity", "🎯 目标步数：targetSteps=$targetSteps, 总步数：steps=$steps (${baseCycles}圈 + ${targetSteps}步)")
        Log.d("MainActivity", "🎯 预期最终位置：$target")
        
        // 生成间隔时间数组，优化为更激动人心的体验
        val intervals = generateSpinIntervals(steps, desiredDuration)
        
        Log.d("MainActivity", "🎯 实际生成的间隔数组长度：${intervals.size}，预期步数：$steps")
        if (intervals.size != steps) {
            Log.w("MainActivity", "⚠️ 间隔数组长度与预期步数不匹配！")
        }
        
        var step = 0
        val sprintStartStep = (intervals.size * 0.23).toInt() // 对应Vue的冲刺开始
        val sprintEndStep = (intervals.size * 0.90).toInt()   // 对应Vue的冲刺结束

        // 精准按动画阶段对齐音频：前奏持续到冲刺开始，随后进入循环
        var introDelayMs = 0L
        for (i in 0 until sprintStartStep.coerceAtLeast(0)) {
            introDelayMs += intervals[i]
        }
        // 按音频输出路由加入延时校正（蓝牙/耳机/扬声器）
        introDelayMs += AudioRouteUtils.computeRouteDelayMs(this)
        // 请求音频焦点，避免外部音源干扰
        audioFocusHelper.requestFocus()
        // 启动三段音乐时间线
        soundManager?.startIntroThenLoop(introDelayMs)
        
        fun spin() {
            activeIndex = (activeIndex + 1) % total
            lotteryWheel.activeIndex = activeIndex
            
            // 只在关键步骤打印日志，避免刷屏
            if (step % 10 == 0 || step >= intervals.size - 5) {
                Log.d("MainActivity", "🎯 转盘步骤：step=$step, activeIndex=$activeIndex")
            }
            
            // 如果已经到达目标位置且接近结束，提前停止
            if (finalTarget >= 0 && activeIndex == finalTarget && step >= intervals.size - 10) {
                Log.d("MainActivity", "🎯 提前到达目标位置：$finalTarget，提前结束动画")
                // 抽奖完成
                isRunning = false
                spinTimer = null
                lotteryWheel.stopSprinting()
                
                // 音频切换到收尾（统一使用三段音乐管理）
                soundManager?.switchToOutro()

                // 延迟0.5秒后展示结果
                handler.postDelayed({
                    Log.d("MainActivity", "🎯 准备显示结果，当前activeIndex=$activeIndex")
                    
                    // 显示实际的抽奖结果，而不是转盘停止位置的奖品
                    currentLotteryResult?.let { result ->
                        if (result.result == "win" && result.prizeInfo != null) {
                            // 找到实际中奖的奖品信息
                            val actualPrize = prizeList.find { it.id == result.prizeInfo.id }
                            if (actualPrize != null) {
                                Log.d("MainActivity", "🎯 显示中奖奖品：${actualPrize.prizeName} (ID: ${actualPrize.id}, Sort: ${actualPrize.sort})")
                                showWinner(actualPrize)
                            } else {
                                Log.w("MainActivity", "未匹配到中奖奖品，取消展示")
                                currentLotteryCommand?.let { sendLotteryErrorResult(it, "no_prize_match") }
                                Toast.makeText(this, "未匹配到中奖奖品，取消展示", Toast.LENGTH_SHORT).show()
                            }
                        } else {
                            // 不做任何兜底展示：仅记录并发送错误
                            Log.w("MainActivity", "未匹配到中奖奖品，取消展示")
                            currentLotteryCommand?.let { sendLotteryErrorResult(it, "no_prize_match") }
                            Toast.makeText(this, "未匹配到中奖奖品，取消展示", Toast.LENGTH_SHORT).show()
                        }
                    } ?: run {
                        // 抽奖结果为空：不展示
                        Log.w("MainActivity", "抽奖结果为空，取消展示")
                        currentLotteryCommand?.let { sendLotteryErrorResult(it, "no_result") }
                    }
                }, 500)
                return
            }
            
            // 冲刺模式视觉效果
            if (step >= sprintStartStep && step <= sprintEndStep) {
                lotteryWheel.startSprinting()
            } else if (step > sprintEndStep) {
                lotteryWheel.stopSprinting()
            }
            
            if (step >= intervals.size - 1) {
                // 抽奖完成 - 强制设置到目标位置
                if (finalTarget >= 0 && finalTarget < total) {
                    activeIndex = finalTarget
                    lotteryWheel.activeIndex = activeIndex
                    Log.d("MainActivity", "🎯 强制设置到目标位置：$finalTarget，当前activeIndex=$activeIndex")
                }
                
                isRunning = false
                spinTimer = null
                lotteryWheel.stopSprinting()
                
                // 音频切换到收尾（统一使用三段音乐管理）
                soundManager?.switchToOutro()

                // 延迟0.5秒后展示结果
                handler.postDelayed({
                    Log.d("MainActivity", "🎯 准备显示结果，当前activeIndex=$activeIndex")
                    
                    // 显示实际的抽奖结果，而不是转盘停止位置的奖品
                    currentLotteryResult?.let { result ->
                        if (result.result == "win" && result.prizeInfo != null) {
                            // 找到实际中奖的奖品信息
                            val actualPrize = prizeList.find { it.id == result.prizeInfo.id }
                            if (actualPrize != null) {
                                Log.d("MainActivity", "🎯 显示中奖奖品：${actualPrize.prizeName} (ID: ${actualPrize.id}, Sort: ${actualPrize.sort})")
                                showWinner(actualPrize)
                            } else {
                                Log.w("MainActivity", "未匹配到中奖奖品，取消展示")
                                currentLotteryCommand?.let { sendLotteryErrorResult(it, "no_prize_match") }
                                Toast.makeText(this, "未匹配到中奖奖品，取消展示", Toast.LENGTH_SHORT).show()
                            }
                        } else {
                            // 不做任何兜底展示：仅记录并发送错误
                            Log.w("MainActivity", "未匹配到中奖奖品，取消展示")
                            currentLotteryCommand?.let { sendLotteryErrorResult(it, "no_prize_match") }
                            Toast.makeText(this, "未匹配到中奖奖品，取消展示", Toast.LENGTH_SHORT).show()
                        }
                    } ?: run {
                        // 抽奖结果为空：不展示
                        Log.w("MainActivity", "抽奖结果为空，取消展示")
                        currentLotteryCommand?.let { sendLotteryErrorResult(it, "no_result") }
                    }
                }, 500)
                return
            }
            
            val delay = intervals[step]
            step++
            spinTimer = Runnable { spin() }
            handler.postDelayed(spinTimer!!, delay)
        }
        
        spin()
    }
    
    private fun generateSpinIntervals(steps: Int, totalDuration: Long): LongArray {
        // 完全按照Vue项目的四阶段动画间隔生成算法
        val intervals = mutableListOf<Long>()
        val minDelay = 16L
        val maxDelay = 240L
        
        // Vue的阶段配比：5% / 18% / 67% / 10%
        val phase1Portion = 0.05f
        val phase2Portion = 0.18f  
        val phase3Portion = 0.67f
        val phase4Portion = 0.10f
        
        val phase1End = phase1Portion
        val phase2End = phase1Portion + phase2Portion
        val phase3End = phase2End + phase3Portion
        
        val midDelay1 = minDelay + (maxDelay - minDelay) * 0.30f
        val midDelay2 = minDelay + (maxDelay - minDelay) * 0.06f
        val endDelayFactor = 0.68f
        val endDelay = minDelay + (maxDelay - minDelay) * endDelayFactor
        
        // 缓动函数
        fun easeOutCubic(t: Float) = 1f - (1f - t).pow(3f)
        fun easeInExpo(t: Float) = if (t == 0f) 0f else 2f.pow(10f * (t - 1f))
        fun easeInSuper(t: Float) = t.pow(4f)
        fun easeOutSine(t: Float) = sin(t * PI.toFloat() / 2f)
        
        // 四个阶段的数组
        val phase1Arr = mutableListOf<Float>()
        val phase2Arr = mutableListOf<Float>()
        val phase3Arr = mutableListOf<Float>()
        val phase4Arr = mutableListOf<Float>()
        
        // 生成各阶段的基础延迟
        for (i in 0 until steps) {
            val p = i.toFloat() / (steps - 1)
            val delay: Float
            
            when {
                p < phase1End -> {
                    val local = p / phase1Portion
                    val k = easeOutCubic(local)
                    delay = maxDelay - (maxDelay - midDelay1) * k
                    phase1Arr.add(delay)
                }
                p < phase2End -> {
                    val local = (p - phase1End) / phase2Portion
                    val k = easeInExpo(local)
                    delay = midDelay1 - (midDelay1 - midDelay2) * k
                    phase2Arr.add(delay)
                }
                p < phase3End -> {
                    val local = (p - phase2End) / phase3Portion
                    val accelCut = 0.35f
                    delay = if (local < accelCut) {
                        val sub = local / accelCut
                        val k = easeInSuper(sub)
                        val targetD = minDelay + 1
                        midDelay2 - (midDelay2 - targetD) * k
                    } else {
                        val sub = (local - accelCut) / (1 - accelCut)
                        val base = minDelay + 1
                        val wobble = sin(sub * PI * 8.5).toFloat() * 0.9f + (Random.nextFloat() * 1.0f - 0.5f)
                        base + wobble
                    }
                    phase3Arr.add(max(minDelay.toFloat(), delay))
                }
                else -> {
                    val local = (p - phase3End) / phase4Portion
                    val k = easeOutSine(local)
                    delay = (minDelay + 3) + (endDelay - (minDelay + 3)) * k
                    phase4Arr.add(delay)
                }
            }
        }
        
        // 时间缩放以匹配目标总时长
        val desired = totalDuration.toFloat()
        val targetTimes = floatArrayOf(
            desired * phase1Portion,
            desired * phase2Portion, 
            desired * phase3Portion,
            desired * phase4Portion
        )
        
        fun scalePhase(arr: List<Float>, target: Float): List<Long> {
            val sum = arr.sum()
            val scale = if (sum > 0) target / sum else 1f
            return arr.map { max(minDelay, (it * scale).toLong()) }
        }
        
        val p1Scaled = scalePhase(phase1Arr, targetTimes[0])
        val p2Scaled = scalePhase(phase2Arr, targetTimes[1])
        val p4Scaled = scalePhase(phase4Arr, targetTimes[3])
        
        // 冲刺阶段：保持高速，用重复扩展时间
        val sprintArr = mutableListOf<Long>()
        val sprintTarget = targetTimes[2]
        val baseSprintPattern = phase3Arr.ifEmpty { listOf(minDelay.toFloat()) }
        
        while (sprintArr.sum() < sprintTarget) {
            for (d in baseSprintPattern) {
                if (sprintArr.sum() >= sprintTarget) break
                val wobble = Random.nextFloat() * 2 - 1
                sprintArr.add(max(minDelay, (d + wobble).toLong()))
            }
        }
        
        // 根据总时长动态确保冲刺阶段最少圈数，短时长减少以避免被拉长
        val total = 16
        val sprintMinLaps = if (totalDuration <= 6000L) 6 else 10
        val currentLaps = sprintArr.size / total
        if (currentLaps < sprintMinLaps) {
            val neededLaps = sprintMinLaps - currentLaps
            repeat(neededLaps * total) {
                val wobble = Random.nextFloat() * 1.6f - 0.8f
                sprintArr.add(max(minDelay, (minDelay + wobble).toLong()))
            }
        }
        
        // 合并所有阶段
        intervals.addAll(p1Scaled)
        intervals.addAll(p2Scaled)
        intervals.addAll(sprintArr)
        intervals.addAll(p4Scaled)
        
        return intervals.toLongArray()
    }
    
    private fun showWinner(prize: Prize) {
        Log.d("MainActivity", "🎯 showWinner调用：显示奖品=${prize.prizeName} (ID: ${prize.id}, Sort: ${prize.sort})")
        Log.d("MainActivity", "🎯 当前转盘位置：activeIndex=$activeIndex")
        Log.d("MainActivity", "🎯 转盘位置对应的奖品：${if (activeIndex >= 0 && activeIndex < prizeList.size) prizeList.find { it.sort == activeIndex }?.prizeName ?: "未找到" else "无效位置"}")
        
        // 发送抽奖结果到服务器（动画结束后）
        currentLotteryResult?.let { result ->
            Log.d("MainActivity", "发送抽奖结果到服务器")
            webSocketManager.sendLotteryResult(result)
            
            // 显示中奖结果消息
            currentLotteryCommand?.let { command ->
                if (result.result == "win") {
                    result.prizeInfo?.let { prizeInfo ->
                        val userName = command.userName ?: command.userId
                        Toast.makeText(this, "🎉 恭喜${userName}中奖：${prizeInfo.name}！", Toast.LENGTH_LONG).show()
                    }
                }
            }
            
            // 清除当前抽奖信息
            currentLotteryCommand = null
            currentLotteryResult = null
        }
        
        // 使用Glide加载网络图片
        ImageLoader.loadPrizeImage(winPrizeImage, prize.prizeImage)
        winPrizeName.text = prize.prizeName
        
        // 对应Vue的winner-overlay显示
        winnerOverlay.visibility = View.VISIBLE
        winnerOverlay.alpha = 0f
        
        // 获取中奖卡片视图 (找到CardView子视图)
        val winnerCard = findCardViewInOverlay(winnerOverlay)
        
        // 设置初始状态 - 对应Vue的popIn动画开始状态
        winnerCard?.apply {
            scaleX = 0.4f
            scaleY = 0.4f  
            rotation = -6f
            alpha = 0f
        }
        
        // 对应Vue的winner-fade-enter-active动画
        winnerOverlay.animate()
            .alpha(1f)
            .setDuration(450) // 对应Vue的.45s
            .start()
        
        // 对应Vue的popIn动画 - cubic-bezier(.26,1.4,.48,1)
        winnerCard?.animate()
            ?.scaleX(1.05f) // 先到1.05倍
            ?.scaleY(1.05f)
            ?.rotation(0f)
            ?.alpha(1f)
            ?.setDuration(360) // 60%的时间到1.05倍
            ?.withEndAction {
                // 然后回到正常大小
                winnerCard.animate()
                    .scaleX(1f)
                    .scaleY(1f)
                    .setDuration(240) // 剩余40%时间
                    .start()
            }
            ?.start()
        
        // 奖品图片的bounce弹跳动画 - 对应Vue的bounce 2.8s ease-in-out infinite
        winPrizeImage.postDelayed({
            startBounceAnimation(winPrizeImage)
        }, 600) // 等popIn完成后开始
        
        // 添加到中奖记录
        val timeFormat = SimpleDateFormat("HH:mm:ss", Locale.getDefault())
        val newRecord = WinRecord(
            user = "用户***${recordsList.size + 1}",
            award = when (prize.id) {
                1, 2, 3 -> "一等奖"
                4, 5, 6 -> "二等奖"
                7, 8, 9 -> "三等奖"
                else -> "参与奖"
            },
            time = timeFormat.format(Date()),
            result = prize.prizeName,
            value = "¥${prize.id * 100}" // 使用模拟价值
        )
        
        recordsList.add(0, newRecord) // 添加到顶部
        if (recordsList.size > 10) { // 保持最多10条记录
            recordsList.removeAt(recordsList.size - 1)
        }
        recordsAdapter.notifyDataSetChanged()
        
        // 5秒倒计时自动关闭
        startWinnerCountdown(5)
    }
    
    // 对应Vue项目的bounce动画
    private fun startBounceAnimation(view: View) {
        val bounceAnimator = ObjectAnimator.ofFloat(view, "translationY", 0f, -12f, 0f).apply {
            duration = 2800 // 对应Vue的2.8s
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.RESTART
            interpolator = AccelerateDecelerateInterpolator() // ease-in-out
        }
        
        view.tag = bounceAnimator // 保存引用用于停止
        bounceAnimator.start()
    }
    
    private fun stopBounceAnimation(view: View) {
        (view.tag as? ObjectAnimator)?.cancel()
        view.tag = null
        view.translationY = 0f
    }
    
    // 辅助方法：查找ViewGroup中的CardView
    private fun findCardViewInOverlay(parent: View): CardView? {
        if (parent is CardView) {
            return parent
        }
        if (parent is ViewGroup) {
            for (i in 0 until parent.childCount) {
                val child = parent.getChildAt(i)
                val result = findCardViewInOverlay(child)
                if (result != null) return result
            }
        }
        return null
    }
    
    private fun startWinnerCountdown(seconds: Int) {
        var countdown = seconds
        winCountdown.text = "窗口将于 ${countdown}s 后自动关闭"
        
        val countdownRunnable = object : Runnable {
            override fun run() {
                countdown--
                if (countdown > 0) {
                    winCountdown.text = "窗口将于 ${countdown}s 后自动关闭"
                    handler.postDelayed(this, 1000)
                } else {
                    closeWinner()
                }
            }
        }
        handler.postDelayed(countdownRunnable, 1000)
    }
    
    private fun closeWinner() {
        // 停止弹跳动画
        stopBounceAnimation(winPrizeImage)
        
        // 对应Vue的winner-fade-leave-active动画
        winnerOverlay.animate()
            .alpha(0f)
            .setDuration(450) // 对应Vue的.45s
            .withEndAction {
                winnerOverlay.visibility = View.GONE
                // 重置状态
                winnerOverlay.alpha = 1f
                winPrizeImage.translationY = 0f
            }
            .start()
    }
    
    private fun playStartSound() {
        try {
            startSound?.seekTo(0)
            startSound?.start()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }
    
    private fun playSpinSound() {
        try {
            spinSound?.seekTo(0)
            spinSound?.start()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }
    
    private fun stopSpinSound() {
        try {
            spinSound?.pause()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }
    
    private fun playEndSound() {
        try {
            endSound?.seekTo(0)
            endSound?.start()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }
    
    override fun onResume() {
        super.onResume()
        
        // 重新设置沉浸式全屏模式
        window.decorView.systemUiVisibility = (
            View.SYSTEM_UI_FLAG_IMMERSIVE_STICKY
            or View.SYSTEM_UI_FLAG_LAYOUT_STABLE
            or View.SYSTEM_UI_FLAG_LAYOUT_HIDE_NAVIGATION
            or View.SYSTEM_UI_FLAG_LAYOUT_FULLSCREEN
            or View.SYSTEM_UI_FLAG_HIDE_NAVIGATION
            or View.SYSTEM_UI_FLAG_FULLSCREEN
        )
        
        // 应用回到前台时重新连接WebSocket
        if (::webSocketManager.isInitialized && !webSocketManager.isConnected()) {
            webSocketManager.connect()
        }

        // 如果奖品尚未加载或视图未就绪，前台恢复时自动预加载
        try {
            if (prizeList.isEmpty() || !lotteryWheel.prizeViewsReady) {
                Log.d("MainActivity", "onResume: 奖品为空或视图未就绪，触发预加载")
                loadPrizesFromApi()
            }
        } catch (e: Exception) {
            Log.w("MainActivity", "onResume: 预加载检查异常: ${e.message}")
        }
    }
    
    override fun onPause() {
        super.onPause()
        // 应用进入后台时不断开WebSocket，保持连接以接收抽奖指令
    }
    
    override fun onDestroy() {
        super.onDestroy()
        
        // 释放音效资源
        releaseAudioPlayers()
        soundManager?.release()
        
        // 断开WebSocket连接
        if (::webSocketManager.isInitialized) {
            webSocketManager.disconnect()
        }
        
        // 清理定时器
        spinTimer?.let { handler.removeCallbacks(it) }
    }
    
    private fun releaseAudioPlayers() {
        // 统一释放三段音乐资源
        soundManager?.release()
    }
    
    /**
     * 生成并设置二维码到轮盘中心（远程API方式）
     */
    private fun generateAndSetQRCode() {
        try {
            val deviceId = DeviceUtils.getDeviceId(this)
            val page: String = "pages/index/index" // 按接口文档默认首页

            // 先尝试生成小程序码，失败则回退到通用二维码
            qrCodeRepository.generateMiniCode(
                deviceCode = deviceId,
                page = page,
                onSuccess = { url ->
                    safeRunOnUiThread { lotteryWheel.setQRCode(url) }
                },
                onError = { err ->
                    Log.w("MainActivity", "小程序码生成失败：$err，回退到通用二维码")
                    qrCodeRepository.generateQrCode(
                        deviceCode = deviceId,
                        page = page,
                        onSuccess = { url ->
                            safeRunOnUiThread { lotteryWheel.setQRCode(url) }
                        },
                        onError = { err2 ->
                            Log.e("MainActivity", "二维码生成失败：$err2")
                        }
                    )
                }
            )
        } catch (e: Exception) {
            Log.e("MainActivity", "生成二维码过程异常", e)
        }
    }
    
    /**
     * 检查设备初始化状态
     * @param deviceId 设备ID
     */
    private fun checkDeviceInitStatus(deviceId: String) {
        deviceRepository.initDevice(
            deviceId = deviceId,
            onSuccess = { initData ->
                runOnUiThread {
                    // 只在设备未完全初始化或奖品不足时才弹窗
                    if (!initData.isInitialized || !initData.prizeCheck.sufficient) {
                        showDeviceInitStatusDialog(initData)
                    } else {
                        Toast.makeText(this, "设备状态良好，可正常使用", Toast.LENGTH_SHORT).show()
                    }
                }
            },
            onError = { errorMsg ->
                runOnUiThread {
                    // 如果初始化接口调用失败，显示简单的设备ID对话框
                    showSimpleDeviceIdDialog(deviceId, errorMsg)
                }
            }
        )
    }
    
    /**
     * 显示设备初始化状态对话框
     * @param initData 设备初始化数据
     */
    private fun showDeviceInitStatusDialog(initData: DeviceInitData) {
        Log.d("MainActivity", "显示设备初始化弹窗")
        Log.d("MainActivity", "设备ID: ${initData.deviceId}")
        Log.d("MainActivity", "初始化状态: ${initData.isInitialized}")
        Log.d("MainActivity", "状态消息: ${initData.message}")
        Log.d("MainActivity", "缺失角色数量: ${initData.missingRoles.size}")
        Log.d("MainActivity", "绑定角色数量: ${initData.boundRoles.size}")
        
        // 将弹窗作为欢迎页呈现：标题统一为欢迎加入异起利是
        val title = "欢迎加入异起利是"

        val msgHtml = buildString {
            // 友好欢迎语（说明当前状态），作为正文开头
            val statusText = if (initData.isInitialized) {
                "<b>状态：</b>设备已初始化，可正常使用。"
            } else {
                "<b>状态：</b><font color='#FF3333'>设备初始化不完整，需要完善配置。</font>"
            }

            append(statusText)
            append("<br/><br/>")

            // 基本信息
            append("<b>设备ID：</b>${initData.deviceId}<br/>")
            // 安装位置展示：若需要定位授权则引导
            val locDisplay = if (initData.needLocationPermission) {
                "<font color='#FF3333'>未授权定位（点击下方“授权定位”按钮获取）</font>"
            } else initData.deviceInfo.installLocation
            append("<b>安装位置：</b>$locDisplay<br/>")
            append("<b>商户：</b>${initData.deviceInfo.merchant}<br/>")
            append("<br/>")

            // 分润与奖品检测
            append("<b>分润配置与奖品检测</b><br/>")
            append("• ${initData.prizeCheck.message}<br/>")
            append("• 当前奖品数量：${initData.prizeCheck.currentCount}<br/>")
            append("• 要求最少数量：${initData.prizeCheck.requiredCount}<br/>")
            if (initData.prizeCheck.sufficient) {
                append("• 状态：✓ 奖品数量充足<br/>")
            } else {
                append("• 状态：<font color='#FF3333'>⚠ 奖品数量不足</font><br/>")
            }
            append("<br/>")

            // 缺失角色高亮
            if (!initData.isInitialized && initData.missingRoles.isNotEmpty()) {
                append("<b>缺失的角色：</b><br/>")
                initData.missingRoles.forEach { role ->
                    append("• <font color='#FF3333'>${role.name}</font><br/>")
                }
                append("<br/>")
            }

            // 已绑定角色
            if (initData.boundRoles.isNotEmpty()) {
                append("<b>已绑定的角色：</b><br/>")
                initData.boundRoles.forEach { role ->
                    append("• ${role.name} (ID: ${role.adminId})<br/>")
                }
            }
        }

        Log.d("MainActivity", "弹窗完整内容(HTML): $msgHtml")

        val spanned = HtmlCompat.fromHtml(msgHtml, HtmlCompat.FROM_HTML_MODE_LEGACY)

        val dialog = AlertDialog.Builder(this)
            .setTitle(title)
            .setMessage(spanned)
            .setPositiveButton("复制设备ID", null) // 稍后覆盖点击，避免自动关闭
            .setNeutralButton("授权定位", null)
            .setCancelable(true) // 允许点击外部关闭
            .create()
        // 允许点击对话框外部区域直接关闭
        dialog.setCanceledOnTouchOutside(true)

        // 添加右上角关闭按钮
        dialog.setOnShowListener {
            // 获取对话框窗口
            val window = dialog.window
            window?.let {
                // 创建关闭按钮
                val closeButton = android.widget.ImageButton(this)
                closeButton.setImageResource(android.R.drawable.ic_menu_close_clear_cancel)
                closeButton.background = null
                closeButton.setPadding(16, 16, 16, 16)
                closeButton.contentDescription = "关闭"
                
                // 设置关闭按钮点击事件（仅关闭，不做任何流程干预）
                closeButton.setOnClickListener {
                    dialog.dismiss()
                }
                
                // 将关闭按钮添加到对话框的装饰视图中
                val decorView = window.decorView as? ViewGroup
                decorView?.let { parent ->
                    val layoutParams = ViewGroup.MarginLayoutParams(
                        ViewGroup.LayoutParams.WRAP_CONTENT,
                        ViewGroup.LayoutParams.WRAP_CONTENT
                    )
                    layoutParams.setMargins(0, 20, 20, 0)
                    
                    // 创建一个容器来放置关闭按钮
                    val buttonContainer = android.widget.FrameLayout(this)
                    val containerParams = ViewGroup.LayoutParams(
                        ViewGroup.LayoutParams.MATCH_PARENT,
                        ViewGroup.LayoutParams.WRAP_CONTENT
                    )
                    
                    val buttonParams = android.widget.FrameLayout.LayoutParams(
                        android.widget.FrameLayout.LayoutParams.WRAP_CONTENT,
                        android.widget.FrameLayout.LayoutParams.WRAP_CONTENT,
                        android.view.Gravity.TOP or android.view.Gravity.END
                    )
                    buttonParams.setMargins(0, 20, 20, 0)
                    
                    buttonContainer.addView(closeButton, buttonParams)
                    parent.addView(buttonContainer, 0, containerParams)
                }
            }

            // 复制按钮：不关闭弹窗
            dialog.getButton(AlertDialog.BUTTON_POSITIVE)?.setOnClickListener {
                val clipboard = getSystemService(CLIPBOARD_SERVICE) as ClipboardManager
                val clip = ClipData.newPlainText("设备ID", initData.deviceId)
                clipboard.setPrimaryClip(clip)
                Toast.makeText(this, "设备ID已复制到剪贴板", Toast.LENGTH_SHORT).show()
            }

            // 授权定位按钮：申请权限并提交安装位置到后端
            dialog.getButton(AlertDialog.BUTTON_NEUTRAL)?.setOnClickListener {
                requestLocationPermissionAndUpdate(initData)
            }

            // 不再使用底部“关闭”按钮，用户可通过右上角关闭或点击外部关闭
        }
        dialog.show()
    }

    private fun requestLocationPermissionAndUpdate(initData: DeviceInitData) {
        val hasFine = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
        val hasCoarse = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED
        if (!hasFine && !hasCoarse) {
            ActivityCompat.requestPermissions(this, arrayOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION), REQUEST_LOCATION_PERMISSION)
            return
        }

        val ssid = NetworkUtils.getCurrentWifiSsid(this) ?: "-"
        val bssid = NetworkUtils.getCurrentWifiBssid(this) ?: "-"
        val ip = NetworkUtils.getLocalIpV4(this) ?: "-"
        val locationText = "Android设备 | WiFi:${ssid}(${bssid}) | IP:${ip}"

        deviceRepository.updateInstallLocation(
            context = this,
            installLocation = locationText,
            onSuccess = {
                Toast.makeText(this, "安装位置已更新", Toast.LENGTH_SHORT).show()
                // 不在APP侧进行任何初始化条件判断，所有校验由后端负责
            },
            onError = { msg ->
                Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()
            }
        )
    }
    
    /**
     * 显示简单的设备ID对话框（当初始化接口调用失败时）
     * @param deviceId 设备ID
     * @param errorMsg 错误信息
     */
    private fun showSimpleDeviceIdDialog(deviceId: String, errorMsg: String) {
        AlertDialog.Builder(this)
            .setTitle("设备配置错误")
            .setMessage("设备未正确配置或注册，无法获取奖品数据。\n\n设备ID：$deviceId\n\n错误信息：$errorMsg\n\n后台入口：http://127.0.0.1:8000/index.php?s=/admin/device\n登录地址（二维码）：https://yiqilishi.com.cn/gWRGDVqZTd.php\n请在后台完成设备绑定后点击“刷新重试”。")
            .setPositiveButton("复制设备ID") { _, _ ->
                val clipboard = getSystemService(CLIPBOARD_SERVICE) as ClipboardManager
                val clip = ClipData.newPlainText("设备ID", deviceId)
                clipboard.setPrimaryClip(clip)
                Toast.makeText(this, "设备ID已复制到剪贴板", Toast.LENGTH_SHORT).show()
            }
            .setNeutralButton("刷新重试") { dialog, _ ->
                dialog.dismiss()
                showLoading()
                loadPrizesFromApi()
            }
            .setNegativeButton("确定") { dialog, _ ->
                dialog.dismiss()
                // 不做任何本地回退展示
                hideLoading()
            }
            .setCancelable(false)
            .show()
    }

    /**
     * 显示设备ID对话框（保留原方法作为备用）
     * @param deviceId 设备ID
     */
    private fun showDeviceIdDialog(deviceId: String) {
        AlertDialog.Builder(this)
            .setTitle("设备配置错误")
            .setMessage("设备未正确配置或注册，无法获取奖品数据。\n\n设备ID：$deviceId\n\n后台入口：http://127.0.0.1:8000/index.php?s=/admin/device\n登录地址（二维码）：https://yiqilishi.com.cn/gWRGDVqZTd.php\n请在后台完成设备绑定后点击“刷新重试”。")
            .setPositiveButton("复制设备ID") { _, _ ->
                // 复制设备ID到剪贴板
                val clipboard = getSystemService(CLIPBOARD_SERVICE) as ClipboardManager
                val clip = ClipData.newPlainText("设备ID", deviceId)
                clipboard.setPrimaryClip(clip)
                Toast.makeText(this, "设备ID已复制到剪贴板", Toast.LENGTH_SHORT).show()
            }
            .setNeutralButton("刷新重试") { dialog, _ ->
                dialog.dismiss()
                showLoading()
                loadPrizesFromApi()
            }
            .setNegativeButton("确定") { dialog, _ ->
                dialog.dismiss()
                // 不做任何本地回退展示
                hideLoading()
            }
            .setCancelable(false)
            .show()
    }
    
    // —— 统一设备绑定错误提示工具 ——
    private fun isDeviceNotBoundError(msg: String): Boolean {
        val keywords = listOf(
            "设备未绑定",
            "配置不属于该设备",
            "device not bound",
            "config mismatch",
            "未注册设备",
            "绑定失败"
        )
        return keywords.any { msg.contains(it, ignoreCase = true) }
    }
    
    private fun handleApiErrorWithBindingTip(deviceId: String, errorMsg: String) {
        if (isDeviceNotBoundError(errorMsg)) {
            showSimpleDeviceIdDialog(deviceId, "配置不属于该设备，请在后台绑定后重试")
        } else {
            Toast.makeText(this, "获取奖品数据失败: $errorMsg", Toast.LENGTH_LONG).show()
        }
    }
    
    /**
     * 请求READ_PHONE_STATE权限以获取IMEI
     */
    private fun requestPhoneStatePermission() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.READ_PHONE_STATE) 
            != PackageManager.PERMISSION_GRANTED) {
            
            ActivityCompat.requestPermissions(
                this,
                arrayOf(Manifest.permission.READ_PHONE_STATE),
                REQUEST_PHONE_STATE_PERMISSION
            )
        } else {
            // 权限已获取，测试设备码生成
            testDeviceIdGeneration()
        }
    }
    
    /**
     * 权限请求结果回调
     */
    override fun onRequestPermissionsResult(
        requestCode: Int,
        permissions: Array<out String>,
        grantResults: IntArray
    ) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults)
        
        when (requestCode) {
            REQUEST_PHONE_STATE_PERMISSION -> {
                if (grantResults.isNotEmpty() && grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                    Toast.makeText(this, "权限已获取，可以使用IMEI", Toast.LENGTH_SHORT).show()
                    testDeviceIdGeneration()
                } else {
                    Toast.makeText(this, "未获取READ_PHONE_STATE权限，无法使用IMEI", Toast.LENGTH_LONG).show()
                }
            }
            REQUEST_LOCATION_PERMISSION -> {
                val granted = grantResults.isNotEmpty() && grantResults.any { it == PackageManager.PERMISSION_GRANTED }
                if (granted) {
                    Toast.makeText(this, "定位权限已授予", Toast.LENGTH_SHORT).show()
                    // 重新检查初始化并提交安装位置
                    val deviceId = DeviceUtils.getDeviceId(this)
                    checkDeviceInitStatus(deviceId)
                } else {
                    Toast.makeText(this, "定位权限未授予", Toast.LENGTH_SHORT).show()
                }
            }
        }
    }
    
    /**
     * 测试设备码生成
     */
    private fun testDeviceIdGeneration() {
        val deviceId = DeviceUtils.getDeviceId(this)
        Log.d("MainActivity", "当前设备码: $deviceId")
        Toast.makeText(this, "设备码: $deviceId", Toast.LENGTH_LONG).show()
    }
    
    /**
     * 安全的UI线程执行方法 - 重构版本新增
     */
    private fun safeRunOnUiThread(action: () -> Unit) {
        try {
            if (Looper.myLooper() == Looper.getMainLooper()) {
                action()
            } else {
                runOnUiThread(action)
            }
        } catch (e: Exception) {
            Log.e("MainActivity", "❌ UI线程执行异常", e)
        }
    }

    companion object {
        private const val REQUEST_PHONE_STATE_PERMISSION = 1001
        private const val REQUEST_LOCATION_PERMISSION = 1002
    }
    
}

// 中奖记录数据类
data class WinRecord(
    val user: String,
    val award: String,
    val time: String,
    val result: String,
    val value: String
)
