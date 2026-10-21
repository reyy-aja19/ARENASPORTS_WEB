@echo off
setlocal
title ArenaSports - Setup MySQL
cd /d "%~dp0"
echo ==========================================
echo   ArenaSports - Persiapan Laravel + MySQL
echo ==========================================
where php >nul 2>nul
if errorlevel 1 (
  echo [ERROR] PHP tidak ditemukan. Tambahkan folder PHP XAMPP ke PATH atau jalankan dari Shell XAMPP.
  pause
  exit /b 1
)
where composer >nul 2>nul
if errorlevel 1 (
  echo [ERROR] Composer tidak ditemukan. Instal Composer dan pastikan terdeteksi di PATH.
  pause
  exit /b 1
)
if not exist ".env" (
  copy ".env.example" ".env" >nul
  echo File .env dibuat dari .env.example.
)
echo.
echo Pastikan database arena_sports sudah dibuat di phpMyAdmin dan MySQL aktif.
echo Konfigurasi database dapat diubah melalui file .env.
echo.
composer install
if errorlevel 1 goto failed
php artisan key:generate
if errorlevel 1 goto failed
php artisan config:clear
if errorlevel 1 goto failed
php artisan migrate --seed
if errorlevel 1 goto failed
echo.
echo Selesai. Jalankan MULAI_ARENASPORTS.bat atau ketik php artisan serve.
pause
exit /b 0
:failed
echo.
echo [ERROR] Proses berhenti. Baca pesan error di atas dan cek PANDUAN_MYSQL_WINDOWS.md.
pause
exit /b 1
