@echo off
REM Menjalankan backend (Laravel :8000) dan frontend (Next.js :3000).
REM Apache (Laragon) mem-proxy semuanya ke http://mtsn1.test

echo Starting Laravel API on http://127.0.0.1:8000 ...
start "MTsN1 Backend" cmd /k "cd /d %~dp0backend && php artisan serve --host=127.0.0.1 --port=8000"

echo Starting Next.js on http://127.0.0.1:3000 ...
start "MTsN1 Frontend" cmd /k "cd /d %~dp0frontend && npm run dev"

echo.
echo Pastikan Apache Laragon menyala, lalu buka: http://mtsn1.test
echo Admin panel: http://mtsn1.test/admin  (admin@mtsn1.sch.id / password)
