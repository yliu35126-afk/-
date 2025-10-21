package com.example.myapplication.api

import com.example.myapplication.model.DeviceInitResponse
import com.example.myapplication.model.DeviceRegisterResponse
import com.example.myapplication.model.PrizeDetailResponse
import com.example.myapplication.model.PrizeListResponse
import com.example.myapplication.model.SmartLotteryRequest
import com.example.myapplication.model.SmartLotteryResponse
import retrofit2.Call
import retrofit2.http.*

/**
 * API接口
 * 后端地址：http://127.0.0.1:8000/
 * API模块路径：使用 api/ 前缀
 */
interface PrizeApiService {
    
    /**
     * 获取奖品列表
     * @param page 页码，默认1
     * @param limit 每页数量，默认5
     * @param status 状态，1=启用
     * @param deviceId 设备ID
     */
    @GET("prizeapi/list")
    fun getPrizeList(
        @Query("page") page: Int = 1,
        @Query("limit") limit: Int = 5,
        @Query("status") status: Int = 1,
        @Query("device_id") deviceId: String,
        @Query("config_id") configId: String
    ): Call<PrizeListResponse>
    
    /**
     * 获取奖品详情
     * @param id 奖品ID
     */
    @GET("prizeapi/detail")
    fun getPrizeDetail(
        @Query("id") id: Int
    ): Call<PrizeDetailResponse>
    
    /**
     * 设备注册/更新
     * @param deviceId 设备唯一标识码
     * @param installLocation 安装位置
     * @param merchant 商户名称
     * @param purchaseInfo 采购信息
     * @param isDebug 是否调试模式
     */
    @FormUrlEncoded
    @POST("device/register")
    fun registerDevice(
        @Field("device_id") deviceId: String,
        @Field("install_location") installLocation: String? = null,
        @Field("merchant") merchant: String? = null,
        @Field("purchase_info") purchaseInfo: String? = null,
        @Field("is_debug") isDebug: Int = 0
    ): Call<DeviceRegisterResponse>
    
    /**
     * 设备初始化状态检测
     * @param deviceId 设备ID
     */
    @FormUrlEncoded
    @POST("device/init")
    fun initDevice(
        @Field("device_id") deviceId: String
    ): Call<DeviceInitResponse>

    /**
     * 更新设备安装位置
     */
    @FormUrlEncoded
    @POST("device/updateLocation")
    fun updateInstallLocation(
        @Field("device_id") deviceId: String,
        @Field("install_location") installLocation: String
    ): Call<DeviceRegisterResponse>
    
    /**
     * 根据设备ID和配置ID获取分润奖品数据
     * @param deviceId 设备ID
     * @param configId 分润配置ID
     * @param limit 每页数量，默认16获取所有奖品
     */
    @GET("prizeapi/list_fenrun")
    fun getProfitPrizes(
        @Query("device_id") deviceId: String,
        @Query("config_id") configId: String,
        @Query("limit") limit: Int = 16
    ): Call<PrizeListResponse>
    
    /**
     * 智能抽奖接口
     * @param request 包含设备ID和配置ID的请求
     */
    @POST("device/smartLottery")
    fun smartLottery(
        @Body request: SmartLotteryRequest
    ): Call<SmartLotteryResponse>
}
