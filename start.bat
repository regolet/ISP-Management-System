@echo off
echo Starting ISP Management System...
echo.

echo Killing existing processes on port 3000...
for /f "tokens=5" %%a in ('netstat -aon ^| find ":3000" ^| find "LISTENING"') do taskkill /f /pid %%a 2>nul
echo.

echo Starting server on port 3000...
start /b npm start

echo Waiting for server to start...
timeout /t 5 /nobreak >nul

echo Opening http://localhost:3000 in your browser...
start http://localhost:3000

echo.
echo Server is running. Press any key to stop...
pause