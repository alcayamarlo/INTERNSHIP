@echo off
REM Starts Skill-Bridge for local use in three windows:
REM   1. the web server  -> http://localhost:8000
REM   2. the queue worker -> sends notification emails and live (Pusher) updates outside
REM      the click, so pages stay fast. Without it, emails and live updates wait in the queue.
REM   3. the scheduler -> nightly backups, invitation expiry, MOA and deadline reminders.
REM Close all windows to stop. queue:listen picks up code changes without a restart.
cd /d "%~dp0"
start "Skill-Bridge web server (http://localhost:8000)" cmd /k php artisan serve
start "Skill-Bridge queue worker" cmd /k php artisan queue:listen --tries=3 --timeout=330 --sleep=1
start "Skill-Bridge scheduler" cmd /k php artisan schedule:work
