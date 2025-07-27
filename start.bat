@echo off
echo ========================================
echo   ISP Management System Launcher
echo ========================================
echo.

:: Check if Node.js is installed
node --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Node.js is not installed!
    echo Please install Node.js from: https://nodejs.org/
    echo.
    pause
    exit /b 1
)

:: Check if npm is available
npm --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] npm is not available!
    echo Please ensure Node.js is properly installed.
    echo.
    pause
    exit /b 1
)

:: Check if package.json exists
if not exist "package.json" (
    echo [ERROR] package.json not found!
    echo Please ensure you're running this from the project directory.
    echo.
    pause
    exit /b 1
)

:: Check if node_modules exists, if not run npm install
if not exist "node_modules\" (
    echo [INFO] Installing dependencies...
    npm install
    if errorlevel 1 (
        echo [ERROR] Failed to install dependencies!
        echo.
        pause
        exit /b 1
    )
    echo [SUCCESS] Dependencies installed successfully!
    echo.
)

echo [INFO] Killing existing processes on port 3000...
for /f "tokens=5" %%a in ('netstat -aon ^| find ":3000" ^| find "LISTENING"') do (
    echo Killing process %%a...
    taskkill /f /pid %%a 2>nul
)
echo.

echo [INFO] Starting ISP Management System on port 3000...
start /b npm start

echo [INFO] Waiting for server to start...
timeout /t 8 /nobreak >nul

echo [INFO] Opening http://localhost:3000 in your browser...
start http://localhost:3000

echo.
echo ========================================
echo   Server is now running!
echo   URL: http://localhost:3000
echo   Default login: admin / admin123
echo ========================================
echo.
echo Press any key to stop the server...
pause

echo.
echo [INFO] Stopping server...
for /f "tokens=5" %%a in ('netstat -aon ^| find ":3000" ^| find "LISTENING"') do (
    echo Stopping process %%a...
    taskkill /f /pid %%a 2>nul
)
echo [SUCCESS] Server stopped.
pause