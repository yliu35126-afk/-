package com.example.myapplication.api

import com.example.myapplication.model.QrCodeGenerateResponse
import retrofit2.Call
import retrofit2.http.Body
import retrofit2.http.FieldMap
import retrofit2.http.FormUrlEncoded
import retrofit2.http.POST

/**
 * 二维码相关API
 */
interface QrCodeApiService {
    /**
     * 生成二维码
     */
    @FormUrlEncoded
    @POST("qrcode/generate")
    fun generateQrCode(@FieldMap params: Map<String, @JvmSuppressWildcards Any>): Call<QrCodeGenerateResponse>

    /**
     * 生成二维码（兼容 index.php 入口）
     */
    @FormUrlEncoded
    @POST("qrcode/generate")
    fun generateQrCodeIndexPhp(@FieldMap params: Map<String, @JvmSuppressWildcards Any>): Call<QrCodeGenerateResponse>

    /**
     * 生成小程序码
     */
    @FormUrlEncoded
    @POST("minicode/generate")
    fun generateMiniCode(@FieldMap params: Map<String, @JvmSuppressWildcards Any>): Call<QrCodeGenerateResponse>

    /**
     * 生成小程序码（兼容 index.php 入口）
     */
    @FormUrlEncoded
    @POST("minicode/generate")
    fun generateMiniCodeIndexPhp(@FieldMap params: Map<String, @JvmSuppressWildcards Any>): Call<QrCodeGenerateResponse>
}
