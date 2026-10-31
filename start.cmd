@echo off
cd /d "%~dp0"
set "DETECTOR_PORT=8765"
set "DETECTOR_PHP=php"
where php >nul 2>nul
if errorlevel 1 (
  set "DETECTOR_PHP=%~dp0..\Workplace\.tools\php-8.4\php.exe"
)
if not exist "%DETECTOR_PHP%" if not "%DETECTOR_PHP%"=="php" (
  echo PHP 8.2 or later is required. Install PHP or add it to PATH.
  pause
  exit /b 1
)
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "%~dp0scripts\open-browser.ps1" -Port 8765
if "%errorlevel%"=="10" exit /b 0
echo Starting DetectorGUI. Your browser will open when the server is ready.
echo Address: http://127.0.0.1:8765 - Keep this window open. Press Ctrl+C to stop.
"%DETECTOR_PHP%" -S 127.0.0.1:8765 -t public public/router.php
if errorlevel 1 pause
