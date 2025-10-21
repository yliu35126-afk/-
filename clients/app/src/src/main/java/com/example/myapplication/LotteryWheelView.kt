package com.example.myapplication

import android.animation.AnimatorSet
import android.animation.ObjectAnimator
import android.content.Context
import android.graphics.Color
import android.util.AttributeSet
import android.view.Gravity
import android.view.View
import android.widget.*
import androidx.cardview.widget.CardView
import androidx.gridlayout.widget.GridLayout
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import androidx.core.content.ContextCompat
import com.example.myapplication.utils.InvalidIndexMonitor

/**
 * 仿Vue项目的16格环绕转盘视图
 * 采用6x4 GridLayout，16个格子环绕，中心区域包含二维码，记录表移到转盘下方
 */
class LotteryWheelView @JvmOverloads constructor(
    context: Context,
    attrs: AttributeSet? = null,
    defStyleAttr: Int = 0
) : FrameLayout(context, attrs, defStyleAttr) {

    override fun onAttachedToWindow() {
        super.onAttachedToWindow()
    }

    override fun onDetachedFromWindow() {
        super.onDetachedFromWindow()
        stopAutoScroll()
    }

    // 16个环绕格子的位置定义，对应Vue的ring数组
    private val ringPositions = arrayOf(
        Pair(0, 0), Pair(0, 1), Pair(0, 2), Pair(0, 3), // 第1行: 4个格子
        Pair(1, 3), Pair(2, 3), Pair(3, 3), Pair(4, 3), // 右边列: 4个格子
        Pair(5, 3), Pair(5, 2), Pair(5, 1), Pair(5, 0), // 第6行: 4个格子
        Pair(4, 0), Pair(3, 0), Pair(2, 0), Pair(1, 0)  // 左边列: 4个格子
    )

    private lateinit var gridLayout: GridLayout
    private lateinit var centerArea: FrameLayout
    private lateinit var qrImageView: ImageView
    private lateinit var topQrImageView: ImageView // 用于显示真实二维码
    private lateinit var startButton: Button
    private lateinit var recordsRecyclerView: RecyclerView

    // 16个格子视图
    private val cellViews = mutableListOf<CardView>()
    private val prizeImageViews = mutableListOf<ImageView>()
    private val priceTextViews = mutableListOf<TextView>()
    private val depletedMaskViews = mutableListOf<TextView>()
    @Volatile var prizeViewsReady: Boolean = false

    // 存储rowGap以便在setupCenterArea中使用
    private var rowGap: Int = 0
    
    // 记录表高度（单位：dp）
    private val RECORDS_HEIGHT_DP = 200
    private val HORIZONTAL_MARGIN_RATIO = 0.10f
    var activeIndex = -1
        set(value) {
            if (field != value) {
                // 清除旧的激活状态 - 对应Vue的cell.active移除
                if (field >= 0 && field < cellViews.size) {
                    val oldCell = cellViews[field]
                    // 恢复原始状态
                    oldCell.cardElevation = 4f
                    oldCell.setCardBackgroundColor(ContextCompat.getColor(context, android.R.color.white))
                    oldCell.background = null

                    // 平滑缩放回原始大小 - 对应Vue的transform: scale(1)
                    oldCell.animate()
                        .scaleX(1f)
                        .scaleY(1f)
                        .setDuration(280) // 对应Vue的transition: .28s
                        .start()

                    // 停止所有动画效果
                    stopVueStyleGlow(oldCell)
                    oldCell.clearAnimation()
                }

                field = value

                // 设置新的激活状态 - 完全对应Vue的cell.active效果
                if (field >= 0 && field < cellViews.size) {
                    val newCell = cellViews[field]

                    // Vue样式的激活效果
                    startVueStyleGlow(newCell)

                    // 对应Vue的 transform: scale(1.04) 或冲刺模式的 scale(1.09)
                    val targetScale = if (isSprinting) 1.09f else 1.04f
                    newCell.animate()
                        .scaleX(targetScale)
                        .scaleY(targetScale)
                        .setDuration(280)
                        .start()

                    // 冲刺模式额外特效
                    if (isSprinting) {
                        enhanceSprintGlow(newCell)
                    }
                }
            }
        }

    var onStartClick: (() -> Unit)? = null
    private var recordsAdapter: WinRecordsAdapter? = null
    private var isSprinting = false

    init {
        setupLayout()
    }

    private fun setupLayout() {
        // 主布局使用LinearLayout垂直排列GridLayout和记录表
        val mainLayout = LinearLayout(context).apply {
            orientation = LinearLayout.VERTICAL
            layoutParams = LayoutParams(LayoutParams.MATCH_PARENT, LayoutParams.MATCH_PARENT)
            gravity = Gravity.CENTER_HORIZONTAL or Gravity.CENTER_VERTICAL
        }

        // 设置GridLayout（转盘部分）
        setupGridLayout(mainLayout)

        // 设置记录表（中奖人名单）
        setupRecordsArea(mainLayout)

        // 将主布局添加到视图
        addView(mainLayout)
    }

    private fun setupGridLayout(parentLayout: LinearLayout) {
        // 创建一个容器来包装GridLayout
        val containerLayout = FrameLayout(context).apply {
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                // 给转盘容器加顶部 margin，避免贴顶
                val topMarginPx = (context.resources.displayMetrics.density * 24).toInt()
                setMargins(0, topMarginPx, 0, topMarginPx)
            }
            clipChildren = false
            clipToPadding = false
        }


        // 计算屏幕尺寸
        val displayMetrics = context.resources.displayMetrics
        val screenWidth = displayMetrics.widthPixels
        val screenHeight = displayMetrics.heightPixels

        // 设置5%的水平边距（左右各5%）
        val horizontalMarginPx = (screenWidth * HORIZONTAL_MARGIN_RATIO).toInt()

        // 计算记录表高度
        val recordsHeightPx = (RECORDS_HEIGHT_DP * context.resources.displayMetrics.density).toInt()
        // 计算可用高度（屏幕高度减去5%和记录表高度及8dp边距）
        val availableHeightPx = (screenHeight * 0.85f).toInt() - recordsHeightPx - (8 * context.resources.displayMetrics.density).toInt()
        
        // 计算可用宽度（屏幕宽度减去左右边距）
        val availableWidth = screenWidth - 2 * horizontalMarginPx
        val sizeFactor = 0.80f // 保持原有比例因子
        
        // 确保格子高度不超过可用高度，保持6行比例
        val maxCellHeight = availableHeightPx / 6 // 每行最大高度
        val cellWidth = minOf(
            ((availableWidth * sizeFactor) / 4).toInt(), // 基于宽度的格子大小
            maxCellHeight // 确保不超过高度限制
        )
        
        // 计算列间距
        val columnGap = ((availableWidth - cellWidth * 4) / 3)
        // 计算行间距
        val rowGapCalc = (availableHeightPx - cellWidth * 6) / 5
        val maxRowGap = (cellWidth * 0.5f).toInt()
        rowGap = maxOf(8, minOf(rowGapCalc, maxRowGap))

        // 创建GridLayout
        gridLayout = GridLayout(context).apply {
            columnCount = 4
            rowCount = 6
            clipChildren = false
            clipToPadding = false

            // 设置GridLayout的精确尺寸
            val gridWidth = cellWidth * 4 + columnGap * 3
            val gridHeight = cellWidth * 6 + rowGap * 5

            layoutParams = FrameLayout.LayoutParams(gridWidth, gridHeight).apply {
                setMargins(horizontalMarginPx, 0, horizontalMarginPx, 0)
                gravity = Gravity.CENTER
            }

            alignmentMode = GridLayout.ALIGN_BOUNDS
            useDefaultMargins = false
            setPadding(0, 0, 0, 0)
        }.apply {
//            val gridPadding = (context.resources.displayMetrics.density * 8).toInt()
//            setPadding(gridPadding, 0, gridPadding, gridPadding)
        }

        // 创建16个环绕格子
        for (i in 0 until 16) {
            val position = ringPositions[i]
            val cellContainer = createPrizeCell(i)

            val params = GridLayout.LayoutParams().apply {
                rowSpec = GridLayout.spec(position.first)
                columnSpec = GridLayout.spec(position.second)
                width = cellWidth
                height = cellWidth
                val rightMargin = if (position.second < 3) columnGap else 0
                val bottomMargin = if (position.first < 5) rowGap else 0
                setMargins(0, 0, rightMargin, bottomMargin)
            }

            cellContainer.layoutParams = params
            gridLayout.addView(cellContainer)
            cellViews.add(cellContainer)
        }

        // 在创建完16个格子后标记视图就绪
        prizeViewsReady = true

        // 设置中心区域
        setupCenterArea()
        containerLayout.addView(gridLayout)
        parentLayout.addView(containerLayout)
    }

    private fun createPrizeCell(index: Int): CardView {
        val cellContainer = CardView(context).apply {
            radius = 16f
            cardElevation = 4f
            setCardBackgroundColor(ContextCompat.getColor(context, android.R.color.white))
        }

        val prizeContainer = FrameLayout(context).apply {
            background = try {
                context.getDrawable(R.drawable.gzbg)
            } catch (e: Exception) {
                context.getDrawable(android.R.color.holo_red_light)
            }
            clipToOutline = true
            outlineProvider = object : android.view.ViewOutlineProvider() {
                override fun getOutline(view: android.view.View, outline: android.graphics.Outline) {
                    outline.setRoundRect(0, 0, view.width, view.height, 16f)
                }
            }
        }

        // 跑马灯环
        val marqueeRing = MarqueeRingView(context).apply {
            layoutParams = FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
            )
        }

        val prizeImageView = ImageView(context).apply {
            layoutParams = FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
            ).apply {
                setMargins(12, 12, 12, 12)
            }
            scaleType = ImageView.ScaleType.CENTER_INSIDE
        }

        // 价格角标
        val priceBadge = TextView(context).apply {
            layoutParams = FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.WRAP_CONTENT,
                FrameLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                gravity = Gravity.TOP or Gravity.END
                val m = (context.resources.displayMetrics.density * 6).toInt()
                setMargins(m, m, m, m)
            }
            setPadding(10, 6, 10, 6)
            textSize = 12f
            setTextColor(Color.WHITE)
            background = android.graphics.drawable.GradientDrawable().apply {
                shape = android.graphics.drawable.GradientDrawable.RECTANGLE
                cornerRadius = 16f
                setColor(0xCCB71C1C.toInt()) // 红色半透明
            }
            visibility = View.GONE
        }

        // 库存耗尽遮罩层
        val depletedMask = TextView(context).apply {
            layoutParams = FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
            )
            text = "奖池已送完"
            textSize = 14f
            setTextColor(Color.WHITE)
            gravity = Gravity.CENTER
            background = android.graphics.drawable.GradientDrawable().apply {
                shape = android.graphics.drawable.GradientDrawable.RECTANGLE
                cornerRadius = 16f
                setColor(0xCC000000.toInt()) // 黑色半透明
            }
            visibility = View.GONE
        }

        prizeContainer.addView(marqueeRing)
        prizeContainer.addView(prizeImageView)
        prizeContainer.addView(priceBadge)
        prizeContainer.addView(depletedMask)
        prizeImageViews.add(prizeImageView)
        priceTextViews.add(priceBadge)
        depletedMaskViews.add(depletedMask)
        cellContainer.addView(prizeContainer)

        return cellContainer
    }

    fun setPrizePrice(index: Int, priceText: String?) {
        if (index in 0 until priceTextViews.size) {
            val tv = priceTextViews[index]
            if (priceText.isNullOrBlank()) {
                tv.visibility = View.GONE
            } else {
                tv.text = priceText
                tv.visibility = View.VISIBLE
            }
        }
        // 所有奖品格子视图已创建
        prizeViewsReady = true
    }

    /**
     * 设置奖品库存状态
     * @param index 奖品索引
     * @param isDepleted 是否库存耗尽 (1=耗尽, 0=有库存)
     */
    fun setPrizeDepletedStatus(index: Int, isDepleted: Int) {
        if (index in 0 until depletedMaskViews.size) {
            val maskView = depletedMaskViews[index]
            val imageView = prizeImageViews[index]
            
            if (isDepleted == 1) {
                // 显示遮罩层，图片半透明
                maskView.visibility = View.VISIBLE
                imageView.alpha = 0.6f
            } else {
                // 隐藏遮罩层，图片正常显示
                maskView.visibility = View.GONE
                imageView.alpha = 1.0f
            }
        }
    }

    private fun setupCenterArea() {
        val centerAreaWrapper = FrameLayout(context).apply {
            clipChildren = false
            clipToPadding = false
        }

        centerArea = FrameLayout(context).apply {
        }

        val centerContent = LinearLayout(context).apply {
            orientation = LinearLayout.VERTICAL
            setPadding(0, 0, 0, 0)
        }

        val heroHeader = FrameLayout(context).apply {
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
            ).apply {
                bottomMargin = (context.resources.displayMetrics.density * 6).toInt()
            }
        }

        // 中心区域背景 + 二维码
        val qrWrapper = FrameLayout(context).apply {
            layoutParams = FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
            )
        }

        // 背景图（预留了二维码边框）
        val bgImageView = ImageView(context).apply {
            layoutParams = FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
            )
            scaleType = ImageView.ScaleType.FIT_CENTER
            setImageResource(R.drawable.qr_bg) // 你的新背景图
        }
        qrWrapper.addView(bgImageView)

        // 二维码 ImageView（大小和位置等比例控制）
        topQrImageView = ImageView(context).apply {
            scaleType = ImageView.ScaleType.FIT_XY
        }
        qrWrapper.addView(topQrImageView)

        // 当背景图加载完成后，计算二维码区域
        bgImageView.viewTreeObserver.addOnGlobalLayoutListener {
            val bgWidth = bgImageView.width.toFloat()
            val bgHeight = bgImageView.height.toFloat()

          // 🔹 参数：相对于背景图的比例（可以调）
            val qrLeftRatio = 0.34f   // 左边缘位置比例
        val qrTopRatio = 0.54f    // 顶部位置比例（上移一点点）
            val qrSizeRatio = 0.40f   // 宽高比例（正方形）

            val qrLeft = (bgWidth * qrLeftRatio).toInt()
            val qrTop = (bgHeight * qrTopRatio).toInt()
            val qrSize = (bgWidth * qrSizeRatio).toInt()

            val params = FrameLayout.LayoutParams(qrSize, qrSize).apply {
                leftMargin = qrLeft
                topMargin = qrTop
            }
            topQrImageView.layoutParams = params
        }

        heroHeader.addView(qrWrapper)
        centerContent.addView(heroHeader)

        startButton = Button(context).apply {
            visibility = View.GONE
            setOnClickListener { onStartClick?.invoke() }
        }

        centerArea.layoutParams = FrameLayout.LayoutParams(
            FrameLayout.LayoutParams.WRAP_CONTENT,
            FrameLayout.LayoutParams.WRAP_CONTENT
        ).apply {
            gravity = Gravity.CENTER
        }

        centerAreaWrapper.layoutParams = GridLayout.LayoutParams().apply {
            rowSpec = GridLayout.spec(1, 4, GridLayout.CENTER)
            columnSpec = GridLayout.spec(1, 2, GridLayout.CENTER)
            val displayMetrics = context.resources.displayMetrics
            val screenWidth = displayMetrics.widthPixels
            val horizontalMarginPx = (screenWidth * HORIZONTAL_MARGIN_RATIO).toInt()
            val availableWidth = screenWidth - 2 * horizontalMarginPx
            val sizeFactor = 0.80f
            val cellSize = ((availableWidth * sizeFactor) / 4).toInt()
            val columnGap = ((availableWidth - cellSize * 4) / 3)
            val gridCenterWidth = cellSize * 2 + columnGap
            val gridCenterHeight = cellSize * 4 + (rowGap * 3)
            width = gridCenterWidth
            height = gridCenterHeight
            setGravity(Gravity.CENTER)
        }
        centerAreaWrapper.scaleX = 1.5f
        centerAreaWrapper.scaleY = 1.5f
        centerArea.addView(centerContent)
        centerAreaWrapper.addView(centerArea)
        centerAreaWrapper.clipChildren = false
        centerAreaWrapper.clipToPadding = false
        gridLayout.addView(centerAreaWrapper)

        centerAreaWrapper.post {
            val parent = centerAreaWrapper.parent as? GridLayout
            parent?.let { grid ->
                val gridActualWidth = grid.width
                val gridActualHeight = grid.height
                val targetCenterX = gridActualWidth / 2
                val targetCenterY = gridActualHeight / 2
                val currentCenterX = centerAreaWrapper.left + centerAreaWrapper.width / 2
                val currentCenterY = centerAreaWrapper.top + centerAreaWrapper.height / 2
                val adjustX = targetCenterX - currentCenterX
                val adjustY = targetCenterY - currentCenterY
                centerAreaWrapper.translationX = adjustX.toFloat()
                centerAreaWrapper.translationY = adjustY.toFloat()
            }
        }
    }

    private fun setupRecordsArea(parentLayout: LinearLayout) {
        // 🔹 标题在 CardView 外面
        val titleView = TextView(context).apply {
            text = "中奖名单"
            gravity = Gravity.CENTER
            textSize = 30f
            setTextColor(0xFFF7E8A1.toInt()) // 金黄色
            setTypeface(null, android.graphics.Typeface.BOLD)
            val titlePadding = (context.resources.displayMetrics.density * 8).toInt()
            setPadding(0, titlePadding, 0, titlePadding)
        }
        parentLayout.addView(titleView)

        // 🔹 创建一个容器来分层显示背景和内容
        val recordsContainer = FrameLayout(context).apply {
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                (RECORDS_HEIGHT_DP * context.resources.displayMetrics.density).toInt()
            ).apply {
                val horizontalMarginPx = (context.resources.displayMetrics.widthPixels * HORIZONTAL_MARGIN_RATIO).toInt()
                setMargins(horizontalMarginPx, 0, horizontalMarginPx, 0)
            }
        }

        // 🔹 背景层：显示背景图片
        val backgroundView = ImageView(context).apply {
            layoutParams = FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
            )
            setImageResource(R.drawable.zjmd_bg)
            scaleType = ImageView.ScaleType.FIT_XY // 或者使用 CENTER_CROP

            // 添加圆角效果
            clipToOutline = true
            outlineProvider = object : android.view.ViewOutlineProvider() {
                override fun getOutline(view: android.view.View, outline: android.graphics.Outline) {
                    outline.setRoundRect(0, 0, view.width, view.height, 32f)
                }
            }
        }

        // 🔹 内容层：CardView 设置透明背景
        val recordTableWrapper = CardView(context).apply {
    //        radius = 32f
    //        cardElevation = 16f
    //        useCompatPadding = true
    //        preventCornerOverlap = true

            // 设置透明背景，让背景图片透过来
            setCardBackgroundColor(android.graphics.Color.TRANSPARENT)

            layoutParams = FrameLayout.LayoutParams(
                FrameLayout.LayoutParams.MATCH_PARENT,
                FrameLayout.LayoutParams.MATCH_PARENT
            )
        }

        val recordContainer = LinearLayout(context).apply {
            orientation = LinearLayout.VERTICAL
            val padding = (context.resources.displayMetrics.density * 8).toInt()
            setPadding(padding, padding, padding, padding)
        }

        // RecyclerView 区域
        recordsRecyclerView = RecyclerView(context).apply {
            layoutParams = LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.MATCH_PARENT
            )
            layoutManager = LinearLayoutManager(context)
            overScrollMode = View.OVER_SCROLL_NEVER
            setBackgroundColor(android.graphics.Color.TRANSPARENT) // 保证透明
        }

        // 组装视图层次
        recordContainer.addView(recordsRecyclerView)
        recordTableWrapper.addView(recordContainer)

        // 先添加背景层，再添加内容层
        recordsContainer.addView(backgroundView)
        recordsContainer.addView(recordTableWrapper)

        parentLayout.addView(recordsContainer)
    }


    fun setPrizeImages(images: List<Int>) {
        for (i in prizeImageViews.indices) {
            if (i < images.size) {
                prizeImageViews[i].setImageResource(images[i])
            }
        }
    }

    fun isPrizeViewsReady(): Boolean = prizeViewsReady

    fun getPrizeImageViewOrNull(index: Int): ImageView? {
        if (!prizeViewsReady) {
            android.util.Log.w(
                "LotteryWheelView",
                "getPrizeImageViewOrNull: views not ready yet (index=$index)"
            )
            InvalidIndexMonitor.record("LotteryWheelView", "not_ready_index=$index")
            return null
        }
        val view = prizeImageViews.getOrNull(index)
        if (view == null) {
            android.util.Log.w(
                "LotteryWheelView",
                "getPrizeImageViewOrNull: invalid index=$index, size=${prizeImageViews.size}"
            )
            InvalidIndexMonitor.record("LotteryWheelView", "invalid_index=$index")
        }
        return view
    }

    fun getPrizeImageView(index: Int): ImageView {
        return getPrizeImageViewOrNull(index) ?: android.widget.ImageView(context).apply { visibility = View.GONE }
    }

    fun setRecordsAdapter(adapter: WinRecordsAdapter) {
        val minRowsForScroll = 10
        if (adapter.itemCount in 2 until minRowsForScroll) {
            val originList = (adapter as? com.example.myapplication.WinRecordsAdapter)?.let { itCopy ->
                val field = itCopy.javaClass.getDeclaredField("records")
                field.isAccessible = true
                @Suppress("UNCHECKED_CAST")
                field.get(itCopy) as? MutableList<com.example.myapplication.WinRecord>
            }
            originList?.let { list ->
                val toAdd = mutableListOf<com.example.myapplication.WinRecord>()
                while (list.size + toAdd.size < minRowsForScroll) {
                    toAdd.addAll(list)
                }
                list.addAll(toAdd.take(minRowsForScroll - list.size))
                adapter.notifyDataSetChanged()
            }
        }
        recordsAdapter = adapter
        recordsRecyclerView.adapter = adapter
        stopAutoScroll()
        startAutoScroll()
    }

    fun setQRCode(qrCodeBitmap: android.graphics.Bitmap) {
        topQrImageView.setImageBitmap(qrCodeBitmap)
    }

    fun setQRCode(qrCodeUrl: String) {
        com.example.myapplication.utils.ImageLoader.loadPrizeImage(topQrImageView, qrCodeUrl)
    }

    private fun startSprintCellAnimation(cell: CardView) {
        val pulseAnimation = ObjectAnimator.ofFloat(cell, "alpha", 1f, 1.3f, 1.05f, 1f).apply {
            duration = 650
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.RESTART
        }

        val rotateAnimation = ObjectAnimator.ofFloat(cell, "rotation", 0f, 0.5f, -0.5f, 0f).apply {
            duration = 650
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.RESTART
        }

        val animatorSet = AnimatorSet().apply {
            playTogether(pulseAnimation, rotateAnimation)
        }

        cell.tag = animatorSet
        animatorSet.start()
    }

    private fun stopSprintCellAnimation(cell: CardView) {
        (cell.tag as? AnimatorSet)?.cancel()
        cell.tag = null
        cell.alpha = 1f
        cell.rotation = 0f
    }

    fun startSprinting() {
        if (!isSprinting) {
            isSprinting = true
            for (cellView in cellViews) {
                cellView.animate()
                    .scaleX(1.05f)
                    .scaleY(1.05f)
                    .setDuration(100)
                    .start()
            }
            if (activeIndex >= 0 && activeIndex < cellViews.size) {
                enhanceSprintGlow(cellViews[activeIndex])
            }
        }
    }

    fun stopSprinting() {
        if (isSprinting) {
            isSprinting = false
            for (cellView in cellViews) {
                stopSprintCellAnimation(cellView)
                stopVueStyleGlow(cellView)
                cellView.animate()
                    .scaleX(1f)
                    .scaleY(1f)
                    .setDuration(300)
                    .start()
            }
        }
    }

    private fun startVueStyleGlow(cellView: CardView) {
        cellView.foreground = context.getDrawable(R.drawable.golden_border)
        cellView.setCardBackgroundColor(0xFFFFE082.toInt())
        val elevationAnimator = ObjectAnimator.ofFloat(cellView, "cardElevation", 15f, 25f, 15f).apply {
            duration = 800
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.REVERSE
            interpolator = android.view.animation.AccelerateDecelerateInterpolator()
        }
        cellView.tag = elevationAnimator.apply { start() }
    }

    private fun stopVueStyleGlow(cellView: CardView) {
        (cellView.tag as? ObjectAnimator)?.cancel()
        cellView.tag = null
        cellView.background = null
        cellView.foreground = null
        cellView.cardElevation = 4f
        cellView.setCardBackgroundColor(ContextCompat.getColor(context, android.R.color.white))
        }

    private fun enhanceSprintGlow(cellView: CardView) {
        cellView.background = context.getDrawable(R.drawable.vue_sprint_glow)
        val sprintElevationAnimator = ObjectAnimator.ofFloat(cellView, "cardElevation", 15f, 35f, 15f).apply {
            duration = 650
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.REVERSE
            interpolator = android.view.animation.LinearInterpolator()
        }
        val rotationAnimator = ObjectAnimator.ofFloat(cellView, "rotation", 0f, 0.5f, 0f).apply {
            duration = 1200
            repeatCount = ObjectAnimator.INFINITE
            repeatMode = ObjectAnimator.REVERSE
        }
        val sprintAnimatorSet = AnimatorSet().apply {
            playTogether(sprintElevationAnimator, rotationAnimator)
            start()
        }
        cellView.tag = sprintAnimatorSet
    }

    private var scrollHandler: android.os.Handler? = null
    private var scrollRunnable: Runnable? = null

    private fun startAutoScroll() {
        stopAutoScroll()
        scrollHandler = android.os.Handler(android.os.Looper.getMainLooper())
        scrollRunnable = object : Runnable {
            override fun run() {
                val layoutManager = recordsRecyclerView.layoutManager as? LinearLayoutManager
                val adapter = recordsRecyclerView.adapter
                if (layoutManager != null && adapter != null && adapter.itemCount > 1) {
                    recordsRecyclerView.smoothScrollBy(0, 2)
                    val lastVisible = layoutManager.findLastCompletelyVisibleItemPosition()
                    if (lastVisible == adapter.itemCount - 1) {
                        recordsRecyclerView.scrollToPosition(0)
                    }
                }
                scrollHandler?.postDelayed(this, 30)
            }
        }
        scrollHandler?.postDelayed(scrollRunnable!!, 30)
    }

    private fun stopAutoScroll() {
        scrollRunnable?.let { scrollHandler?.removeCallbacks(it) }
        scrollHandler = null
        scrollRunnable = null
    }
}
