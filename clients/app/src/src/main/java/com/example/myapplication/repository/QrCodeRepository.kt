package com.example.myapplication.repository

import android.util.Log
import com.example.myapplication.model.QrCodeGenerateResponse
import com.example.myapplication.network.QrCodeApiManager
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

/**
 * 二维码仓库 - 管理二维码相关API调用
 */
class QrCodeRepository {
    private val TAG = "QrCodeRepository"

    /**
     * 生成二维码
     * @param deviceCode 设备编码
     * @param page 跳转页面（可选）
     * @param onSuccess 成功回调，返回二维码图片URL
     * @param onError 错误回调
     */
    fun generateQrCode(
        deviceCode: String,
        page: String? = null,
        onSuccess: (String) -> Unit,
        onError: (String) -> Unit
    ) {
        val params = mutableMapOf<String, Any>("device_code" to deviceCode)
        page?.let { params["page"] = it }
        QrCodeApiManager.apiService.generateQrCode(params)
            .enqueue(object : Callback<QrCodeGenerateResponse> {
                override fun onResponse(
                    call: Call<QrCodeGenerateResponse>,
                    response: Response<QrCodeGenerateResponse>
                ) {
                    if (response.isSuccessful) {
                        val result = response.body()
                        if (result != null && result.code == 1) {
                            val urlFromData = result.data?.qrcodeUrl
                            val urlFromMsg = if (result.msg != null && result.msg.isJsonObject) {
                                result.msg.asJsonObject.get("qrcode_url")?.asString
                            } else null
                            val url = urlFromData ?: urlFromMsg

                            if (!url.isNullOrBlank()) {
                                onSuccess(url)
                            } else {
                                val err = if (result.msg != null && result.msg.isJsonPrimitive) {
                                    result.msg.asString
                                } else {
                                    "二维码生成失败"
                                }
                                onError(err)
                            }
                        } else {
                            val err = if (result?.msg != null && result.msg.isJsonPrimitive) {
                                result.msg.asString
                            } else {
                                "二维码生成失败"
                            }
                            onError(err)
                        }
                    } else {
                        onError("网络请求失败: ${response.code()}")
                    }
                }

                override fun onFailure(call: Call<QrCodeGenerateResponse>, t: Throwable) {
                    onError("网络连接失败: ${t.message}")
                }
            })
    }

