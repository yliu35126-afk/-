package com.example.myapplication

import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.widget.Button
import android.widget.CheckBox
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.example.myapplication.utils.DeviceUtils

/**
 * 注册引导页面：展示设备ID，支持复制，提供进入主界面按钮。
 * - 首次安装默认展示；勾选“下次不再显示”后后续直接进入主界面。
 * - 重新安装会清空偏好设置，页面会再次显示。
 */
class RegistrationActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // 若用户选择跳过注册页，直接进入主界面
        if (shouldSkip(this)) {
            startActivity(Intent(this, MainActivity::class.java))
            finish()
            return
        }

        setContentView(R.layout.activity_registration)
        supportActionBar?.hide()

        val deviceId = DeviceUtils.getDeviceId(this)
        val tvDeviceId = findViewById<TextView>(R.id.tvDeviceId)
        val btnCopyId = findViewById<Button>(R.id.btnCopyId)
        val btnContinue = findViewById<Button>(R.id.btnContinue)
        val cbSkipNext = findViewById<CheckBox>(R.id.cbSkipNext)

        tvDeviceId.text = deviceId

        btnCopyId.setOnClickListener {
            val cm = getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
            cm.setPrimaryClip(ClipData.newPlainText("deviceId", deviceId))
            Toast.makeText(this, "设备ID已复制", Toast.LENGTH_SHORT).show()
        }

        btnContinue.setOnClickListener {
            if (cbSkipNext.isChecked) {
                getSharedPreferences("app_prefs", Context.MODE_PRIVATE)
                    .edit().putBoolean(KEY_SKIP_REG, true).apply()
            }
            startActivity(Intent(this, MainActivity::class.java))
            finish()
        }
    }

    companion object {
        private const val KEY_SKIP_REG = "skip_registration"

        fun shouldSkip(context: Context): Boolean {
            return context.getSharedPreferences("app_prefs", Context.MODE_PRIVATE)
                .getBoolean(KEY_SKIP_REG, false)
        }
    }
}