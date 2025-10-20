@echo off
echo ========================================
echo  Integration Helper - Docker Startup
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
echo.

echo 🏗️  Building and starting containers...
docker-compose up -d

if %errorlevel% neq 0 (
    echo ❌ Failed to start containers
    pause
    exit /b 1
)

echo.
echo 🎉 Containers started successfully!
echo.
echo 🔗 Application URLs:
echo    Main App:     http://localhost:8080
echo    Queue Test:   http://localhost:8080/test-queue.php  
echo    phpMyAdmin:   http://localhost:8081
echo.
echo 📋 Login credentials for phpMyAdmin:
echo    Username: integration_user
echo    Password: integration_pass
echo    Database: integration_db
echo.
echo 📊 Container status:
docker-compose ps
echo.
echo Press any key to view application logs...
pause >nul

echo 📜 Application logs (press Ctrl+C to exit):
docker-compose logs -f php-app