package com.example.myapplication.utils

import android.util.Log

/**
 * 简单的“invalid index”监控器：记录出现次数与速率，便于后续优化。
 */
object InvalidIndexMonitor {
    private var count: Int = 0
    private var startMs: Long = System.currentTimeMillis()

    @Synchronized
    fun record(tag: String = "InvalidIndexMonitor", detail: String? = null) {
        count++
        if (count % 10 == 0) {
            val elapsed = (System.currentTimeMillis() - startMs).coerceAtLeast(1)
            val ratePerMin = count * 60000.0 / elapsed
            Log.w(
                tag,
                "invalid index occurrences=$count, elapsed=${elapsed}ms, rate=${String.format("%.1f", ratePerMin)}/min" +
                        (detail?.let { ", detail=$it" } ?: "")
            )
        }
    }

    @Synchronized
    fun snapshot(): Pair<Int, Long> = count to (System.currentTimeMillis() - startMs)

    @Synchronized
    fun reset() {
        count = 0
        startMs = System.currentTimeMillis()
    }
}