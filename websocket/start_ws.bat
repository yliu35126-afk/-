@echo off
setlocal enabledelayedexpansion
REM 一键启动 WebSocket 服务（默认使用小皮 Node，回退为系统 node）

set WS_DIR=%~dp0
set NODE_EXE=D:\phpstudy_pro\Extensions\nodejs\node-v22.11.0-win-x64\node.exe

if exist "%NODE_EXE%" (
  echo 使用小皮 Node: "%NODE_EXE%"
  "%NODE_EXE%" "%WS_DIR%server.js"
) else (
  echo 使用系统 PATH 中的 node
  node "%WS_DIR%server.js"
)

echo 已启动，按 Ctrl+C 可停止。
pause