@echo off
echo ================================================
echo   SKILL-BRIDGE SYSTEM - AUTOMATED SETUP
echo ================================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo ERROR: Please run this script from the project root directory
    pause
    exit /b 1
)

echo [Step 1/8] Installing PHP dependencies...
call composer install
if errorlevel 1 (
    echo ERROR: Composer install failed
    pause
    exit /b 1
)
echo.

echo [Step 2/8] Installing Node dependencies...
call npm install
if errorlevel 1 (
    echo ERROR: NPM install failed
    pause
    exit /b 1
)
echo.

echo [Step 3/8] Copying environment file...
if not exist ".env" (
    copy .env.example .env
    echo .env file created
) else (
    echo .env file already exists, skipping...
)
echo.

echo [Step 4/8] Generating application key...
php artisan key:generate
echo.

echo [Step 5/8] Running database migrations...
php artisan migrate --force
if errorlevel 1 (
    echo ERROR: Database migration failed
    echo Please ensure:
    echo  1. XAMPP MySQL is running
    echo  2. Database 'nexus' exists
    echo  3. .env file has correct database credentials
    pause
    exit /b 1
)
echo.

echo [Step 6/8] Seeding database with demo data...
php artisan db:seed --force
if errorlevel 1 (
    echo ERROR: Database seeding failed
    pause
    exit /b 1
)
echo.

echo [Step 7/8] Creating storage link...
php artisan storage:link
echo.

echo [Step 8/8] Building frontend assets...
call npm run build
echo.

echo ================================================
echo   SETUP COMPLETED SUCCESSFULLY!
echo ================================================
echo.
echo Default Login Credentials:
echo.
echo Administrator:
echo   Email: admin@skillbridge.test
echo   Password: password
echo.
echo Student:
echo   Email: student@skillbridge.test
echo   Password: password
echo.
echo Employer:
echo   Email: employer@skillbridge.test
echo   Password: password
echo.
echo Coordinator:
echo   Email: coordinator@skillbridge.test
echo   Password: password
echo.
echo ================================================
echo.
echo To start the application, run:
echo   php artisan serve
echo.
echo Then open your browser to:
echo   http://localhost:8000
echo.
echo ================================================
pause
