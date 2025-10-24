package com.example.myapplication.repository

import android.util.Log
import com.example.myapplication.api.TurntableApiService
import com.example.myapplication.network.ApiManager
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response

class TurntableRepository {

    private val TAG = "TurntableRepository"
    private val service: TurntableApiService = ApiManager.turntableService

    fun fetchPrizeList(
        siteId: Int? = null,
        deviceSn: String? = null,
        deviceId: Int? = null,
        boardId: Int? = null,
        onSuccess: (TurntablePrizeListResponse) -> Unit,
        onError: (String) -> Unit
    ) {
        service.getPrizeList(siteId, deviceSn, deviceId, boardId).enqueue(object : Callback<TurntablePrizeListResponse> {
            override fun onResponse(
                call: Call<TurntablePrizeListResponse>,
                response: Response<TurntablePrizeListResponse>
            ) {
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.code == 0) {
                        onSuccess(body)
                    } else {
                        onError(body?.msg ?: "加载奖品失败")
                    }
                } else {
                    onError("网络错误: ${response.code()}")
                }
            }

            override fun onFailure(call: Call<TurntablePrizeListResponse>, t: Throwable) {
                onError(t.message ?: "请求失败")
            }
        })
    }

    fun drawOnce(
        siteId: Int? = null,
        deviceSn: String? = null,
        deviceId: Int? = null,
        boardId: Int? = null,
        tierId: Int? = null,
        onSuccess: (TurntableDrawResponse) -> Unit,
        onError: (String) -> Unit
    ) {
        service.draw(siteId, deviceSn, deviceId, boardId, tierId).enqueue(object : Callback<TurntableDrawResponse> {
            override fun onResponse(
                call: Call<TurntableDrawResponse>,
                response: Response<TurntableDrawResponse>
            ) {
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.code == 0) {
                        onSuccess(body)
                    } else {
                        onError(body?.msg ?: "抽奖失败")
                    }
                } else {
                    onError("网络错误: ${response.code()}")
                }
            }

            override fun onFailure(call: Call<TurntableDrawResponse>, t: Throwable) {
                onError(t.message ?: "请求失败")
            }
        })
    }

    fun listRecords(
        page: Int = 1,
        pageSize: Int = 10,
        deviceId: Int? = null,
        onSuccess: (TurntableRecordResponse) -> Unit,
        onError: (String) -> Unit
    ) {
        service.getRecord(page, pageSize, deviceId).enqueue(object : Callback<TurntableRecordResponse> {
            override fun onResponse(
                call: Call<TurntableRecordResponse>,
                response: Response<TurntableRecordResponse>
            ) {
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.code == 0) {
                        onSuccess(body)
                    } else {
                        onError(body?.msg ?: "获取记录失败")
                    }
                } else {
                    onError("网络错误: ${response.code()}")
                }
            }

            override fun onFailure(call: Call<TurntableRecordResponse>, t: Throwable) {
                onError(t.message ?: "请求失败")
            }
        })
    }

    fun submitAddress(
        recordId: Int,
        name: String,
        mobile: String,
        address: String,
        provinceId: Int? = null,
        cityId: Int? = null,
        districtId: Int? = null,
        fullAddress: String? = null,
        longitude: String? = null,
        latitude: String? = null,
        onSuccess: (TurntableAddressResponse) -> Unit,
        onError: (String) -> Unit
    ) {
        service.submitAddress(
            recordId, name, mobile,
            provinceId, cityId, districtId,
            address, fullAddress, longitude, latitude
        ).enqueue(object : Callback<TurntableAddressResponse> {
            override fun onResponse(
                call: Call<TurntableAddressResponse>,
                response: Response<TurntableAddressResponse>
            ) {
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.code == 0) {
                        onSuccess(body)
                    } else {
                        onError(body?.msg ?: "地址提交失败")
                    }
                } else {
                    onError("网络错误: ${response.code()}")
                }
            }

            override fun onFailure(call: Call<TurntableAddressResponse>, t: Throwable) {
                onError(t.message ?: "请求失败")
            }
        })
    }

    fun verify(
        recordId: Int,
        verifyCode: String,
        onSuccess: (TurntableVerifyResponse) -> Unit,
        onError: (String) -> Unit
    ) {
        service.verify(recordId, verifyCode).enqueue(object : Callback<TurntableVerifyResponse> {
            override fun onResponse(
                call: Call<TurntableVerifyResponse>,
                response: Response<TurntableVerifyResponse>
            ) {
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.code == 0) {
                        onSuccess(body)
                    } else {
                        onError(body?.msg ?: "核销失败")
                    }
                } else {
                    onError("网络错误: ${response.code()}")
                }
            }

            override fun onFailure(call: Call<TurntableVerifyResponse>, t: Throwable) {
                onError(t.message ?: "请求失败")
            }
        })
    }
}