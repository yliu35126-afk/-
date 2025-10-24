package com.example.myapplication.network

import com.example.myapplication.api.PrizeApiService
import com.example.myapplication.api.TurntableApiService
import com.google.gson.GsonBuilder
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

/**
 * 网络管理器 - 负责API配置和调用
 */
object ApiManager {
    
    // 统一使用 NetworkConfig 解析出的 BASE_URL
    val baseUrl: String = NetworkConfig.baseUrl()
    
    // Gson配置
    private val gson = GsonBuilder()
        .setLenient()
        .create()
    
    // OkHttp客户端配置
    private val okHttpClient = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .addInterceptor(HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        })
        .build()
    
    // Retrofit实例 - 直接使用已含 index.php/api/ 的 BASE_URL
    private val retrofit = Retrofit.Builder()
        .baseUrl(baseUrl)
        .client(okHttpClient)
        .addConverterFactory(GsonConverterFactory.create(gson))
        .build()
    
    // API服务
    val apiService: PrizeApiService = retrofit.create(PrizeApiService::class.java)

    // Turntable 插件服务
    val turntableService: TurntableApiService = retrofit.create(TurntableApiService::class.java)

    // 备用 BASE_URL 与 Retrofit，用于 404 时进行路径前缀回退
    val fallbackBaseUrl: String = NetworkConfig.alternateBaseUrl()

    private val fallbackRetrofit = Retrofit.Builder()
        .baseUrl(fallbackBaseUrl)
        .client(okHttpClient)
        .addConverterFactory(GsonConverterFactory.create(gson))
        .build()

    val fallbackApiService: PrizeApiService = fallbackRetrofit.create(PrizeApiService::class.java)

    // 第二层备用 BASE_URL 与 Retrofit：回退到 /api.php/
    val fallbackApiPhpBaseUrl: String = NetworkConfig.fallbackApiPhpBaseUrl()

    private val fallbackApiPhpRetrofit = Retrofit.Builder()
        .baseUrl(fallbackApiPhpBaseUrl)
        .client(okHttpClient)
        .addConverterFactory(GsonConverterFactory.create(gson))
        .build()

    val fallbackApiPhpService: PrizeApiService = fallbackApiPhpRetrofit.create(PrizeApiService::class.java)
}
