package com.example.myapplication

import android.animation.ValueAnimator
import android.content.Context
import android.graphics.Canvas
import android.graphics.Paint
import android.util.AttributeSet
import android.view.View

/**
 * 在奖品格子周围绘制一圈小黄点并做跑马灯动效
 */
class MarqueeRingView @JvmOverloads constructor(
    context: Context,
    attrs: AttributeSet? = null,
    defStyleAttr: Int = 0
) : View(context, attrs, defStyleAttr) {

    private val paint = Paint(Paint.ANTI_ALIAS_FLAG)
    private var animator: ValueAnimator? = null

    var dotCount: Int = 24
    var dotRadiusPx: Float = dp(4f)
    var insetPx: Float = dp(6f)
    var brightColor: Int = 0xFFFFD54F.toInt() // 亮黄色
    var dimColor: Int = 0x66FFD54F // 半透明黄色

    private var phaseIndex: Int = 0

    override fun onAttachedToWindow() {
        super.onAttachedToWindow()
        startMarquee()
    }

    override fun onDetachedFromWindow() {
        super.onDetachedFromWindow()
        stopMarquee()
    }

    fun startMarquee() {
        if (animator != null) return
        animator = ValueAnimator.ofInt(0, dotCount - 1).apply {
            duration = 1200
            repeatCount = ValueAnimator.INFINITE
            addUpdateListener {
                phaseIndex = it.animatedValue as Int
                invalidate()
            }
            start()
        }
    }

    fun stopMarquee() {
        animator?.cancel()
        animator = null
    }

    override fun onDraw(canvas: Canvas) {
        super.onDraw(canvas)
        val cx = width / 2f
        val cy = height / 2f
        val r = (minOf(width, height) / 2f) - insetPx
        for (i in 0 until dotCount) {
            val theta = (2.0 * Math.PI * i) / dotCount
            val x = (cx + r * Math.cos(theta)).toFloat()
            val y = (cy + r * Math.sin(theta)).toFloat()
            paint.color = if (i == phaseIndex) brightColor else dimColor
            canvas.drawCircle(x, y, dotRadiusPx, paint)
        }
    }

    private fun dp(v: Float): Float {
        return v * resources.displayMetrics.density
    }
}