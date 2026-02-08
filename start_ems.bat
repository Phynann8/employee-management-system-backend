@echo off
title EMS Server & Scheduler
cd /d "%~dp0"

echo ------------------------------------------------
echo [1/2] Starting Background Scheduler...
start "" "run_scheduler_silent.vbs"
echo Scheduler is running silently.

echo ------------------------------------------------
echo [2/2] Starting Laravel Server...
echo backend running at http://127.0.0.1:8000
echo ------------------------------------------------
php artisan serve
