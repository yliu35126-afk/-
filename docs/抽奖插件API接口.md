# 幸运抽奖插件 API 接口说明（turntable）

基础路径：`/addons/turntable/api/turntable`
说明：以下接口均基于 `addon/turntable/api/controller/Turntable.php` 当前实现整理，参数命名与返回结构以代码为准。

## 获取游戏详情
- 路由：`GET /info`
- 入参：`game_id, site_id`
- 认证：可选；若开启参与频次限制，会在登录态下返回用户剩余次数 `surplus_num`
- 返回：游戏详情，含“是否展示中奖名单”时的最近中奖记录片段

## 奖品列表（16格）
- 路由：`GET /prizeList`
- 入参：`site_id, device_sn | device_id [, board_id]`
- 说明：
  - 若传 `device_id` 将自动解析为对应 `device_sn/board_id`
  - 返回 16 格列表；不足 16 时用真实奖品补齐；剔除“谢谢参与”
  - 若设备已绑定价档，返回生效价档与 `profit_info`（分润金额映射，自动识别“定额/比例”）
- 返回：`{ board, slots[ { position, prize_type, weight, inventory, round_qty, ... } ], [tier], [profit_info] }`

## 抽一次
- 路由：`POST /draw`
- 入参：`site_id, device_sn | device_id [, board_id], [tier_id]`
- 认证：需要用户 token
- 保护：同用户同设备 3s 频控；同盘并发锁（Redis NX 或缓存锁）
- 返回：命中记录，示例：`{ code, data:{ record_id, slot_id, prize_type, amount, round_no, ... } }`

## 填写收件地址（仅实物奖品）
- 路由：`POST /address`
- 入参：`record_id, name, mobile, address, [province_id, city_id, district_id, full_address, longitude, latitude]`
- 认证：需要用户 token
- 返回：`{ record_id }`（写入到 `lottery_record.ext.shipping_address`）

## 到店核销
- 路由：`POST /verify`
- 入参：`record_id, verify_code`
- 认证：需要用户 token（会员本人核销）
- 行为：将 `ext.order.status` 更新为 `verified`；入队 `lottery_settlement` 并触发事件 `turntable_settlement`（幂等）
- 返回：`{ record_id, status: verified }`

## 抽奖记录分页
- 路由：`GET /record`
- 入参：`[page, page_size, device_id]`
- 认证：可选；若未登录且未指定 `device_id`，返回空列表以保护隐私
- 返回：`{ list:[ { ... , order:{ ... }, shipping_address:{ ... }, verify_status } ], count }`

---

## 错误码（示例）
- `设备不存在 / DEVICE_NOT_FOUND`
- `设备未绑定盘 / BOARD_NOT_BOUND`
- `设备未绑定该价档 / DEVICE_TIER_UNBOUND`
- `抽奖参数错误 / PARAM_INVALID`
- `地址信息不完整 / ADDR_INCOMPLETE`
- `核销码不正确 / VERIFY_CODE_INVALID`
- `操作过于频繁 / RATE_LIMITED`
- `系统繁忙，请稍后重试 / SYSTEM_BUSY`

实际错误消息以接口返回 `message` 为准。

---

## 请求示例

1) 奖品列表
```
GET /addons/turntable/api/turntable/prizeList?site_id=1&device_id=1001
```

2) 抽一次
```
POST /addons/turntable/api/turntable/draw
Body: {"site_id":1,"device_id":1001,"tier_id":5}
Header: Authorization: Bearer <token>
```

3) 填地址
```
POST /addons/turntable/api/turntable/address
Body: {"record_id":123,"name":"张三","mobile":"13800000000","address":"XX路XX号"}
Header: Authorization: Bearer <token>
```

4) 到店核销
```
POST /addons/turntable/api/turntable/verify
Body: {"record_id":123,"verify_code":"ABCD12"}
Header: Authorization: Bearer <token>
```

5) 记录分页
```
GET /addons/turntable/api/turntable/record?page=1&page_size=10&device_id=1001
```

---

## 关联文档
- 设备抽奖 API（含价档/核销中心联动）：`docs/api_device_turntable.md`
- 后台操作 SOP（人性化版）：`docs/交接_后台操作SOP_幸运抽奖_人性化版.md`
- 全链路落地视图：`docs/交接_幸运抽奖全链路_落地视图.md`

（以上为插件级 API 的成文说明，用于外部/前端联调与交付参考）