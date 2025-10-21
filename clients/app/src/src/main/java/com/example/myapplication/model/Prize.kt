package com.example.myapplication.model

import com.google.gson.annotations.SerializedName

/**
 * 奖品数据模型
 */
data class Prize(
    @SerializedName("id")
    val id: Int = 0,

    @SerializedName("supplier")
    val supplier: String? = "系统默认",

    @SerializedName(value = "name", alternate = ["prize_name"])
    val prizeName: String = "",

    @SerializedName(value = "image", alternate = ["prize_image", "pic_url"])
    val prizeImage: String = "",

    @SerializedName("status")
    val status: Int = 1,

    @SerializedName("sort")
    val sort: Int = 0,

    @SerializedName("price")
    val price: Double = 0.0,

    @SerializedName("original_price")
    val originalPrice: String = "0.00",

    @SerializedName("activity_price")
    val activityPrice: String = "0.00",

    @SerializedName("type")
    val type: String = "",

    @SerializedName("probability")
    val probability: Double = 0.0,

    @SerializedName("status_text")
    val statusText: String = "",

    @SerializedName("is_depleted")
    val isDepleted: Int = 0,  // 新增字段：1=库存为0（不可抽中），0=有库存

    @SerializedName("stock_quantity")
    val stockQuantity: Int = 0  // 新增字段：库存数量
)

/**
 * 奖品列表响应
 */
data class PrizeListResponse(
    @SerializedName("code")
    val code: Int,
    
    @SerializedName("msg")
    val msg: String,
    
    @SerializedName("data")
    val data: PrizeListData
)

data class PrizeListData(
    @SerializedName("total")
    val total: Int,
    
    @SerializedName("list")
    val list: List<Prize>,
    
    @SerializedName("page")
    val page: Int,
    
    @SerializedName("limit")
    val limit: Int
)

/**
 * 奖品详情响应
 */
data class PrizeDetailResponse(
    @SerializedName("code")
    val code: Int,
    
    @SerializedName("msg")
    val msg: String,
    
    @SerializedName("data")
    val data: Prize
)

/**
 * 分润奖品响应
 */
data class ProfitPrizesResponse(
    @SerializedName("code")
    val code: Int,
    
    @SerializedName("msg")
    val msg: String,
    
    @SerializedName("time")
    val time: Long,
    
    @SerializedName("data")
    val data: ProfitPrizesData
)

data class ProfitPrizesData(
    @SerializedName("device_id")
    val deviceId: String,
    
    @SerializedName("lottery_amount")
    val lotteryAmount: Double,
    
    @SerializedName("config_id")
    val configId: Int,
    
    @SerializedName("config_name")
    val configName: String,
    
    @SerializedName("supplier_prizes")
    val supplierPrizes: List<SupplierPrize>,
    
    @SerializedName("merchant_prizes")
    val merchantPrizes: List<MerchantPrize>,
    
    @SerializedName("total_supplier_prizes")
    val totalSupplierPrizes: Int,
    
    @SerializedName("total_merchant_prizes")
    val totalMerchantPrizes: Int,
    
    @SerializedName("total_prizes")
    val totalPrizes: Int
)

/**
 * 供应商奖品
 */
data class SupplierPrize(
    @SerializedName("id")
    val id: Int = 0,

    @SerializedName(value = "name", alternate = ["prize_name"])
    val name: String = "",

    @SerializedName("price")
    val price: Double = 0.0,

    @SerializedName("original_price")
    val originalPrice: String? = "0.00",

    @SerializedName("activity_price")
    val activityPrice: String? = "0.00",

    @SerializedName(value = "image", alternate = ["prize_image", "pic_url"])
    val image: String = "",

    @SerializedName("description")
    val description: String = ""
)

/**
 * 商家奖品
 */
data class MerchantPrize(
    @SerializedName("id")
    val id: Int = 0,

    @SerializedName(value = "name", alternate = ["prize_name"])
    val name: String = "",

    @SerializedName("price")
    val price: Double = 0.0,

    @SerializedName("original_price")
    val originalPrice: String? = "0.00",

    @SerializedName("activity_price")
    val activityPrice: String? = "0.00",

    @SerializedName(value = "image", alternate = ["prize_image", "pic_url"])
    val image: String = "",

    @SerializedName("description")
    val description: String = ""
)
