@echo off
:menu
cls
echo ========================================
echo   ISP Management Server Manager
echo ========================================
echo.
echo 1. Check Server Status
echo 2. Start/Restart Server
echo 3. Stop Server
echo 4. View Server Logs (PM2)
echo 5. Install as Windows Service
echo 6. Open Web Interface
echo 7. Exit
echo.
set /p choice="Select option (1-7): "

if "%choice%"=="1" goto check_status
if "%choice%"=="2" goto start_server
if "%choice%"=="3" goto stop_server
if "%choice%"=="4" goto view_logs
if "%choice%"=="5" goto install_service
if "%choice%"=="6" goto open_web
if "%choice%"=="7" goto exit
goto menu

:check_status
echo.
echo [INFO] Checking server status...
call check-server.bat
goto menu

:start_server
echo.
echo [INFO] Starting/Restarting server...
call restart-server.bat
goto menu

:stop_server
echo.
echo [INFO] Stopping server...
pm2 stop isp >nul 2>&1
if not errorlevel 1 (
    echo [SUCCESS] Server stopped via PM2
) else (
    echo [INFO] Stopping manual processes...
    for /f "tokens=5" %%a in ('netstat -aon ^| find ":3000" ^| find "LISTENING"') do (
        echo Stopping process %%a...
        taskkill /f /pid %%a 2>nul
    )
    echo [SUCCESS] Server stopped
)
echo.
pause
goto menu

:view_logs
echo.
echo [INFO] Opening PM2 logs...
pm2 logs isp --lines 50
if errorlevel 1 (
    echo [WARNING] PM2 not available - logs not accessible
    echo [INFO] Check Windows Event Viewer for application logs
)
echo.
pause
goto menu

:install_service
echo.
echo [INFO] Installing Windows service...
call install-service.bat
goto menu

:open_web
echo.
echo [INFO] Opening web interface...
start http://localhost:3000
echo [SUCCESS] Browser opened
echo.
pause
goto menu

:exit
echo.
echo Goodbye!
exit /b 0