    /**
     * 生成小程序码（优先使用后端 minicode/generate）
     */
    fun generateMiniCode(
        deviceCode: String,
        page: String? = null,
        onSuccess: (String) -> Unit,
        onError: (String) -> Unit
    ) {
        // 前置校验：设备码为空或无效时直接报错，避免无意义请求
        val dc = deviceCode.trim()
        if (dc.isEmpty() || dc.equals("DEV_ANDROID_ID_INVALID", ignoreCase = true) || dc.equals("DEV_ANDROID_ID_ERROR", ignoreCase = true)) {
            Log.w(TAG, "设备码无效，无法生成小程序码: '$deviceCode'")
            onError("设备码不能为空或无效，请先完成设备注册")
            return
        }
        // 默认页面：按照接口文档，首页为 pages/index/index
        val effectivePage = page ?: "pages/index/index"
        val params = mutableMapOf<String, Any>(
            "device_code" to dc,
            "page" to effectivePage
        )
        Log.d(TAG, "准备生成小程序码: device_code='$dc', page='$effectivePage'")
        // 先尝试 /api/minicode/generate，失败兼容 /index.php/api/minicode/generate
        QrCodeApiManager.apiService.generateMiniCode(params)
            .enqueue(object : Callback<QrCodeGenerateResponse> {
                override fun onResponse(
                    call: Call<QrCodeGenerateResponse>,
                    response: Response<QrCodeGenerateResponse>
                ) {
                    if (response.isSuccessful) {
                        val result = response.body()
                        if (result != null && result.code == 1) {
                            val urlFromData = result.data?.qrcodeUrl
                            val urlFromMsg = if (result.msg != null && result.msg.isJsonObject) {
                                result.msg.asJsonObject.get("qrcode_url")?.asString
                            } else null
                            val url = urlFromData ?: urlFromMsg

                            if (!url.isNullOrBlank()) {
                                Log.d(TAG, "小程序码生成成功: $url")
                                onSuccess(url)
                            } else {
                                val err = if (result.msg != null && result.msg.isJsonPrimitive) {
                                    result.msg.asString
                                } else {
                                    "小程序码生成失败"
                                }
                                Log.w(TAG, "返回成功但URL为空，错误: $err")
                                onError(err)
                            }
                        } else {
                            val errMsg = if (result?.msg != null && result.msg.isJsonPrimitive) {
                                result.msg.asString
                            } else {
                                "小程序码生成失败"
                            }
                            Log.w(TAG, "小程序码生成返回失败: code=${result?.code}, msg='$errMsg'，尝试兼容 index.php 入口")
                            // 参数错误不再重试；其它错误尝试 index.php 入口
                            val nonRetryable = errMsg.contains("设备码不能为空") || errMsg.contains("参数错误")
                            if (nonRetryable) {
                                onError(errMsg)
                            } else {
                                QrCodeApiManager.apiService.generateMiniCodeIndexPhp(params)
                                    .enqueue(object : Callback<QrCodeGenerateResponse> {
                                        override fun onResponse(
                                            call2: Call<QrCodeGenerateResponse>,
                                            resp2: Response<QrCodeGenerateResponse>
                                        ) {
                                            if (resp2.isSuccessful) {
                                                val result2 = resp2.body()
                                                if (result2 != null && result2.code == 1) {
                                                    val urlFromData2 = result2.data?.qrcodeUrl
                                                    val urlFromMsg2 = if (result2.msg != null && result2.msg.isJsonObject) {
                                                        result2.msg.asJsonObject.get("qrcode_url")?.asString
                                                    } else null
                                                    val url2 = urlFromData2 ?: urlFromMsg2

                                                    if (!url2.isNullOrBlank()) {
                                                        Log.d(TAG, "index.php入口小程序码生成成功: $url2")
                                                        onSuccess(url2)
                                                    } else {
                                                        val err2 = if (result2.msg != null && result2.msg.isJsonPrimitive) {
                                                            result2.msg.asString
                                                        } else {
                                                            "小程序码生成失败"
                                                        }
                                                        Log.w(TAG, "index.php返回成功但URL为空，错误: $err2")
                                                        onError(err2)
                                                    }
                                                } else {
                                                    val err2 = if (result2?.msg != null && result2.msg.isJsonPrimitive) {
                                                        result2.msg.asString
                                                    } else {
                                                        "小程序码生成失败"
                                                    }
                                                    Log.w(TAG, "index.php入口返回失败: code=${result2?.code}, msg='$err2'")
                                                    onError(err2)
                                                }
                                            } else {
                                                Log.e(TAG, "index.php入口网络请求失败: ${resp2.code()}")
                                                onError("网络请求失败: ${resp2.code()}")
                                            }
                                        }

                                        override fun onFailure(call2: Call<QrCodeGenerateResponse>, t2: Throwable) {
                                            Log.e(TAG, "index.php入口网络连接失败: ${t2.message}")
                                            onError("网络连接失败: ${t2.message}")
                                        }
                                    })
                            }
                        }
                    } else {
                        // 回退到 index.php 入口
                        QrCodeApiManager.apiService.generateMiniCodeIndexPhp(params)
                            .enqueue(object : Callback<QrCodeGenerateResponse> {
                                override fun onResponse(
                                    call2: Call<QrCodeGenerateResponse>,
                                    resp2: Response<QrCodeGenerateResponse>
                                ) {
                                    if (resp2.isSuccessful) {
                                        val result2 = resp2.body()
                                        if (result2 != null && result2.code == 1) {
                                            val urlFromData2 = result2.data?.qrcodeUrl
                                            val urlFromMsg2 = if (result2.msg != null && result2.msg.isJsonObject) {
                                                result2.msg.asJsonObject.get("qrcode_url")?.asString
                                            } else null
                                            val url2 = urlFromData2 ?: urlFromMsg2

                                            if (!url2.isNullOrBlank()) {
                                                Log.d(TAG, "index.php入口小程序码生成成功: $url2")
                                                onSuccess(url2)
                                            } else {
                                                val err2 = if (result2.msg != null && result2.msg.isJsonPrimitive) {
                                                    result2.msg.asString
                                                } else {
                                                    "小程序码生成失败"
                                                }
                                                Log.w(TAG, "index.php返回成功但URL为空，错误: $err2")
                                                onError(err2)
                                            }
                                        } else {
                                            val err2 = if (result2?.msg != null && result2.msg.isJsonPrimitive) {
                                                result2.msg.asString
                                            } else {
                                                "小程序码生成失败"
                                            }
                                            Log.w(TAG, "index.php入口返回失败: code=${result2?.code}, msg='$err2'")
                                            onError(err2)
                                        }
                                    } else {
                                        Log.e(TAG, "index.php入口网络请求失败: ${resp2.code()}")
                                        onError("网络请求失败: ${resp2.code()}")
                                    }
                                }

                                override fun onFailure(call2: Call<QrCodeGenerateResponse>, t2: Throwable) {
                                    Log.e(TAG, "index.php入口网络连接失败: ${t2.message}")
                                    onError("网络连接失败: ${t2.message}")
                                }
                            })
                    }
                }

                override fun onFailure(call: Call<QrCodeGenerateResponse>, t: Throwable) {
                    Log.e(TAG, "小程序码网络连接失败: ${t.message}")
                    onError("网络连接失败: ${t.message}")
                }
            })
    }
}
