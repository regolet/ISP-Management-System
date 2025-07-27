@echo off
echo ========================================
echo   ISP Management Server Status Check
echo ========================================
echo.

:: Check if port 3000 is listening
netstat -an | find ":3000" | find "LISTENING" >nul 2>&1
if not errorlevel 1 (
    echo [SUCCESS] Server is running on port 3000
    echo [INFO] Service URL: http://localhost:3000
    echo.
    
    :: Try to ping the health endpoint
    powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost:3000/api/health' -TimeoutSec 5; if ($response.StatusCode -eq 200) { Write-Host '[SUCCESS] Health check passed - Server responding' -ForegroundColor Green } else { Write-Host '[WARNING] Server running but health check failed' -ForegroundColor Yellow } } catch { Write-Host '[WARNING] Server running but not responding to requests' -ForegroundColor Yellow }"
    echo.
    
    choice /c YN /m "Open web interface in browser"
    if errorlevel 2 goto :end
    if errorlevel 1 (
        start http://localhost:3000
        echo [INFO] Opening browser...
    )
    
) else (
    echo [ERROR] Server is NOT running!
    echo.
    choice /c YN /m "Start the server now"
    if errorlevel 2 goto :end
    if errorlevel 1 (
        echo [INFO] Starting server...
        call restart-server.bat
    )
)

:end
echo.
pause