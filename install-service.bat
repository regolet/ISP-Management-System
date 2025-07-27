@echo off
echo ========================================
echo   Install ISP Management as Service
echo ========================================
echo.

:: Check if running as administrator
net session >nul 2>&1
if errorlevel 1 (
    echo [ERROR] This script requires administrator privileges!
    echo Right-click and select "Run as administrator"
    echo.
    pause
    exit /b 1
)

:: Check if Node.js is installed
node --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Node.js is not installed!
    echo Please install Node.js from: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

:: Install PM2 globally if not installed
echo [INFO] Installing PM2 process manager...
npm install -g pm2
npm install -g pm2-windows-service

echo [INFO] Installing dependencies...
npm install

echo [INFO] Setting up PM2 service...
pm2-service-install -n "ISP-Management"

echo [INFO] Starting ISP Management System service...
pm2 start ecosystem.config.js
pm2 save
pm2 startup

echo.
echo ========================================
echo   Service Installation Complete!
echo ========================================
echo.
echo The ISP Management System is now installed as a Windows service.
echo It will automatically start on boot and restart if it crashes.
echo.
echo Service Management Commands:
echo   pm2 list           - Show running processes
echo   pm2 restart isp    - Restart the service
echo   pm2 stop isp       - Stop the service
echo   pm2 logs isp       - View logs
echo   pm2 monit          - Monitor dashboard
echo.
echo Web Interface: http://localhost:3000
echo.
pause