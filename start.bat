@echo off
chcp 65001 >nul
rem Ohrid Summer - start on Windows with a double click (needs XAMPP in C:\xampp).
cd /d "%~dp0"
set PHP=C:\xampp\php\php.exe
if not exist "%PHP%" (
  echo XAMPP not found in C:\xampp. Install XAMPP from https://www.apachefriends.org
  pause
  exit /b 1
)
netstat -an | find ":3306 " | find "LISTENING" >nul
if errorlevel 1 (
  echo Starting MySQL...
  start "" /min C:\xampp\mysql_start.bat
  timeout /t 6 >nul
)
"%PHP%" tools\update-db.php
if errorlevel 1 ( pause & exit /b 1 )
echo.
echo   Ohrid Summer is running at http://localhost:8000/
echo   Admin: admin@ohridsummer.test / Admin123!
echo   Close this window to stop.
echo.
start "" http://localhost:8000/
"%PHP%" -S localhost:8000 router.php
