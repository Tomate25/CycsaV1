@echo off
title Tunel Cloudflare - Cycsa
chcp 65001 > nul

echo ===================================================
echo     INICIANDO TÚNEL DE CLOUDFLARE PARA CYCSA
echo ===================================================
echo.
echo Asegúrese de que XAMPP (Apache y MySQL) estén activos.
echo.
echo Iniciando túnel... 
echo Busque una línea en la pantalla que empiece con:
echo   https://xxxx.trycloudflare.com
echo.
echo Copie ese enlace y agréguele el subdirectorio:
echo   https://xxxx.trycloudflare.com/Cycsa/publico/login
echo ===================================================
echo.

where cloudflared >nul 2>nul
if %errorlevel% equ 0 (
    cloudflared tunnel --url http://localhost
) else (
    "C:\Users\abdia\AppData\Local\Microsoft\WinGet\Packages\Cloudflare.cloudflared_Microsoft.Winget.Source_8wekyb3d8bbwe\cloudflared.exe" tunnel --url http://localhost
)
pause
