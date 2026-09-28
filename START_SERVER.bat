@echo off
title SIATRACK Launcher
cd /d "C:\Users\Ped\Desktop\CodeCommanders"

echo ==================================================
echo           Starting SIATRACK Server...             
echo ==================================================

:: Start Laravel Server
start "SIATRACK Server" cmd /k "title Laravel Server && php artisan serve"

:: Give Laravel a moment to start before the bridge posts card taps
timeout /t 3 /nobreak >nul

:: Start the ACR122U NFC bridge in a separate managed console
start "SIATRACK NFC Bridge" cmd /k "title SIATRACK NFC Bridge && where python >nul 2>&1 && python -u nfc_bridge.py || (where py >nul 2>&1 && py -3 -u nfc_bridge.py || (echo Python 3 was not found. && pause))"

:: Open the application
start http://127.0.0.1:8000

exit
