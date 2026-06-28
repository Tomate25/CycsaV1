@echo off
title Compartir Proyecto Cycsa
chcp 65001 > nul

:: Obtener IP Pública para Localtunnel
echo Obteniendo IP pública para el túnel (espere un momento)...
for /f "delims=" %%i in ('powershell -Command "Invoke-RestMethod -Uri https://ipinfo.io/ip -ErrorAction SilentlyContinue"') do set public_ip=%%i

:menu
cls
echo ===================================================
echo     COMPARTIR PROYECTO CYCSA EN INTERNET
echo ===================================================
echo.
echo Asegúrese de que XAMPP (Apache y MySQL) estén activos.
echo.
if not "%public_ip%"=="" (
    echo [IP Pública de su PC]: %public_ip%
    echo (Use esta IP si el túnel le pide un "Tunnel Password" o "Endpoint IP")
    echo.
)
echo Seleccione una opción para exponer su servidor local:
echo.
echo [1] Usar Localtunnel (Recomendado - genera enlace .loca.lt)
echo [2] Usar Cloudflare Tunnel (Gratis, sin registro ni contraseñas)
echo [3] Usar SSH Tunnel (Pinggy - Sin instalar nada, rápido)
echo [4] Usar Ngrok (Requiere cuenta gratuita y authtoken)
echo [5] Salir
echo.
set /p opcion="Elija una opción (1-5): "

if "%opcion%"=="1" goto localtunnel
if "%opcion%"=="2" goto cloudflare
if "%opcion%"=="3" goto ssh_pinggy
if "%opcion%"=="4" goto ngrok_tunnel
if "%opcion%"=="5" exit
goto menu

:localtunnel
echo.
echo Iniciando Localtunnel en el puerto 80...
echo.
echo =========================================================================
echo IMPORTANTE:
echo 1. Busque una línea que diga "your url is: https://xxxx.loca.lt"
echo 2. Añada el subdirectorio del proyecto al compartir el enlace:
echo    https://xxxx.loca.lt/Cycsa/publico/
echo 3. Si le pide una contraseña/IP de acceso en el navegador, use:
echo    %public_ip%
echo =========================================================================
echo.
npx localtunnel --port 80
pause
goto menu

:cloudflare
echo.
echo Buscando 'cloudflared'...
where cloudflared >nul 2>nul
if %errorlevel% neq 0 (
    echo [INFO] No se encontró 'cloudflared'. Intentando instalar con winget automáticamente...
    winget install Cloudflare.cloudflared --silent
    if %errorlevel% neq 0 (
        echo [ERROR] No se pudo instalar automáticamente. Por favor instálelo manualmente o use la Opción 1 o 3.
        pause
        goto menu
    )
    echo [ÉXITO] Instalado correctamente. Iniciando túnel...
)
echo.
echo Iniciando Cloudflare Tunnel...
echo.
echo =========================================================================
echo IMPORTANTE: Busque una línea que empiece con:
echo   https://xxxx.trycloudflare.com
echo Ese es el enlace público que debe compartir con sus clientes y desarrolladores.
echo Ejemplo de enlace completo para acceder:
echo   https://xxxx.trycloudflare.com/Cycsa/publico/
echo =========================================================================
echo.
cloudflared tunnel --url http://localhost
pause
goto menu

:ssh_pinggy
echo.
echo Iniciando túnel SSH con Pinggy (sin instalar nada)...
echo.
echo =========================================================================
echo IMPORTANTE: Busque el enlace en pantalla que empieza con 'https://'
echo Ejemplo: https://xxxx.pinggy.link
echo Compártalo con el subdirectorio para abrir el proyecto:
echo   https://xxxx.pinggy.link/Cycsa/publico/
echo =========================================================================
echo.
ssh -R 80:localhost:80 loop.pinggy.io
pause
goto menu

:ngrok_tunnel
echo.
where ngrok >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] No se encontró 'ngrok' en el sistema.
    echo Instálelo con: winget install Ngrok.Ngrok
    pause
    goto menu
)
echo Asegúrese de haber configurado su token con: ngrok config add-authtoken TU_TOKEN
echo.
echo Iniciando Ngrok en el puerto 80...
echo.
echo =========================================================================
echo IMPORTANTE: Copie el enlace 'https://xxxx.ngrok-free.app' de la pantalla
echo y compártalo con el subdirectorio para abrir el proyecto:
echo   https://xxxx.ngrok-free.app/Cycsa/publico/
echo =========================================================================
echo.
ngrok http 80
pause
goto menu
