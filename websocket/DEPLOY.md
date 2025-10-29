部署与联调指南（WebSocket 心跳与绑定任务）

环境准备
- Windows 下建议使用 PowerShell 7+。
- 确认本机已安装 Node.js：在 PowerShell 执行 `node -v`。
- 如提示“node 未识别”，可尝试：
  - 使用默认安装路径：`& "C:\\Users\\<你的用户名>\\AppData\\Local\\Programs\\nodejs\\node.exe" -v`
  - 或将 Node.js 安装目录加入系统 `PATH` 后重开终端。

关键环境变量
- `PORT`：WebSocket 服务端口，默认 `3001`。
- `ENABLE_NODE_HEARTBEAT_WRITE`：启用 Node 直连数据库心跳/上下线写库，默认开启。
- `HEARTBEAT_WRITE_INTERVAL`：心跳最小写库间隔（秒），默认 `30`。
- `HEARTBEAT_OFFLINE_TIMEOUT`：心跳离线判定阈值（秒），默认 `300`。
- `HEARTBEAT_SWEEP_INTERVAL`：离线巡检间隔（秒），默认 `60`。
- `HEARTBEAT_ENFORCE_DISCONNECT`：心跳超时是否强制断开（生产默认开，开发默认关）。
- `EXTRA_CORS_ORIGIN` / `CORS_ORIGINS`：追加或覆盖允许的 CORS 来源。
- 开发联调辅助：`NODE_ENV=development`、`FORCE_DEVICE_REGISTER=true`（后端不可达时允许设备注册）。

启动服务（开发模式）
```powershell
# 方式一：已配置 PATH，可直接启动
$env:NODE_ENV='development';
$env:FORCE_DEVICE_REGISTER='true';
node ./server.js

# 方式二：未配置 PATH，使用 Node 全路径
$env:NODE_ENV='development';
$env:FORCE_DEVICE_REGISTER='true';
& "$env:LOCALAPPDATA\Programs\nodejs\node.exe" ./server.js
```

验证心跳节流与离线清理
1. 保持服务运行。
2. 在新终端运行测试脚本：
```powershell
cd ..\..\test
node .\heartbeat_burst_test.js
```
3. 观察服务端日志：
- 心跳快速到来时，仅在达到最小写库间隔后进行写库并广播在线状态。
- 设备主动断开或长时间无心跳时，触发离线标记写库并广播离线。

直连数据库绑定任务
- 新增 HTTP 接口：`POST /task/bind`
- 请求体：`{ device_id, role_bindings: {}, area_bindings: {} }`
- 服务端将任务入队并异步更新 `fa_device` 表，实现“即装即见”，不依赖 PHP 接口。

常见问题
- `ECONNREFUSED 127.0.0.1:8000`：PHP 接口不可达；开发模式可开启 `FORCE_DEVICE_REGISTER=true`，或依赖 Node 直连数据库验证。
- `node 未识别`：使用 Node 全路径或修复 `PATH`。

变更摘要
- 配置文件 `backend/websocket/config.js` 新增心跳节流与离线清理相关阈值、直连写库开关。
- `server.js`：
  - 心跳写库限流（设备级时间戳 Map）。
  - 离线巡检与强制离线标记。
  - 设备验证优先走数据库直连，失败回退 PHP 接口；开发模式支持强制注册。
  - 新增 `/task/bind` 接口接入异步绑定任务。
- 新增任务模块 `backend/websocket/tasks/bindingTask.js`。
- 新增测试脚本 `test/heartbeat_burst_test.js`。