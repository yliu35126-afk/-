@echo off
setlocal enabledelayedexpansion
cd /d "%~dp0"

set PORT=8000
set KILLED=0

echo Searching for process listening on port %PORT% ...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr ":%PORT%" ^| findstr LISTENING') do (
  echo Killing PID %%a ...
  taskkill /PID %%a /F >nul 2>&1
  if not errorlevel 1 (
    set KILLED=1
  )
)

if !KILLED!==1 (
  echo Stopped server on http://localhost:%PORT%.
) else (
  echo No LISTENING process found on port %PORT% or kill failed.
)

endlocal