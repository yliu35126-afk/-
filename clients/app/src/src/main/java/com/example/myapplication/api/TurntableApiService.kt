package com.example.myapplication.api

import retrofit2.Call
import retrofit2.http.*

/**
 * Turntable 插件接口映射（与小程序保持一致路径前缀）
 * 基础前缀：/addons/turntable/api/turntable/
 */
interface TurntableApiService {

    // 16格奖品列表
    @GET("addons/turntable/api/turntable/prizeList")
    fun getPrizeList(
        @Query("site_id") siteId: Int? = null,
        @Query("device_sn") deviceSn: String? = null,
        @Query("device_id") deviceId: Int? = null,
        @Query("board_id") boardId: Int? = null
    ): Call<TurntablePrizeListResponse>

    // 抽一次（需要登录token，APP侧后续接入会员体系时传）
    @FormUrlEncoded
    @POST("addons/turntable/api/turntable/draw")
    fun draw(
        @Field("site_id") siteId: Int? = null,
        @Field("device_sn") deviceSn: String? = null,
        @Field("device_id") deviceId: Int? = null,
        @Field("board_id") boardId: Int? = null,
        @Field("tier_id") tierId: Int? = null
    ): Call<TurntableDrawResponse>

    // 抽奖记录（可选登录）
    @GET("addons/turntable/api/turntable/record")
    fun getRecord(
        @Query("page") page: Int = 1,
        @Query("page_size") pageSize: Int = 10,
        @Query("device_id") deviceId: Int? = null
    ): Call<TurntableRecordResponse>

    // 收件信息填写（命中奖励为实物时）
    @FormUrlEncoded
    @POST("addons/turntable/api/turntable/address")
    fun submitAddress(
        @Field("record_id") recordId: Int,
        @Field("name") name: String,
        @Field("mobile") mobile: String,
        @Field("province_id") provinceId: Int? = null,
        @Field("city_id") cityId: Int? = null,
        @Field("district_id") districtId: Int? = null,
        @Field("address") address: String,
        @Field("full_address") fullAddress: String? = null,
        @Field("longitude") longitude: String? = null,
        @Field("latitude") latitude: String? = null
    ): Call<TurntableAddressResponse>

    // 到店核销（若插件已提供）
    @FormUrlEncoded
    @POST("addons/turntable/api/turntable/verify")
    fun verify(
        @Field("record_id") recordId: Int,
        @Field("verify_code") verifyCode: String
    ): Call<TurntableVerifyResponse>
}

// --- 数据模型（按后端返回结构裁剪必要字段） ---

data class TurntablePrizeSlot(
    val slot_id: Int? = null,
    val position: Int? = null,
    val prize_type: String? = null,
    val goods_id: Int? = null,
    val weight: Int? = null,
    val round_qty: Int? = null,
    val inventory: Int? = null,
    val prize_name: String? = null,
    val prize_image: String? = null
)

data class TurntableBoardInfo(
    val board_id: Int? = null,
    val mode: String? = null,
    val round_no: Int? = null
)

data class TurntablePrizeListData(
    val board: TurntableBoardInfo? = null,
    val slots: List<TurntablePrizeSlot> = emptyList()
)

data class TurntablePrizeListResponse(
    val code: Int,
    val msg: String?,
    val data: TurntablePrizeListData?
)

data class TurntableDrawData(
    val result: String? = null, // "hit"
    val hit: TurntablePrizeSlot? = null,
    val amount: Double? = null
)

data class TurntableDrawResponse(
    val code: Int,
    val msg: String?,
    val data: TurntableDrawData?
)

data class TurntableRecordItem(
    val record_id: Int? = null,
    val prize_type: String? = null,
    val goods_id: Int? = null,
    val order_id: Int? = null,
    val verify_status: String? = null,
    val create_time: Long? = null
)

data class TurntableRecordData(
    val list: List<TurntableRecordItem> = emptyList(),
    val page: Int? = null,
    val page_size: Int? = null,
    val total: Int? = null
)

data class TurntableRecordResponse(
    val code: Int,
    val msg: String?,
    val data: TurntableRecordData?
)

data class TurntableAddressResponse(
    val code: Int,
    val msg: String?,
    val data: Any?
)

data class TurntableVerifyResponse(
    val code: Int,
    val msg: String?,
    val data: Any?
)