package com.example.myapplication.repository

import android.util.Log
import com.example.myapplication.model.Prize
import com.example.myapplication.model.PrizeDetailResponse
import com.example.myapplication.model.PrizeListResponse
import com.example.myapplication.network.ApiManager
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

/**
 * 奖品数据仓库 - 管理奖品相关的API调用
 */
class PrizeRepository {
    
    private val TAG = "PrizeRepository"
    
    /**
     * 获取奖品列表（含分润优先与降级回退）
     * @param page 页码
     * @param limit 每页数量
     * @param deviceId 设备ID
     * @param configId 分润配置ID
     * @param onSuccess 成功回调
     * @param onError 错误回调
     * @param onDeviceNotBound 设备未绑定供应商回调 - 传递设备ID用于显示
     */
    fun getPrizeList(
        page: Int = 1,
        limit: Int = 16, // 获取16个奖品用于填满格子
        deviceId: String,
        configId: String,
        onSuccess: (List<Prize>) -> Unit,
        onError: (String) -> Unit,
        onDeviceNotBound: ((String) -> Unit)? = null
    ) {
        Log.d(TAG, "正在获取奖品列表: page=$page, limit=$limit, deviceId=$deviceId, configId=$configId")
        
        // 优先走支持分润的接口
        ApiManager.apiService.getProfitPrizes(deviceId, configId, limit)
            .enqueue(object : Callback<PrizeListResponse> {
                override fun onResponse(
                    call: Call<PrizeListResponse>,
                    response: Response<PrizeListResponse>
                ) {
                    if (response.isSuccessful) {
                        val result = response.body()
                        if (result != null && result.code == 1) {
                            Log.d(TAG, "获取奖品列表成功(分润): ${result.data.list.size}个奖品")
                            val normalized = normalizePrizes(result.data.list)
                            onSuccess(normalized)
                        } else {
                            val msg = result?.msg ?: "获取奖品列表失败"
                            Log.w(TAG, "分润接口返回错误: $msg")
                            if (msg.contains("不属于此设备") || msg.contains("未绑定")) {
                                onDeviceNotBound?.invoke(deviceId)
                            } else {
                                onError(msg)
                            }
                        }
                    } else {
                        val code = response.code()
                        val errorMsg = "网络请求失败: $code"
                        Log.e(TAG, errorMsg)
                        if (code == 404) {
                            Log.w(TAG, "分润接口路径 404，尝试入口路径回退重试")
                            // 使用备用入口路径重试一次分润接口
                            ApiManager.fallbackApiService.getProfitPrizes(deviceId, configId, limit)
                                .enqueue(object : Callback<PrizeListResponse> {
                                    override fun onResponse(
                                        call2: Call<PrizeListResponse>,
                                        resp2: Response<PrizeListResponse>
                                    ) {
                                        if (resp2.isSuccessful) {
                                            val result2 = resp2.body()
                                            if (result2 != null && result2.code == 1) {
                                                val normalized = normalizePrizes(result2.data.list)
                                                Log.d(TAG, "备用入口成功(分润): ${normalized.size}个奖品")
                                                onSuccess(normalized)
                                            } else {
                                                val msg2 = result2?.msg ?: "获取分润奖品数据失败"
                                                onError(msg2)
                                            }
                                        } else {
                                            val code2 = resp2.code()
                                            Log.e(TAG, "备用入口响应失败(分润): $code2")
                                            onError("网络请求失败(备用入口 分润): $code2")
                                        }
                                    }

                                    override fun onFailure(call2: Call<PrizeListResponse>, t2: Throwable) {
                                        Log.e(TAG, "备用入口请求异常(分润): ${t2.message}")
                                        onError("网络请求异常(备用入口 分润): ${t2.message}")
                                    }
                                })
                        } else {
                            onError(errorMsg)
                        }
                    }
                }
                
                override fun onFailure(call: Call<PrizeListResponse>, t: Throwable) {
                    val errorMsg = "网络连接失败: ${t.message}"
                    Log.e(TAG, errorMsg, t)
                    onError(errorMsg)
                }
            })
    }

