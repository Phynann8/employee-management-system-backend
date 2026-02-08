@echo off
title EMS Auto-Scheduler
cd /d "%~dp0"
echo Starting EMS Scheduler...
echo This window will run Biometric Sync every minute.
echo Do not close this window!
echo ------------------------------------------------
php artisan schedule:work
pause
