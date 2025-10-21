package com.example.myapplication.ui

import android.content.Context
import android.graphics.BlurMaskFilter
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.util.AttributeSet
import android.view.View
import android.animation.ValueAnimator
import android.util.TypedValue

/**
 * 屏幕边缘黄色跑马灯发光点视图（适配大屏）
 * - 在四周绘制发光小圆点，并通过动画实现“追逐”效果
 * - 采用软件渲染以支持 BlurMaskFilter 的发光
 */
class MarqueeBorderLightsView @JvmOverloads constructor(
    context: Context,
    attrs: AttributeSet? = null,
    defStyleAttr: Int = 0
) : View(context, attrs, defStyleAttr) {

    private val dotPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.YELLOW
        style = Paint.Style.FILL
    }
    private val glowPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.argb(220, 255, 180, 0)
        style = Paint.Style.FILL
        maskFilter = BlurMaskFilter(12f, BlurMaskFilter.Blur.NORMAL)
    }

    private var animator: ValueAnimator? = null
    private var phase: Float = 0f

    // 可调参数（以 dp 为单位转换）
    private val dotRadiusPx = dp(4f) // 小圆点半径
    private val glowRadiusExtraPx = dp(6f) // 发光扩散额外半径
    private val spacingPx = dp(36f) // 点间距（越小越密）
    private val edgeInsetPx = dp(12f) // 与屏幕边缘的内缩距离

    init {
        // 启用软件层以支持发光效果
        setLayerType(LAYER_TYPE_SOFTWARE, null)
        start()
    }

    override fun onAttachedToWindow() {
        super.onAttachedToWindow()
        start()
    }

    override fun onDetachedFromWindow() {
        super.onDetachedFromWindow()
        stop()
    }

    fun start() {
        if (animator?.isRunning == true) return
        animator = ValueAnimator.ofFloat(0f, 1f).apply {
            duration = 1600L
            repeatCount = ValueAnimator.INFINITE
            repeatMode = ValueAnimator.RESTART
            addUpdateListener {
                phase = it.animatedFraction
                invalidate()
            }
            start()
        }
    }

    fun stop() {
        animator?.cancel()
        animator = null
    }

    override fun onDraw(canvas: Canvas) {
        super.onDraw(canvas)
        val w = width.toFloat()
        val h = height.toFloat()
        if (w <= 0 || h <= 0) return

        val left = edgeInsetPx
        val top = edgeInsetPx
        val right = w - edgeInsetPx
        val bottom = h - edgeInsetPx

        // 计算四边的点位数量（保持均匀间隔）
        val topCount = ((right - left) / spacingPx).toInt().coerceAtLeast(2)
        val sideCount = ((bottom - top) / spacingPx).toInt().coerceAtLeast(2)

        // 计算总点数，用于动画追逐索引
        val totalDots = topCount * 2 + sideCount * 2
        if (totalDots <= 0) return

        // 当前高亮索引（顺时针）
        val highlightIndex = ((totalDots) * phase).toInt() % totalDots

        var idx = 0
        // 顶边：从左到右
        for (i in 0..topCount) {
            val x = left + i * spacingPx
            val y = top
            drawDot(canvas, x, y, isHighlight = (idx == highlightIndex))
            idx++
        }
        // 右边：从上到下
        for (i in 0..sideCount) {
            val x = right
            val y = top + i * spacingPx
            drawDot(canvas, x, y, isHighlight = (idx == highlightIndex))
            idx++
        }
        // 底边：从右到左
        for (i in 0..topCount) {
            val x = right - i * spacingPx
            val y = bottom
            drawDot(canvas, x, y, isHighlight = (idx == highlightIndex))
            idx++
        }
        // 左边：从下到上
        for (i in 0..sideCount) {
            val x = left
            val y = bottom - i * spacingPx
            drawDot(canvas, x, y, isHighlight = (idx == highlightIndex))
            idx++
        }
    }

    private fun drawDot(canvas: Canvas, x: Float, y: Float, isHighlight: Boolean) {
        val r = dotRadiusPx
        if (isHighlight) {
            // 高亮：先画发光，再画主体
            canvas.drawCircle(x, y, r + glowRadiusExtraPx, glowPaint)
            canvas.drawCircle(x, y, r, dotPaint)
        } else {
            // 非高亮：淡一点
            val oldAlpha = dotPaint.alpha
            dotPaint.alpha = 180
            canvas.drawCircle(x, y, r, dotPaint)
            dotPaint.alpha = oldAlpha
        }
    }

    private fun dp(value: Float): Float = TypedValue.applyDimension(
        TypedValue.COMPLEX_UNIT_DIP, value, resources.displayMetrics
    )
}