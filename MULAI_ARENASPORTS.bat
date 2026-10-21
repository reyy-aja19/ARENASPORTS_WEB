@echo off
setlocal
cd /d "%~dp0"
if not exist ".env" (
  echo File .env belum ada. Jalankan PERSIAPAN_ARENASPORTS_MYSQL.bat terlebih dahulu.
  pause
  exit /b 1
)
php artisan serve
pause
