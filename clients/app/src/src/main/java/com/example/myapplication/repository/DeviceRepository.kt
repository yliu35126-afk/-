package com.example.myapplication.repository

import android.content.Context
import android.util.Log
import com.example.myapplication.model.DeviceInitData
import com.example.myapplication.model.DeviceInitResponse
import com.example.myapplication.model.DeviceRegisterData
import com.example.myapplication.model.DeviceRegisterResponse
import com.example.myapplication.model.PrizeCheck
import com.example.myapplication.model.DeviceInfoData
import com.example.myapplication.model.RoleInfo
import com.example.myapplication.network.ApiManager
import com.example.myapplication.utils.DeviceUtils
import com.example.myapplication.utils.NetworkUtils
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

/**
 * 设备管理仓库 - 负责设备注册相关API调用
 */
class DeviceRepository {
    
    private val TAG = "DeviceRepository"
    
    /**
     * 注册设备
     * @param context 上下文，用于获取设备信息
     * @param onSuccess 成功回调
     * @param onError 错误回调
     */
    fun registerDevice(
        context: Context,
        onSuccess: (DeviceRegisterData) -> Unit,
        onError: (String) -> Unit
    ) {
        val deviceId = DeviceUtils.getDeviceId(context)
        // 组装更可读的网络信息（尽量不依赖定位权限，取不到SSID/BSSID时回退为“-”）
        val ssid = NetworkUtils.getCurrentWifiSsid(context) ?: "-"
        val bssid = NetworkUtils.getCurrentWifiBssid(context) ?: "-"
        val ip = NetworkUtils.getLocalIpV4(context) ?: "-"
        val installLocation = "Android设备 | WiFi:${ssid}(${bssid}) | IP:${ip}"
        val merchant = DeviceUtils.getMerchantInfo()
        
        Log.d(TAG, "正在注册设备: $deviceId")
        Log.d(TAG, "安装位置: $installLocation")
        Log.d(TAG, "商户信息: $merchant")
        Log.d(TAG, "设备信息: ${DeviceUtils.getDeviceInfo()}")
        Log.d(TAG, "网络信息: ip=${NetworkUtils.getLocalIpV4(context)}, ssid=${NetworkUtils.getCurrentWifiSsid(context)}, bssid=${NetworkUtils.getCurrentWifiBssid(context)}, wifiConnected=${NetworkUtils.isWifiConnected(context)}")
        
        ApiManager.apiService.registerDevice(
            deviceId = deviceId,
            installLocation = installLocation,
            merchant = merchant,
            purchaseInfo = "Android抽奖应用",
            isDebug = 1 // 开发阶段使用调试模式
        ).enqueue(object : Callback<DeviceRegisterResponse> {
            override fun onResponse(
                call: Call<DeviceRegisterResponse>,
                response: Response<DeviceRegisterResponse>
            ) {
                if (response.isSuccessful) {
                    val result = response.body()
                    if (result != null && result.code == 1) {
                        Log.d(TAG, "设备注册成功: ${result.msg}")
                        Log.d(TAG, "设备状态: ${result.data.status}")
                        Log.d(TAG, "响应消息: ${result.data.message}")
                        onSuccess(result.data)
                    } else {
                        val errorMsg = result?.msg ?: "设备注册失败"
                        Log.e(TAG, "API返回错误: $errorMsg")
                        onError(errorMsg)
                    }
                } else {
                    val errorMsg = "网络请求失败: ${response.code()}"
                    Log.e(TAG, errorMsg)
                    onError(errorMsg)
                }
            }
            
            override fun onFailure(call: Call<DeviceRegisterResponse>, t: Throwable) {
                val errorMsg = "设备注册失败: ${t.message}"
                Log.e(TAG, errorMsg, t)
                onError(errorMsg)
            }
        })
    }
    
    /**
     * 获取当前设备ID（不发起网络请求）
     */
    fun getCurrentDeviceId(context: Context): String {
        return DeviceUtils.getDeviceId(context)
    }
    
