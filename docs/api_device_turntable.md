# 设备抽奖 API（device_turntable）

基础路径：`/addons/device_turntable/api`

## 价档查询
- 路由：`GET /turntable/amounttiers`
- 入参：`device_sn | device_id`
- 返回：`{ list: [{ tier_id,title,price,status }], count }`

示例：
```
GET /addons/device_turntable/api/turntable/amounttiers?device_sn=SN001
```

## 奖品列表
- 路由：`GET /turntable/prizelist`
- 入参：`site_id, device_sn|device_id [,board_id]`
- 返回：抽奖盘 16 格及权重/库存信息（与通用 turntable 一致）

## 抽一次
- 路由：`POST /turntable/draw`
- 认证：需要用户 token
- 入参：`site_id, device_sn|device_id [,board_id], tier_id`
- 返回：`{ result: hit|miss, hit, amount }`

备注：命中实物时会在 `lottery_record.ext.order` 写入奖励单与 `verify_code`。

## 填写地址（实物）
- 路由：`POST /turntable/address`
- 认证：需要用户 token
- 入参：`record_id, name, mobile, address, [province_id,city_id,district_id,full_address,longitude,latitude]`

## 到店核销
- 路由：`POST /turntable/verify`
- 认证：需要用户 token
- 入参：`record_id, verify_code [, need_ship]`
  - 说明：默认现场领取；当场缺货或选择后续配送时传 `need_ship=1`（需先填写地址），将写入 `ext.order.delivery_status='to_ship'` 供后续发货流程使用。

## 抽奖记录
- 路由：`GET /turntable/record`
- 入参：`[page,page_size,device_id]`
- 认证：可选；未登录时仅支持按 `device_id` 查询或返回空列表

---

# 核销流程

## 核销码来源
- 命中实物奖品（`award_type=goods`）时，在抽奖流程（`Lottery.php::draw`）生成核销码：`md5(record_id-member_id-device_id)`。
- 将核销码与轻量订单信息写入 `lottery_record.ext.order.verify_code`，并在通用核销中心新增一条待核销记录：`verify.verify_type=turntable_goods, relate_id=record_id, is_verify=0`。

## 核销入口
- 设备端快捷核销：`POST /addons/device_turntable/api/turntable/verify`
  - 入参：`record_id, verify_code`
- 通用核销中心接口：
  - 查询核销信息：`GET /api/verify/verifyInfo?verify_code=XXXX`
  - 执行核销：`POST /api/verify/verify`（需核销员/店铺后台登录态）
  - 核销类型查询：`GET /api/verify/getVerifyType`（包含 `turntable_goods`）

## 状态回写
- 核销成功后：
  - 写入 `lottery_record.ext.order.status = verified`
  - 写入 `verify_time`、`verifier_id`、`verifier_name`
  - 若 `need_ship=1` 且已填写地址，写入 `ext.order.delivery_status='to_ship'`
- 同时将通用核销中心记录 `verify.is_verify = 1`。

示例：
```
ext.order = {
  "record_id": 1001,
  "verify_code": "md5(...)",
  "status": "verified",
  "verify_time": 1700000000,
  "verifier_id": 23,
  "verifier_name": "店员A",
  "delivery_status": "to_ship" // 当且仅当 need_ship=1 且已有地址
}
```

## 联调要点
- `site_id` 归属：优先使用设备表 `device_info.site_id`，无则回退为抽奖入参 `site_id`。
- 幂等处理：按 `verify_code` 唯一去重，重复核销返回已核销状态。
- 权限校验：通用核销接口需核销员或店铺后台登录态。

## 与结算/分润串联（建议）
- 以 `ext.order.status=verified` 作为结算触发条件：
  - 发货（少量场景）：当场缺货或选择后续配送时，传 `need_ship=1`，并在供应商后台按 `ext.order.delivery_status='to_ship'` 进入发货流程；
  - 到店领取（主流程）：默认现场扫码核销后直接领取，无发货。
  - 结算：在 `turntable_settlement` 或独立任务中，按 `verified` 聚合做供应商结算；
  - 分润：以 `verified` 为完成态入账，结合 `ext.profit_groups` 做分摊。

# 错误码
# 订单与支付

## 创建订单
- 路由：`POST /order/create`
- 认证：需要用户 token
- 入参：`site_id, device_sn|device_id, tier_id`
- 返回：`{ order_no, amount, subject, attach }`
  - 将 `order_no/amount/subject` 交给支付网关
  - 回调需原样传回 `attach`

## 支付回调
- 路由：`POST /order/paynotify`
- 认证：无需
- 入参：`order_no, total_amount, trade_status, attach [,ts,sign]`
- 返回：`{ order_no, draw: { result, hit, amount } }`

签名（可选）：启用环境变量 `LOTTERY_PAY_SECRET` 后，网关需计算
```
HMAC-SHA256("order_no|amount|status|ts", LOTTERY_PAY_SECRET)
```

状态：`trade_status in [success, finished, paid]` 视为成功，其余忽略。

---

# 错误码

放在 `app/api/lang/zh-cn.php`：
- `MISSING_DEVICE`：缺少设备标识
- `DEVICE_NOT_FOUND`：设备不存在
- `BOARD_NOT_BOUND`：设备未绑定抽奖盘
- `DEVICE_TIER_UNBOUND`：该设备未绑定此价档
- `TIER_INVALID`：价档不可用
- `ADDR_INCOMPLETE`：地址信息不完整
- `VERIFY_CODE_INVALID`：核销码不正确
- `SIGN_MISSING_OR_EXPIRED`：签名缺失或过期
- `SIGN_INVALID`：签名校验失败
- `PARAM_INCOMPLETE`：参数不完整
- `NON_SUCCESS_STATUS`：非成功态
- `CONTEXT_INCOMPLETE`：回调上下文不完整

---

# 表结构说明（关键）
- `lottery_record`：抽奖记录，`ext` 中含 `order`、`pay`、`shipping_address`、`profit_groups`
- `lottery_profit`：分润占位（累计金额）
- `lottery_settlement`：供应商结算占位（pending→done）