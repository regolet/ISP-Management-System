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
echo 6. System Update (Git Pull)
echo 7. Open Web Interface
echo 8. Exit
echo.
set /p choice="Select option (1-8): "

if "%choice%"=="1" goto check_status
if "%choice%"=="2" goto start_server
if "%choice%"=="3" goto stop_server
if "%choice%"=="4" goto view_logs
if "%choice%"=="5" goto install_service
if "%choice%"=="6" goto system_update
if "%choice%"=="7" goto open_web
if "%choice%"=="8" goto exit
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

:system_update
echo.
echo [INFO] Starting system update process...
echo [WARNING] This will stop the server and update from Git repository.
echo.
set /p confirm="Continue with system update? (y/n): "
if /i not "%confirm%"=="y" (
    echo [CANCELLED] System update cancelled by user
    echo.
    pause
    goto menu
)

echo.
echo [STEP 1/6] Stopping server...
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
echo [STEP 2/6] Stashing local changes...
git stash push -m "Auto-stash before update %date% %time%" >nul 2>&1
if not errorlevel 1 (
    echo [SUCCESS] Local changes stashed
) else (
    echo [INFO] No changes to stash or stash failed
)

echo.
echo [STEP 3/6] Fetching latest changes...
git fetch origin main
if not errorlevel 1 (
    echo [SUCCESS] Fetched latest changes from remote
) else (
    echo [ERROR] Failed to fetch from remote repository
    goto update_failed
)

echo.
echo [STEP 4/6] Pulling updates...
git pull origin main
if not errorlevel 1 (
    echo [SUCCESS] Successfully pulled latest changes
) else (
    echo [ERROR] Git pull failed
    goto update_failed
)

echo.
echo [STEP 5/6] Installing/updating dependencies...
npm install
if not errorlevel 1 (
    echo [SUCCESS] Dependencies updated successfully
) else (
    echo [WARNING] npm install completed with warnings (check output above)
)

echo.
echo [STEP 6/6] Checking repository status...
git status --porcelain > nul 2>&1
if not errorlevel 1 (
    echo [INFO] Repository status:
    git status --short
) else (
    echo [INFO] Repository status check completed
)

echo.
echo [SUCCESS] System update completed successfully!
echo [INFO] Current version: 
git rev-parse --short HEAD
echo.
echo [RECOMMENDATION] Restart the server to apply updates
echo.
set /p restart_choice="Would you like to restart the server now? (y/n): "
if /i "%restart_choice%"=="y" (
    echo [INFO] Restarting server...
    call restart-server.bat
) else (
    echo [INFO] Remember to restart the server when ready
)
goto update_complete

:update_failed
echo.
echo [ERROR] System update failed!
echo [INFO] You may need to manually resolve conflicts
echo [INFO] Try running: git status
echo.
pause
goto menu

:update_complete
echo.
pause
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