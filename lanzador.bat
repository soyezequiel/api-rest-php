@echo off
setlocal EnableExtensions EnableDelayedExpansion

cd /d "%~dp0"
title API REST PHP - Lanzador

if /i "%~1"=="abrir" goto abrir
if /i "%~1"=="iniciar" goto abrir
if /i "%~1"=="cerrar" goto cerrar
if /i "%~1"=="detener" goto cerrar
if /i "%~1"=="estado" goto estado
if /i "%~1"=="logs" goto logs
if /i "%~1"=="reiniciar" goto reiniciar
if /i "%~1"=="limpiar-db" goto limpiar_db
if /i "%~1"=="limpiar" goto limpiar_db

:menu
cls
echo ========================================
echo        API REST PHP - Lanzador
echo ========================================
echo.
echo  1. Abrir proyecto
echo  2. Cerrar proyecto
echo  3. Ver estado
echo  4. Ver logs
echo  5. Reiniciar proyecto
echo  6. Limpiar base de datos
echo  0. Salir
echo.
set /p "opcion=Elegi una opcion: "

if "%opcion%"=="1" goto abrir
if "%opcion%"=="2" goto cerrar
if "%opcion%"=="3" goto estado
if "%opcion%"=="4" goto logs
if "%opcion%"=="5" goto reiniciar
if "%opcion%"=="6" goto limpiar_db
if "%opcion%"=="0" exit /b 0
goto menu

:abrir
call :preparar
if errorlevel 1 goto volver

echo.
echo Iniciando servicios...
%DOCKER_COMPOSE% up -d
if errorlevel 1 goto error_comando

echo.
echo Abriendo navegador...
start "" "http://localhost:%SLIM_PORT%"
start "" "http://localhost:%DBADMIN_PORT%"

echo.
echo Proyecto iniciado.
echo API:        http://localhost:%SLIM_PORT%
echo phpMyAdmin: http://localhost:%DBADMIN_PORT%
goto volver

:cerrar
call :preparar_sin_volume
if errorlevel 1 goto volver

echo.
echo Deteniendo servicios...
%DOCKER_COMPOSE% down
if errorlevel 1 goto error_comando

echo.
echo Proyecto detenido. La base de datos se conserva en el volumen "%DB_VOLUME%".
goto volver

:estado
call :preparar_sin_volume
if errorlevel 1 goto volver

echo.
%DOCKER_COMPOSE% ps
goto volver

:logs
call :preparar_sin_volume
if errorlevel 1 goto volver

echo.
echo Mostrando logs. Presiona Ctrl+C para volver.
%DOCKER_COMPOSE% logs -f
goto volver

:reiniciar
call :preparar
if errorlevel 1 goto volver

echo.
echo Reiniciando servicios...
%DOCKER_COMPOSE% down
if errorlevel 1 goto error_comando
%DOCKER_COMPOSE% up -d
if errorlevel 1 goto error_comando

echo.
echo Proyecto reiniciado.
goto volver

:limpiar_db
call :preparar_sin_volume
if errorlevel 1 goto volver

echo.
echo Esta accion detiene los servicios y borra permanentemente el volumen "%DB_VOLUME%".
set /p "confirmar=Escribi BORRAR para continuar: "
if /i not "%confirmar%"=="BORRAR" (
  echo Operacion cancelada.
  goto volver
)

%DOCKER_COMPOSE% down
docker volume rm "%DB_VOLUME%"
if errorlevel 1 goto error_comando

echo.
echo Base de datos eliminada.
goto volver

:preparar
call :preparar_sin_volume auto
if errorlevel 1 exit /b 1

docker volume inspect "%DB_VOLUME%" >nul 2>&1
if errorlevel 1 (
  echo Creando volumen de base de datos "%DB_VOLUME%"...
  docker volume create "%DB_VOLUME%" >nul
  if errorlevel 1 (
    echo No se pudo crear el volumen "%DB_VOLUME%".
    exit /b 1
  )
)

exit /b 0

:preparar_sin_volume
call :asegurar_env
if errorlevel 1 exit /b 1
call :cargar_env
call :detectar_compose
if errorlevel 1 exit /b 1
call :asegurar_docker %~1
if errorlevel 1 exit /b 1
exit /b 0

:asegurar_env
if exist ".env" exit /b 0

if not exist ".env.dist" (
  echo No existe .env ni .env.dist.
  exit /b 1
)

copy ".env.dist" ".env" >nul
echo Se creo .env desde .env.dist. Revisalo si necesitas cambiar puertos o credenciales.
exit /b 0

:cargar_env
for /f "usebackq tokens=1,* delims==" %%A in (".env") do (
  set "clave=%%A"
  if not "!clave!"=="" if not "!clave:~0,1!"=="#" set "%%A=%%B"
)

if "%DB_VOLUME%"=="" set "DB_VOLUME=seminariophp"
if "%SLIM_PORT%"=="" set "SLIM_PORT=80"
if "%DBADMIN_PORT%"=="" set "DBADMIN_PORT=8080"
exit /b 0

:detectar_compose
docker compose version >nul 2>&1
if not errorlevel 1 (
  set "DOCKER_COMPOSE=docker compose"
  exit /b 0
)

docker-compose version >nul 2>&1
if not errorlevel 1 (
  set "DOCKER_COMPOSE=docker-compose"
  exit /b 0
)

echo No se encontro Docker Compose. Instala Docker Desktop o agrega docker-compose al PATH.
exit /b 1

:asegurar_docker
docker info >nul 2>&1
if not errorlevel 1 exit /b 0

echo Docker no esta respondiendo.
if /i "%~1"=="auto" if exist "%ProgramFiles%\Docker\Docker\Docker Desktop.exe" (
  echo Intentando abrir Docker Desktop...
  start "" "%ProgramFiles%\Docker\Docker\Docker Desktop.exe"
)
if /i "%~1"=="auto" (
  echo Espera a que Docker termine de iniciar y volve a elegir la opcion.
) else (
  echo Si Docker ya estaba cerrado, no hay nada mas para detener.
)
exit /b 1

:error_comando
echo.
echo El comando fallo. Revisa el mensaje anterior.
goto volver

:volver
if not "%~1"=="" exit /b 0
echo.
pause
goto menu
