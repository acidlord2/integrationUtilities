@echo off
echo ========================================
echo   Queue Service Runner
echo   Runs every 10 seconds
echo ========================================
echo.

REM Check if Docker is running
docker version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker is not running or not installed
    echo Please start Docker Desktop first
    pause
    exit /b 1
)

echo ✅ Docker is running
echo 🔄 Starting queue service loop...
echo 📋 Press Ctrl+C to stop
echo.

:loop
    echo [%date% %time%] Running queue service...
    
    REM Run the queue service in the Docker container
    docker-compose exec -T php-app php /var/www/html/queue-service/queue-service.php
    
    if %errorlevel% neq 0 (
        echo ❌ Queue service failed with error code %errorlevel%
    ) else (
        echo ✅ Queue service completed successfully
    )
    
    echo.
    echo ⏰ Waiting 10 seconds...
    timeout /t 10 /nobreak >nul
    
    REM Check if user wants to exit (this won't work with timeout, but kept for reference)
    REM You can press Ctrl+C to stop the loop
    
goto loop