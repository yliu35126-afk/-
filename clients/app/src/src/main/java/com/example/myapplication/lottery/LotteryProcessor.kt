package com.example.myapplication.lottery

import android.util.Log
import com.example.myapplication.network.ApiManager
import com.example.myapplication.api.TurntableDrawResponse
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
     * 处理抽奖逻辑 - 使用转盘插件 draw 接口
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
            onError("奖品列表为空")
            return
        }

        // 设备ID适配：优先使用 device_sn（字符串），否则尝试 device_id（整数）
        val deviceIdInt: Int? = try {
            command.deviceId.toInt()
        } catch (_: Throwable) { null }

        ApiManager.turntableService.draw(
            siteId = null,
            deviceSn = if (deviceIdInt == null) command.deviceId else null,
            deviceId = deviceIdInt,
            boardId = null,
            tierId = null
        ).enqueue(object : Callback<TurntableDrawResponse> {
            override fun onResponse(
                call: Call<TurntableDrawResponse>,
                response: Response<TurntableDrawResponse>
            ) {
                if (!response.isSuccessful) {
                    onError("网络错误: ${response.code()}")
                    return
                }
                val body = response.body()
                if (body == null) {
                    onError("返回为空")
                    return
                }
                if (body.code != 0) {
                    onError(body.msg ?: "抽奖失败")
                    return
                }

                val data = body.data
                if (data?.result == "hit" && data.hit != null) {
                    val hit = data.hit
                    val prizeInfo = PrizeInfo(
                        id = (hit.slot_id ?: hit.position ?: 0),
                        name = hit.prize_name ?: "中奖奖品",
                        image = hit.prize_image ?: ""
                    )
                    val result = LotteryResult(
                        userId = command.userId,
                        result = "win",
                        prizeInfo = prizeInfo,
                        lotteryRecordId = generateLotteryRecordId()
                    )
                    onSuccess(result)
                } else {
                    val result = LotteryResult(
                        userId = command.userId,
                        result = "lose",
                        prizeInfo = null,
                        lotteryRecordId = generateLotteryRecordId()
                    )
                    onSuccess(result)
                }
            }

            override fun onFailure(call: Call<TurntableDrawResponse>, t: Throwable) {
                onError("请求失败: ${t.message}")
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
