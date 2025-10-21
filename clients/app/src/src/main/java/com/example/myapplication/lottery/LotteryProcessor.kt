package com.example.myapplication.lottery

import android.util.Log
import com.example.myapplication.network.ApiManager
import com.example.myapplication.model.*
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import kotlin.random.Random

/**
 * 抽奖逻辑处理器
 */
class LotteryProcessor {
    
    companion object {
        private const val TAG = "LotteryProcessor"
    }
    
    /**
     * 处理抽奖逻辑 - 使用智能抽奖接口获取中奖信息
     * @param command 抽奖指令
     * @param prizeList 奖品列表
     * @param onSuccess 成功回调
     * @param onError 失败回调
     */
    fun processLottery(
        command: LotteryCommand, 
        prizeList: List<Prize>,
        onSuccess: (LotteryResult) -> Unit,
        onError: (String) -> Unit
    ) {
        Log.d(TAG, "处理抽奖请求: $command")
        Log.d(TAG, "奖品池共${prizeList.size}个奖品")
        
        if (prizeList.isEmpty()) {
            Log.e(TAG, "奖品列表为空！")
            onError("奖品列表为空")
            return
        }
        
        // 调用智能抽奖接口
        val request = SmartLotteryRequest(
            deviceId = command.deviceId,
            configId = command.configId
        )
        
        Log.d(TAG, "调用智能抽奖接口: deviceId=${request.deviceId}, configId=${request.configId}")
        
        ApiManager.apiService.smartLottery(request).enqueue(object : Callback<SmartLotteryResponse> {
            override fun onResponse(
                call: Call<SmartLotteryResponse>,
                response: Response<SmartLotteryResponse>
            ) {
                if (response.isSuccessful) {
                    val smartLotteryResponse = response.body()
                    if (smartLotteryResponse?.code == 1 && smartLotteryResponse.data != null) {
                        val data = smartLotteryResponse.data
                        
                        // 根据智能抽奖返回的prize_id查找对应的奖品
                        val selectedPrize = prizeList.find { it.id == data.prizeId }
                        
                        if (selectedPrize != null) {
                            val prizeInfo = PrizeInfo(
                                id = selectedPrize.id,
                                name = selectedPrize.prizeName,
                                image = selectedPrize.prizeImage
                            )
                            
                            Log.d(TAG, "✓ 智能抽奖成功: ${selectedPrize.prizeName} (ID: ${selectedPrize.id}, Sort: ${selectedPrize.sort})")
                            Log.d(TAG, "✓ 抽奖详情: 原始概率=${data.originalProbability}%, 实际概率=${data.actualProbability}%")
                            Log.d(TAG, "✓ 调整原因: ${data.adjustmentReason}")
                            Log.d(TAG, "✓ 今日统计: 总次数=${data.totalPlaysToday}, 中奖=${data.totalWinsToday}, 中奖率=${data.winRateToday}%")
                            
                            val result = LotteryResult(
                                userId = command.userId,
                                result = "win",
                                prizeInfo = prizeInfo,
                                lotteryRecordId = generateLotteryRecordId()
                            )
                            onSuccess(result)
                        } else {
                            // 未在本地奖品列表中：调用奖品详情获取真实信息；失败则直接报错
                            Log.w(TAG, "智能抽奖奖品未在本地列表中，尝试拉取详情。prizeId=${data.prizeId}, deviceId=${command.deviceId}, configId=${command.configId}")
                            try {
                                val availableIds = prizeList.take(10).joinToString(",") { it.id.toString() }
                                Log.w(TAG, "当前奖品池前10个ID: $availableIds")
                            } catch (_: Throwable) {}

                            ApiManager.apiService.getPrizeDetail(data.prizeId)
                                .enqueue(object : Callback<PrizeDetailResponse> {
                                    override fun onResponse(
                                        call2: Call<PrizeDetailResponse>,
                                        resp2: Response<PrizeDetailResponse>
                                    ) {
                                        if (resp2.isSuccessful) {
                                            val detail = resp2.body()
                                            if (detail != null && detail.code == 1) {
                                                val p = detail.data
                                                val prizeInfo = PrizeInfo(
                                                    id = p.id,
                                                    name = p.prizeName,
                                                    image = p.prizeImage
                                                )
                                                val result = LotteryResult(
                                                    userId = command.userId,
                                                    result = "win",
                                                    prizeInfo = prizeInfo,
                                                    lotteryRecordId = generateLotteryRecordId()
                                                )
                                                onSuccess(result)
                                            } else {
                                                val msg = detail?.msg ?: "获取奖品详情失败"
                                                Log.e(TAG, "奖品详情API返回异常: $msg")
                                                onError(msg)
                                            }
                                        } else {
                                            val code = resp2.code()
                                            Log.e(TAG, "奖品详情HTTP失败: $code")
                                            onError("奖品详情HTTP失败: $code")
                                        }
                                    }

                                    override fun onFailure(call2: Call<PrizeDetailResponse>, t2: Throwable) {
                                        Log.e(TAG, "奖品详情API调用失败", t2)
                                        onError("奖品详情API调用失败: ${t2.message}")
                                    }
                                })
                        }
                    } else {
                        Log.e(TAG, "智能抽奖接口返回错误: ${smartLotteryResponse?.msg}")
                        onError(smartLotteryResponse?.msg ?: "智能抽奖失败")
                    }
                } else {
                    Log.e(TAG, "智能抽奖接口请求失败: ${response.code()}")
                    onError("网络请求失败")
                }
            }
            
            override fun onFailure(call: Call<SmartLotteryResponse>, t: Throwable) {
                Log.e(TAG, "智能抽奖接口调用失败", t)
                onError("网络连接失败: ${t.message}")
            }
        })
    }
    
    /**
     * 生成抽奖记录ID
     */
    private fun generateLotteryRecordId(): String {
        return "LR_${System.currentTimeMillis()}_${Random.nextInt(1000, 9999)}"
    }
    
    /**
     * 获取抽奖类型描述
     */
    fun getLotteryTypeDescription(type: Int): String {
        return when (type) {
            1 -> "投币抽奖"
            2 -> "扫码抽奖"
            3 -> "免费抽奖"
            else -> "未知类型"
        }
    }
    
    /**
     * 验证抽奖指令
     */
    fun validateLotteryCommand(command: LotteryCommand): Boolean {
        return command.userId.isNotBlank() && 
               command.deviceId.isNotBlank() &&
               command.configId.isNotBlank() &&
               command.lotteryType in 1..3
    }
}