    /**
     * 设备初始化状态检测
     * @param deviceId 设备ID
     * @param onSuccess 成功回调
     * @param onError 错误回调
     */
    fun initDevice(
        deviceId: String,
        onSuccess: (DeviceInitData) -> Unit,
        onError: (String) -> Unit
    ) {
        Log.d(TAG, "正在检测设备初始化状态: $deviceId")
        
        ApiManager.apiService.initDevice(deviceId)
            .enqueue(object : Callback<DeviceInitResponse> {
                override fun onResponse(
                    call: Call<DeviceInitResponse>,
                    response: Response<DeviceInitResponse>
                ) {
                    if (response.isSuccessful) {
                        val result = response.body()
                        if (result != null) {
                            when (result.code) {
                                1 -> {
                                    // 成功获取设备信息
                                    Log.d(TAG, "设备初始化状态检测成功: ${result.msg}")
                                    Log.d(TAG, "设备初始化状态: ${result.data?.isInitialized}")
                                    Log.d(TAG, "设备状态: ${result.data?.status}")
                                    result.data?.let { onSuccess(it) }
                                }
                                0, 404 -> {
                                    // 设备未注册或不存在，创建一个表示未初始化的数据对象
                                    Log.w(TAG, "设备未注册: ${result.msg}")
                                    val uninitializedData = DeviceInitData(
                                        deviceId = deviceId, // 使用传入的实际设备ID
                                        isInitialized = false,
                                        status = "unregistered",
                                        message = result.msg, // 使用后端返回的实际消息
                                        missingRoles = listOf(
                                            RoleInfo("admin_role", "管理员角色"),
                                            RoleInfo("fenrun_config", "分润配置"),
                                            RoleInfo("prize_config", "奖品配置")
                                        ),
                                        boundRoles = emptyList(),
                                        prizeCheck = PrizeCheck(false, 0, 16, "设备未注册，无法检测奖品"),
                                        deviceInfo = DeviceInfoData(
                                            installLocation = "未配置",
                                            merchant = "未配置", 
                                            isDebug = 1,
                                            createTime = "",
                                            updateTime = ""
                                        )
                                    )
                                    onSuccess(uninitializedData)
                                }
                                else -> {
                                    val errorMsg = result.msg
                                    Log.e(TAG, "API返回错误: $errorMsg")
                                    onError(errorMsg)
                                }
                            }
                        } else {
                            val errorMsg = "响应数据为空"
                            Log.e(TAG, errorMsg)
                            onError(errorMsg)
                        }
                    } else {
                        val errorMsg = "网络请求失败: ${response.code()}"
                        Log.e(TAG, errorMsg)
                        onError(errorMsg)
                    }
                }
                
                override fun onFailure(call: Call<DeviceInitResponse>, t: Throwable) {
                    val errorMsg = "设备初始化状态检测失败: ${t.message}"
                    Log.e(TAG, errorMsg, t)
                    onError(errorMsg)
                }
            })
    }

    /**
     * 更新设备安装位置
     */
    fun updateInstallLocation(
        context: Context,
        installLocation: String,
        onSuccess: (DeviceRegisterData) -> Unit,
        onError: (String) -> Unit
    ) {
        val deviceId = DeviceUtils.getDeviceId(context)
        ApiManager.apiService.updateInstallLocation(deviceId, installLocation)
            .enqueue(object : Callback<DeviceRegisterResponse> {
                override fun onResponse(
                    call: Call<DeviceRegisterResponse>,
                    response: Response<DeviceRegisterResponse>
                ) {
                    if (response.isSuccessful) {
                        val result = response.body()
                        if (result != null && result.code == 1) {
                            onSuccess(result.data)
                        } else {
                            onError(result?.msg ?: "安装位置更新失败")
                        }
                    } else {
                        onError("网络请求失败: ${response.code()}")
                    }
                }

                override fun onFailure(call: Call<DeviceRegisterResponse>, t: Throwable) {
                    onError(t.message ?: "网络错误")
                }
            })
    }
}
