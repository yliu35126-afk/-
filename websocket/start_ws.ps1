# 启动 WebSocket 服务脚本
param(
    [string]$Port = "3001"
)

Write-Host "Starting WebSocket server on port $Port..."
$env:PORT=$Port
$env:WS_PORT=$Port

# 环境模式与后端 API 地址（与本地 PHP 端口 8000 对齐）
$env:ENV_MODE='local'
$env:API_HOST='127.0.0.1'
$env:API_PORT='8000'
$env:API_BASE_URL='http://127.0.0.1:8000/index.php/api'

# CORS 允许来源（联调：统一到 8000）
$env:CORS_ORIGINS='http://127.0.0.1:8000,http://localhost:8000'

# 数据库连接（与 config/database.php 保持一致）
$env:DB_HOST='127.0.0.1'
$env:DB_USER='niushop'
$env:DB_PASSWORD='niushop'
$env:DB_NAME='b2b2c503'
$env:DB_PORT='3306'

# 开启 Node 直写心跳（可按需关闭）
$env:ENABLE_NODE_HEARTBEAT_WRITE='1'

# 执行字段巡检脚本，确保 fa_device.config_id 存在（失败不阻断启动）
Write-Host "Checking DB schema: fa_device.config_id"
& 'D:\phpstudy_pro\Extensions\nodejs\node-v22.11.0-win-x64\node.exe' "$PSScriptRoot\tools\check_and_add_config_id_column.js"
if ($LASTEXITCODE -ne 0) { Write-Host "Schema check warning, continue" }

# 启动新版 server_new.js（含预热逻辑）
& 'D:\phpstudy_pro\Extensions\nodejs\node-v22.11.0-win-x64\node.exe' "$PSScriptRoot\server_new.js"