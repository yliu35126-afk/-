package com.example.myapplication.utils

import android.widget.ImageView
import com.bumptech.glide.Glide
import com.bumptech.glide.load.engine.DiskCacheStrategy
import com.bumptech.glide.request.RequestOptions
import com.bumptech.glide.request.target.Target
import com.bumptech.glide.request.transition.Transition
import com.bumptech.glide.request.target.CustomTarget
import android.graphics.drawable.Drawable
import com.example.myapplication.R
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.util.Base64

/**
 * 图片加载工具类
 */
object ImageLoader {
    
    // 使用 NetworkConfig 的图片基础域名，避免把 API 前缀用于图片路径
    private fun baseImageDomain(): String {
        return com.example.myapplication.network.NetworkConfig.imageBaseUrl()
    }

    // 规范化拼接，避免出现双斜杠或缺失斜杠
    private fun joinUrl(base: String, path: String): String {
        val b = base.trimEnd('/')
        val p = path.trimStart('/')
        return "$b/$p"
    }

    // 统一规范化图片URL，修复模拟器/局域网绝对地址导致加载失败的问题
    private fun normalizeImageUrl(raw: String?): String? {
        if (raw.isNullOrBlank()) return null
        val base = baseImageDomain()
        val u = raw.trim()
        return try {
            if (u.startsWith("http", ignoreCase = true)) {
                val uploadsIdx = u.indexOf("/uploads/")
                if (uploadsIdx != -1) {
                    // 将 /uploads/ 之后的路径切换到当前资源域名
                    val pathFromUploads = u.substring(uploadsIdx)
                    joinUrl(base, pathFromUploads)
                } else {
                    // 将本地/局域网域名替换为当前资源域名
                    u.replace(Regex(
                        "^https?://((10\\.0\\.2\\.2)|(127\\.0\\.0\\.1)|(localhost)|(192\\.168\\.[0-9]{1,3}\\.[0-9]{1,3}))(\\:[0-9]+)?",
                        RegexOption.IGNORE_CASE
                    ), base.trimEnd('/'))
                }
            } else if (u.startsWith("data:image", ignoreCase = true)) {
                // data URI 原样返回，后续逻辑会特殊处理
                u
            } else {
                // 相对路径：拼接到资源域名
                joinUrl(base, u)
            }
        } catch (_: Exception) {
            u
        }
    }

    // 判断是否为 data:image/png;base64 之类的 data URI
    private fun isDataImage(raw: String?): Boolean {
        return raw?.trim()?.startsWith("data:image", ignoreCase = true) == true
    }

    // 将 data:image/...;base64,xxx 转为 Bitmap
    private fun decodeDataImageToBitmap(raw: String?): Bitmap? {
        if (!isDataImage(raw)) return null
        val uri = raw!!.trim()
        val commaIdx = uri.indexOf(",")
        if (commaIdx <= 0) return null
        val base64Part = uri.substring(commaIdx + 1)
        return try {
            val bytes = Base64.decode(base64Part, Base64.DEFAULT)
            BitmapFactory.decodeByteArray(bytes, 0, bytes.size)
        } catch (_: Exception) {
            null
        }
    }
    
    /**
     * 加载奖品图片
     * @param imageView 目标ImageView
     * @param imagePath 图片路径（来自API）
     * @param placeholder 占位图资源ID
     */
    fun loadPrizeImage(
        imageView: ImageView,
        imagePath: String?,
        placeholder: Int = R.drawable.gzbg
    ) {
        // 检查Context是否有效，防止在Activity销毁时加载图片
        val context = imageView.context
        if (context is android.app.Activity && (context.isFinishing || context.isDestroyed)) {
            return
        }

        // 先处理 data:image 的情况
        val bitmapFromData = decodeDataImageToBitmap(imagePath)
        if (bitmapFromData != null) {
            try {
                Glide.with(imageView.context).clear(imageView)
            } catch (_: Exception) {}
            imageView.setImageBitmap(bitmapFromData)
            return
        }
        
        val fullUrl = normalizeImageUrl(imagePath)
        
        // 若无有效URL，显示占位图，避免出现空白导致“黑屏”观感
        if (fullUrl == null) {
            try {
                Glide.with(imageView.context).clear(imageView)
            } catch (_: Exception) {}
            imageView.setImageResource(placeholder)
            return
        }

        val requestOptions = RequestOptions()
            .diskCacheStrategy(DiskCacheStrategy.ALL)
            .centerCrop()
        
        try {
            Glide.with(imageView.context)
                .load(fullUrl)
                .apply(requestOptions)
                .into(imageView)
        } catch (e: IllegalArgumentException) {
            // Activity已销毁，忽略加载
            android.util.Log.w("ImageLoader", "Activity已销毁，跳过图片加载: ${e.message}")
        }
    }
    
