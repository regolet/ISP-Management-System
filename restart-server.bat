@echo off
echo ========================================
echo   ISP Management Server Restart Tool
echo ========================================
echo.

echo [INFO] Checking for running processes...

:: Check if PM2 service is running
pm2 list >nul 2>&1
if not errorlevel 1 (
    echo [INFO] PM2 service detected - Restarting via PM2...
    pm2 restart isp
    echo [SUCCESS] Server restarted via PM2!
    echo [INFO] Service URL: http://localhost:3000
    echo.
    pause
    exit /b 0
)

:: Fallback to manual restart
echo [INFO] PM2 not detected - Manual restart...

echo [INFO] Killing existing processes on port 3000...
for /f "tokens=5" %%a in ('netstat -aon ^| find ":3000" ^| find "LISTENING"') do (
    echo Stopping process %%a...
    taskkill /f /pid %%a 2>nul
)

echo [INFO] Waiting 3 seconds...
timeout /t 3 /nobreak >nul

echo [INFO] Starting server...
start /b npm start

echo [INFO] Waiting for server to start...
timeout /t 5 /nobreak >nul

echo [SUCCESS] Server restarted manually!
echo [INFO] Service URL: http://localhost:3000
echo.
echo The server is now running in the background.
echo Use this script again to restart if needed.
echo.
pause