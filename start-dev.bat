@echo off
setlocal
REM Change to script directory
cd /d "%~dp0"

set PORT=8000

REM Check PHP is available
php -v >nul 2>&1
if errorlevel 1 (
  echo [ERROR] PHP is not available in PATH. Please install PHP or add it to PATH.
  exit /b 1
)

echo Starting PHP built-in server on http://localhost:%PORT% ...
echo Document root: "%CD%\public"
echo Router script: "public\router.php"
echo Press Ctrl+C to stop.

php -S localhost:%PORT% -t public public/router.php