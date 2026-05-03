@echo off
REM Navigate to Backend and Start PHP Server
cd /d "backend"
start cmd /k "php artisan serve --port=8000"

REM Navigate to Frontend and Start Vite (in the correct folder)
cd /d "..\frontend/web"
start cmd /k "npm run dev"

REM Wait for 2 seconds
timeout /t 2

REM Minimize Windows
start "" wscript "%~dp0minimize_windows.vbs"

REM Pause to keep terminal open
pause