    private fun shouldFallback(msg: String): Boolean {
        // 关闭所有前端回退：一律不启用通用列表兜底
        return false
    }

    private fun normalizePrizes(list: List<Prize>): List<Prize> {
        return list.map { p ->
            val supplierName = p.supplier?.takeIf { it.isNotBlank() } ?: "系统默认"
            val stock = p.stockQuantity
            val depleted = if (stock <= 0) 1 else p.isDepleted
            p.copy(supplier = supplierName, stockQuantity = stock, isDepleted = depleted)
        }
    }


    /**
     * 获取奖品详情
     * @param id 奖品ID
     * @param onSuccess 成功回调
     * @param onError 错误回调
     */
    fun getPrizeDetail(
        id: Int,
        onSuccess: (Prize) -> Unit,
        onError: (String) -> Unit
    ) {
        Log.d(TAG, "正在获取奖品详情: id=$id")
        
        ApiManager.apiService.getPrizeDetail(id)
            .enqueue(object : Callback<PrizeDetailResponse> {
                override fun onResponse(
                    call: Call<PrizeDetailResponse>,
                    response: Response<PrizeDetailResponse>
                ) {
                    if (response.isSuccessful) {
                        val result = response.body()
                        if (result != null && result.code == 1) {
                            Log.d(TAG, "获取奖品详情成功: ${result.data.prizeName}")
                            onSuccess(result.data)
                        } else {
                            val errorMsg = result?.msg ?: "获取奖品详情失败"
                            Log.e(TAG, "API返回错误: $errorMsg")
                            onError(errorMsg)
                        }
                    } else {
                        val errorMsg = "网络请求失败: ${response.code()}"
                        Log.e(TAG, errorMsg)
                        onError(errorMsg)
                    }
                }
                
                override fun onFailure(call: Call<PrizeDetailResponse>, t: Throwable) {
                    val errorMsg = "网络连接失败: ${t.message}"
                    Log.e(TAG, errorMsg, t)
                    onError(errorMsg)
                }
            })
    }
    
    /**
     * 根据设备ID和配置ID获取分润奖品数据（保留原方法，供直接调用）
     */
    fun getProfitPrizes(
        deviceId: String,
        configId: String,
        onSuccess: (List<Prize>) -> Unit,
        onError: (String) -> Unit
    ) {
        Log.d(TAG, "正在获取分润奖品数据: deviceId=$deviceId, configId=$configId")
        // 新增：打印实际使用的 BaseUrl 与备用入口，便于排查 404 或路径错误
        Log.d(TAG, "BaseUrl=${ApiManager.baseUrl}")
        Log.d(TAG, "FallbackBaseUrl=${ApiManager.fallbackBaseUrl}")
        Log.d(TAG, "FallbackApiPhpBaseUrl=${ApiManager.fallbackApiPhpBaseUrl}")
        
        ApiManager.apiService.getProfitPrizes(deviceId, configId, 16)
            .enqueue(object : Callback<PrizeListResponse> {
                override fun onResponse(
                    call: Call<PrizeListResponse>,
                    response: Response<PrizeListResponse>
                ) {
                    if (response.isSuccessful) {
                        val result = response.body()
                        val size = result?.data?.list?.size ?: -1
                        Log.d(TAG, "分润接口返回: code=${result?.code}, msg=${result?.msg}, size=$size")
                        if (result != null && result.code == 1) {
                            val normalized = normalizePrizes(result.data.list)
                            Log.d(TAG, "normalize 后数量: ${normalized.size}")
                            onSuccess(normalized)
                        } else {
                            val msg = result?.msg ?: "获取分润奖品数据失败"
                            onError(msg)
                        }
                    } else {
                        val code = response.code()
                        Log.e(TAG, "分润接口响应失败: HTTP $code")
                        onError("网络请求失败(分润): $code")
                    }
                }
                override fun onFailure(call: Call<PrizeListResponse>, t: Throwable) {
                    Log.e(TAG, "网络连接失败(分润): ${t.message}", t)
                    onError("网络连接失败(分润): ${t.message}")
                }
            })
    }
}
