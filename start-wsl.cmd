@echo off
cd /d "%~dp0"
set "DETECTOR_PORT=8766"
powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "%~dp0scripts\open-browser.ps1" -Port 8766
if "%errorlevel%"=="10" exit /b 0
echo Starting DetectorGUI in Ubuntu. Your browser will open when the server is ready.
echo Address: http://127.0.0.1:8766
echo Keep this window open. Press Ctrl+C to stop the Linux service.
wsl.exe -d Ubuntu --cd "%~dp0." -- env DETECTOR_PORT=8766 bash ./scripts/start-linux.sh
if errorlevel 1 pause
