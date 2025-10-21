package com.example.myapplication

import android.content.Context
import android.media.MediaPlayer
import android.util.Log

/**
 * 简单的三段音乐播放管理：前奏/加速(中段循环)/收尾
 * 需在 res/raw 放置：lottery_intro、lottery_mid、lottery_outro（mp3/ogg均可）
 */
class SoundManager(private val context: Context) {
    private var intro: MediaPlayer? = null
    private var mid: MediaPlayer? = null
    private var outro: MediaPlayer? = null
    private val mainHandler = android.os.Handler(android.os.Looper.getMainLooper())
    private val scheduled = mutableListOf<Runnable>()
    var onOutroComplete: (() -> Unit)? = null

    fun init() {
        try {
            intro = safeCreate(R.raw.lottery_intro)
            mid = safeCreate(R.raw.lottery_mid)?.apply { isLooping = true }
            outro = safeCreate(R.raw.lottery_outro)
            try {
                outro?.setOnCompletionListener { onOutroComplete?.invoke() }
            } catch (_: Exception) {}
        } catch (e: Exception) {
            Log.e("SoundManager", "init error", e)
        }
    }

    private fun safeCreate(resId: Int): MediaPlayer? {
        return try { MediaPlayer.create(context, resId) } catch (e: Exception) { null }
    }

    /**
     * 新版时间线：手动控制收尾，确保中段(loop)可持续到动画真正结束。
     * - 立即播放前奏；在 delayMs 后切入中段并循环；
     * - 由外部在动画结束时调用 [switchToOutro] 进入收尾。
     */
    fun startIntroThenLoop(delayMs: Long = 2500L) {
        cancelScheduled()
        playIntro()
        val r = Runnable { playMid() }
        scheduled.add(r)
        mainHandler.postDelayed(r, delayMs)
    }

    /** 保持当前loop，不做切换（用于外部强制继续）。 */
    fun continueLoop() { try { mid?.isLooping = true; if (mid?.isPlaying != true) mid?.start() } catch (_: Exception) {} }

    /** 在需要显示结果的瞬间触发收尾。 */
    fun switchToOutro() { try { mid?.pause() } catch (_: Exception) {}; playOutro(); cancelScheduled() }

    fun playIntro() { try { intro?.seekTo(0); intro?.start() } catch (_: Exception) {} }
    fun playMid() { try { mid?.isLooping = true; mid?.seekTo(0); mid?.start() } catch (_: Exception) {} }
    fun playOutro() {
        try {
            mid?.pause()
            // 确保每次播放收尾都能回调
            try { outro?.setOnCompletionListener { onOutroComplete?.invoke() } } catch (_: Exception) {}
            outro?.seekTo(0)
            outro?.start()
        } catch (_: Exception) {}
    }

    fun isReady(): Boolean = (intro != null || mid != null || outro != null)

    fun stopAll() {
        try { intro?.pause() } catch (_: Exception) {}
        try { mid?.pause() } catch (_: Exception) {}
        try { outro?.pause() } catch (_: Exception) {}
        cancelScheduled()
    }

    private fun cancelScheduled() {
        scheduled.forEach { mainHandler.removeCallbacks(it) }
        scheduled.clear()
    }

    fun release() {
        listOf(intro, mid, outro).forEach { mp ->
            try { mp?.stop(); mp?.release() } catch (_: Exception) {}
        }
        intro = null; mid = null; outro = null
    }
}