package com.example.myapplication.test

import android.content.Context
import android.util.Log
import com.example.myapplication.repository.DeviceRepository
import com.example.myapplication.repository.PrizeRepository

/**
 * API测试类 - 用于测试后端接口连接
 */
class ApiTest {
    
    companion object {
        private const val TAG = "ApiTest"
        
        /**
         * 测试设备注册API
         */
        fun testDeviceRegisterApi(context: Context) {
            Log.d(TAG, "开始测试设备注册API...")
            
            val repository = DeviceRepository()
            repository.registerDevice(
                context = context,
                onSuccess = { deviceData ->
                    Log.d(TAG, "✓ 设备注册API测试成功！")
                    Log.d(TAG, "设备ID: ${deviceData.deviceId}")
                    Log.d(TAG, "状态: ${deviceData.status}")
                    Log.d(TAG, "消息: ${deviceData.message}")
                },
                onError = { error ->
                    Log.e(TAG, "✗ 设备注册API测试失败: $error")
                }
            )
        }
        
        /**
         * 测试奖品列表API
         */
        fun testPrizeListApi() {
            Log.d(TAG, "开始测试奖品列表API...")
            
            val repository = PrizeRepository()
            repository.getPrizeList(
                page = 1,
                limit = 5,
                deviceId = "TEST_DEVICE_ID", // 测试用的设备ID
                configId = "8", // 新增必须参数：分润配置ID（示例值）
                onSuccess = { prizes ->
                    Log.d(TAG, "✓ 奖品列表API测试成功！获得 ${prizes.size} 个奖品")
                    
                    prizes.forEachIndexed { index, prize ->
                        Log.d(TAG, "${index + 1}. ${prize.prizeName} (ID: ${prize.id})")
                        Log.d(TAG, "   供应商: ${prize.supplier}")
                        // 为兼容不同后端字段版本，移除对图片与状态文案的直接引用
                        Log.d(TAG, "   状态: ${prize.status}")
                    }
                },
                onError = { error ->
                    Log.e(TAG, "✗ 奖品列表API测试失败: $error")
                },
                onDeviceNotBound = { deviceId ->
                    Log.w(TAG, "⚠ 设备未绑定供应商，设备ID: $deviceId")
                }
            )
        }
        
        /**
         * 测试奖品详情API
         */
        fun testPrizeDetailApi(prizeId: Int = 1) {
            Log.d(TAG, "开始测试奖品详情API (ID: $prizeId)...")
            
            val repository = PrizeRepository()
            repository.getPrizeDetail(
                id = prizeId,
                onSuccess = { prize ->
                    Log.d(TAG, "✓ 奖品详情API测试成功！")
                    Log.d(TAG, "奖品详情:")
                    Log.d(TAG, "  ID: ${prize.id}")
                    Log.d(TAG, "  名称: ${prize.prizeName}")
                    Log.d(TAG, "  供应商: ${prize.supplier}")
                    // 兼容不同版本字段：移除对图片、状态文案与排序的直接引用
                    Log.d(TAG, "  状态: ${prize.status}")
                    Log.d(TAG, "  价格: ${prize.price}")
                },
                onError = { error ->
                    Log.e(TAG, "✗ 奖品详情API测试失败: $error")
                }
            )
        }
        
        /**
         * 运行所有API测试
         */
        fun runAllTests(context: Context) {
            Log.d(TAG, "========== API测试开始 ==========")
            
            // 1. 测试设备注册
            testDeviceRegisterApi(context)
            
            // 2. 测试奖品列表
            android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
                testPrizeListApi()
            }, 2000)
            
            // 3. 测试奖品详情
            android.os.Handler(android.os.Looper.getMainLooper()).postDelayed({
                testPrizeDetailApi()
            }, 4000)
        }
    }
}
