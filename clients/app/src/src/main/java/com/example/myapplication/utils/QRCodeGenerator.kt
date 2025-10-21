package com.example.myapplication.utils

import android.graphics.Bitmap
import android.graphics.Color
import com.google.zxing.BarcodeFormat
import com.google.zxing.EncodeHintType
import com.google.zxing.WriterException
import com.google.zxing.common.BitMatrix
import com.google.zxing.qrcode.QRCodeWriter
import com.google.zxing.qrcode.decoder.ErrorCorrectionLevel

/**
 * 二维码生成工具类
 */
object QRCodeGenerator {
    
    /**
     * 生成二维码Bitmap
     * @param content 二维码内容
     * @param width 宽度
     * @param height 高度
     * @param foregroundColor 前景色（默认黑色）
     * @param backgroundColor 背景色（默认白色）
     * @return 二维码Bitmap，生成失败返回null
     */
    fun generateQRCode(
        content: String,
        width: Int = 500,
        height: Int = 500,
        foregroundColor: Int = Color.BLACK,
        backgroundColor: Int = Color.WHITE
    ): Bitmap? {
        return try {
            val writer = QRCodeWriter()
            val hints = mapOf(
                EncodeHintType.ERROR_CORRECTION to ErrorCorrectionLevel.H,
                EncodeHintType.CHARACTER_SET to "UTF-8",
                EncodeHintType.MARGIN to 1
            )
            
            val bitMatrix: BitMatrix = writer.encode(content, BarcodeFormat.QR_CODE, width, height, hints)
            val bitmap = Bitmap.createBitmap(width, height, Bitmap.Config.RGB_565)
            
            for (x in 0 until width) {
                for (y in 0 until height) {
                    bitmap.setPixel(x, y, if (bitMatrix[x, y]) foregroundColor else backgroundColor)
                }
            }
            
            bitmap
        } catch (e: WriterException) {
            e.printStackTrace()
            null
        } catch (e: Exception) {
            e.printStackTrace()
            null
        }
    }
    
    /**
     * 生成抽奖游戏二维码URL
     * @param deviceId 设备ID
     * @param gameType 游戏类型（默认lottery）
     * @param userId 用户ID（可选）
     * @return 二维码URL
     */
    fun generateLotteryGameUrl(
        deviceId: String,
        gameType: String = "lottery",
        userId: String? = null
    ): String {
        val baseUrl = "http://127.0.0.1:8000/demo.html"
        val params = mutableMapOf<String, String>()
        
        params["device_id"] = deviceId
        params["game_type"] = gameType
        
        if (!userId.isNullOrBlank()) {
            params["user_id"] = userId
        }
        
        val queryString = params.map { "${it.key}=${it.value}" }.joinToString("&")
        return "$baseUrl?$queryString"
    }
    
    /**
     * 生成抽奖游戏二维码Bitmap
     * @param deviceId 设备ID
     * @param gameType 游戏类型（默认lottery）
     * @param userId 用户ID（可选）
     * @param size 二维码尺寸（默认500x500）
     * @return 二维码Bitmap，生成失败返回null
     */
    fun generateLotteryGameQRCode(
        deviceId: String,
        gameType: String = "lottery",
        userId: String? = null,
        size: Int = 500
    ): Bitmap? {
        val url = generateLotteryGameUrl(deviceId, gameType, userId)
        return generateQRCode(url, size, size)
    }
}
