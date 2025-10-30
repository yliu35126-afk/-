# APP 端接通指南

目标：让 Android APP 即装即连到后端（含转盘插件）。本指南覆盖环境要求、构建安装、连通验证与常见问题。

## 环境准备
- Android Studio（含 SDK、平台工具）。
- 一台 Android 真机或模拟器。
- 后端已可通过 HTTPS 访问：`https://yiqilishi.com.cn/`（或你的域名）。

## 构建与安装
- 当前调试包使用本地联调地址：`http://${LAN_IP}:8000/index.php/api/`；Socket.IO：`http://${LAN_IP}:3001`。
- 在 Android Studio 打开 `clients/app/src` 项目后：
  - 选择 Build Variant：`standard21Debug`（或 `legacy19Debug` 兼容老盒子）。
  - 点击 Run/Build 安装到设备。
- 如果需要指向线上域名，可在 `clients/app/src/build.gradle.kts` 的 `debug` 段改为 HTTPS 域名。

## 本地调试（LAN_IP）
- 模拟器：无需设置，`LAN_IP` 默认是 `10.0.2.2` 指向宿主机。
- 真机/盒子：把宿主机的局域网 IP 注入构建（示例 `192.168.1.23`）：
  - Android Studio：在 Gradle Properties 或环境变量设置 `LAN_IP=192.168.1.23`，然后构建 `standard21Debug`。
  - 命令行（Gradle Wrapper）：在项目目录 `clients/app/src` 执行 `.\gradlew.bat assembleStandard21Debug -PLAN_IP=192.168.1.23`。
  - 端口分工：HTTP API 走 `8000`；Socket.IO 固定走 `3001`。

## 连通验证（必须）
1) 启动 APP，首次会显示注册引导页，复制设备ID并进入主界面。
2) APP 会自动执行以下动作：
   - 设备注册：`POST device/register`。
   - 奖品列表：`GET prizeapi/list`（含 `device_id`、`config_id`）。
   - 转盘奖品：`GET addons/turntable/api/turntable/prizeList`（按需传 `device_id` 或 `device_sn`）。
   - WebSocket：连接 `SOCKET_BASE_URL + SOCKET_PATH`，监听抽奖指令。
3) 在 Logcat 搜索关键日志：
   - `ApiTest` / `PrizeRepository` / `TurntableRepository` 的 `✓` 成功输出。
   - `WebSocketManager` 的 `=== 初始化`、`已连接`、`心跳正常`。

## 手动测试（可选）
- 运行 Turntable 测试：在 `MainActivity` 已集成流程，或调用 `ApiTest.testTurntableApis()`（需设置有效 `deviceId/siteId/boardId`）。
- 触发一次抽奖：向后端发送 `draw` 指令或在设备端点击抽奖，观察 `LotteryProcessor` 的结果回调。

## 路由与基址说明
- 通用 API 基址：`index.php/api/`。
- 插件 API 基址：`index.php/`（避免形成 `index.php/api/addons/...` 造成 404）。
- APP 已通过 `NetworkConfig` 分别配置 Retrofit 基址并自动回退 `alternateBaseUrl` 与 `fallbackApiPhpBaseUrl`。

## 常见问题
- 404：检查 Nginx 反代是否暴露 `index.php` 与 `api.php`；必要时启用回退基址。
- 跨域/明文HTTP：线上使用 HTTPS；局域网调试已在 `network_security_config.xml` 放行常见域名。
- 图片不显示：使用 `ImageLoader` 的 `imageBaseUrl()`，避免将 `index.php/api/` 用于图片路径。

## 验收标准
- 能成功注册设备并拉取奖品列表与转盘槽位。
- 能收到 WebSocket 指令并播放抽奖动画，返回抽奖结果。
- 抽奖记录与收件信息流程可正常调用（命中实物时）。