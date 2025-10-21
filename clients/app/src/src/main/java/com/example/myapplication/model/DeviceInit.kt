package com.example.myapplication.model

import com.google.gson.annotations.SerializedName
import com.google.gson.JsonDeserializationContext
import com.google.gson.JsonDeserializer
import com.google.gson.JsonElement
import com.google.gson.JsonObject
import com.google.gson.JsonPrimitive
import com.google.gson.annotations.JsonAdapter
import java.lang.reflect.Type

/**
 * 设备初始化响应
 */
data class DeviceInitResponse(
    @SerializedName("code")
    val code: Int,
    
    @SerializedName("msg")
    val msg: String,
    
    @SerializedName("time")
    val time: String,
    
    @SerializedName("data")
    @JsonAdapter(DeviceInitDataDeserializer::class)
    val data: DeviceInitData?
)

/**
 * 自定义反序列化器处理data字段可能是字符串的情况
 */
class DeviceInitDataDeserializer : JsonDeserializer<DeviceInitData> {
    override fun deserialize(
        json: JsonElement,
        typeOfT: Type,
        context: JsonDeserializationContext
    ): DeviceInitData? {
        return when {
            json.isJsonObject -> {
                // 正常的对象格式
                context.deserialize(json, DeviceInitData::class.java)
            }
            json.isJsonPrimitive && json.asJsonPrimitive.isString -> {
                // 字符串格式（包括空字符串），表示设备未注册或出错
                val message = json.asString.ifEmpty { "设备不存在，请先注册设备" }
                null // 返回null，让Repository层处理默认数据创建
            }
            json.isJsonNull -> {
                // null值，也表示设备未注册
                null
            }
            else -> null
        }
    }
}

/**
 * 设备初始化数据
 */
data class DeviceInitData(
    @SerializedName("device_id")
    val deviceId: String,
    
    @SerializedName("is_initialized")
    val isInitialized: Boolean,
    
    @SerializedName("status")
    val status: String,
    
    @SerializedName("message")
    val message: String,
    
    @SerializedName("missing_roles")
    val missingRoles: List<RoleInfo>,
    
    @SerializedName("bound_roles")
    val boundRoles: List<BoundRoleInfo>,
    
    @SerializedName("prize_check")
    val prizeCheck: PrizeCheck,
    
    @SerializedName("device_info")
    val deviceInfo: DeviceInfoData,

    // 是否需要位置授权
    @SerializedName("need_location_permission")
    val needLocationPermission: Boolean = false,

    // 是否自动关闭弹窗（达到最低初始化要求）
    @SerializedName("auto_close_dialog")
    val autoCloseDialog: Boolean = false,

    // 新增：设备绑定的分润配置ID列表（可选）
    @SerializedName("profit_config_ids")
    val profitConfigIds: List<Int>? = null,

    // 新增：后端推荐的默认分润配置ID（可选）
    @SerializedName("recommended_config_id")
    val recommendedConfigId: Int? = null
)

/**
 * 角色信息（缺失的角色）
 */
data class RoleInfo(
    @SerializedName("field")
    val field: String,
    
    @SerializedName("name")
    val name: String
)

/**
 * 绑定的角色信息
 */
data class BoundRoleInfo(
    @SerializedName("field")
    val field: String,
    
    @SerializedName("name")
    val name: String,
    
    @SerializedName("admin_id")
    val adminId: Int
)

/**
 * 奖品检测结果
 */
data class PrizeCheck(
    @SerializedName("sufficient")
    val sufficient: Boolean,
    
    @SerializedName("current_count")
    val currentCount: Int,
    
    @SerializedName("required_count")
    val requiredCount: Int,
    
    @SerializedName("message")
    val message: String
)

/**
 * 设备信息
 */
data class DeviceInfoData(
    @SerializedName("install_location")
    val installLocation: String,
    
    @SerializedName("merchant")
    val merchant: String,
    
    @SerializedName("is_debug")
    val isDebug: Int,
    
    @SerializedName("createtime")
    val createTime: String,
    
    @SerializedName("updatetime")
    val updateTime: String
)
