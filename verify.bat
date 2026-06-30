@echo off
echo ================================================
echo   SKILL-BRIDGE SYSTEM - VERIFICATION SCRIPT
echo ================================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo [ERROR] Please run this script from the project root directory
    pause
    exit /b 1
)

echo [1/10] Checking PHP version...
php -v | findstr /C:"PHP 8"
if errorlevel 1 (
    echo [WARNING] PHP 8.2+ recommended
) else (
    echo [OK] PHP version is compatible
)
echo.

echo [2/10] Checking Composer installation...
composer --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Composer is not installed
) else (
    echo [OK] Composer is installed
)
echo.

echo [3/10] Checking Node.js installation...
node --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Node.js is not installed
) else (
    echo [OK] Node.js is installed
)
echo.

echo [4/10] Checking .env file...
if exist ".env" (
    echo [OK] .env file exists
) else (
    echo [WARNING] .env file not found - run: copy .env.example .env
)
echo.

echo [5/10] Checking database connection...
php artisan db:show >nul 2>&1
if errorlevel 1 (
    echo [WARNING] Database connection failed - check .env settings
) else (
    echo [OK] Database connection successful
)
echo.

echo [6/10] Checking required directories...
if exist "storage\app" (
    echo [OK] storage/app directory exists
) else (
    echo [ERROR] storage/app directory missing
)
if exist "storage\framework" (
    echo [OK] storage/framework directory exists
) else (
    echo [ERROR] storage/framework directory missing
)
if exist "bootstrap\cache" (
    echo [OK] bootstrap/cache directory exists
) else (
    echo [ERROR] bootstrap/cache directory missing
)
echo.

echo [7/10] Checking storage link...
if exist "public\storage" (
    echo [OK] Storage link exists
) else (
    echo [WARNING] Storage link not found - run: php artisan storage:link
)
echo.

echo [8/10] Checking migrations status...
php artisan migrate:status 2>nul | findstr /C:"users"
if errorlevel 1 (
    echo [WARNING] Database not migrated - run: php artisan migrate
) else (
    echo [OK] Database tables exist
)
echo.

echo [9/10] Checking vendor dependencies...
if exist "vendor\autoload.php" (
    echo [OK] Composer dependencies installed
) else (
    echo [ERROR] Vendor directory missing - run: composer install
)
echo.

echo [10/10] Checking node_modules...
if exist "node_modules" (
    echo [OK] NPM dependencies installed
) else (
    echo [WARNING] node_modules missing - run: npm install
)
echo.

echo ================================================
echo   VERIFICATION COMPLETE
echo ================================================
echo.

echo Checking for common issues...
echo.

REM Check APP_KEY
findstr /C:"APP_KEY=base64:" .env >nul 2>&1
if errorlevel 1 (
    echo [WARNING] APP_KEY not set - run: php artisan key:generate
) else (
    echo [OK] APP_KEY is set
)
echo.

REM Check database credentials
findstr /C:"DB_DATABASE=nexus" .env >nul 2>&1
if errorlevel 1 (
    echo [WARNING] DB_DATABASE may not be set to 'nexus'
) else (
    echo [OK] Database name is 'nexus'
)
echo.

echo ================================================
echo   QUICK START COMMANDS
echo ================================================
echo.
echo To start the application:
echo   1. Start XAMPP (Apache + MySQL)
echo   2. Run: php artisan serve
echo   3. Open: http://localhost:8000
echo.
echo To rebuild assets:
echo   npm run build
echo.
echo To clear all caches:
echo   php artisan optimize:clear
echo.
echo ================================================
pause