    /**
     * 加载奖品图片（带加载完成回调）
     * @param imageView 目标ImageView
     * @param imageUrl 图片URL
     * @param placeholder 占位图资源ID
     * @param onLoadComplete 加载完成回调
     */
    fun loadPrizeImageWithCallback(
        imageView: ImageView,
        imageUrl: String?,
        placeholder: Int = R.drawable.gzbg,
        onLoadComplete: () -> Unit
    ) {
        // 检查Context是否有效
        val context = imageView.context
        if (context is android.app.Activity && (context.isFinishing || context.isDestroyed)) {
            return
        }

        // 先处理 data:image 的情况
        val bitmapFromData = decodeDataImageToBitmap(imageUrl)
        if (bitmapFromData != null) {
            try {
                Glide.with(imageView.context).clear(imageView)
            } catch (_: Exception) {}
            imageView.setImageBitmap(bitmapFromData)
            onLoadComplete()
            return
        }
        
        val fullUrl = normalizeImageUrl(imageUrl)
        
        // 如果没有有效URL：显示占位图并回调，保证界面可见
        if (fullUrl == null) {
            try {
                Glide.with(imageView.context).clear(imageView)
            } catch (_: Exception) {}
            imageView.setImageResource(placeholder)
            onLoadComplete()
            return
        }

        try {
            Glide.with(imageView.context)
                .load(fullUrl)
                .diskCacheStrategy(DiskCacheStrategy.ALL)
                .centerCrop()
                .into(object : CustomTarget<Drawable>() {
                    override fun onResourceReady(resource: Drawable, transition: Transition<in Drawable>?) {
                        imageView.setImageDrawable(resource)
                        onLoadComplete()
                    }
                    
                    override fun onLoadFailed(errorDrawable: Drawable?) {
                        imageView.setImageResource(placeholder)
                        onLoadComplete() // 即使失败也算加载完成
                    }
                    
                    override fun onLoadCleared(placeholder: Drawable?) {
                        // Do nothing
                    }
                })
        } catch (e: IllegalArgumentException) {
            // Activity已销毁，忽略加载
            android.util.Log.w("ImageLoader", "Activity已销毁，跳过图片加载: ${e.message}")
        }
    }
    
    /**
     * 加载圆形奖品图片
     */
    fun loadCirclePrizeImage(
        imageView: ImageView,
        imagePath: String?,
        placeholder: Int = R.drawable.gzbg
    ) {
        // 检查Context是否有效
        val context = imageView.context
        if (context is android.app.Activity && (context.isFinishing || context.isDestroyed)) {
            return
        }
        
        // 与其他方法保持一致：优先处理 data:image
        val bitmapFromData = decodeDataImageToBitmap(imagePath)
        if (bitmapFromData != null) {
            try {
                Glide.with(imageView.context)
                    .load(bitmapFromData)
                    .diskCacheStrategy(DiskCacheStrategy.ALL)
                    .circleCrop()
                    .into(imageView)
            } catch (_: Exception) {
                imageView.setImageBitmap(bitmapFromData)
            }
            return
        }
        
        // 与其他方法保持一致：优先使用 NetworkConfig 的基础域名
        val fullUrl = normalizeImageUrl(imagePath)
        
        if (fullUrl == null) {
            try {
                Glide.with(imageView.context).clear(imageView)
            } catch (_: Exception) {}
            imageView.setImageResource(placeholder)
            return
        }

        val requestOptions = RequestOptions()
            .diskCacheStrategy(DiskCacheStrategy.ALL)
            .circleCrop()
        
        try {
            Glide.with(imageView.context)
                .load(fullUrl)
                .apply(requestOptions)
                .into(imageView)
        } catch (e: IllegalArgumentException) {
            // Activity已销毁，忽略加载
            android.util.Log.w("ImageLoader", "Activity已销毁，跳过图片加载: ${e.message}")
        }
    }
}
