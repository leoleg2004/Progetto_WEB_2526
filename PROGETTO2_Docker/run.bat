@echo off
setlocal
echo ========================================================
echo   Avvio compilazione Maven (Deploy Locale Automatico)
echo ========================================================

:: Spostati nella cartella dello script
cd /d "%~dp0"

:: --- 0. CONTROLLO REQUISITI ---
echo.
echo [0/4] Controllo requisiti di sistema...

java -version >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo [AVVISO] Java non trovato! Tento l'installazione con winget...
    winget install --id Oracle.JavaRuntimeEnvironment --accept-source-agreements --accept-package-agreements >nul 2>&1
) ELSE (
    echo [OK] Java e' installato.
)

mysql --version >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo [AVVISO] MySQL non trovato! Tento l'installazione con winget...
    winget install --id Oracle.MySQL --accept-source-agreements --accept-package-agreements >nul 2>&1
) ELSE (
    echo [OK] MySQL e' installato.
)

:: --- 1. CONFIGURAZIONE DATABASE ---
echo.
echo [1/4] Configurazione Database MySQL

set DB_USER=root
set DB_PASS=leonardo

echo Verifica credenziali MySQL in corso...
mysql -u %DB_USER% -p%DB_PASS% -e "SELECT 1;" >nul 2>&1
IF %ERRORLEVEL% EQU 0 (
    set MYSQL_CMD=mysql -u %DB_USER% -p%DB_PASS%
) ELSE (
    mysql -u %DB_USER% -e "SELECT 1;" >nul 2>&1
    IF %ERRORLEVEL% EQU 0 (
        set MYSQL_CMD=mysql -u %DB_USER%
    ) ELSE (
        echo [ERRORE] Impossibile connettersi a MySQL con utente root. (Password diversa da 'leonardo' o vuota).
        pause
        exit /b 1
    )
)

echo Creazione del database 'Progetto_WEB' se non esiste...
%MYSQL_CMD% -e "CREATE DATABASE IF NOT EXISTS Progetto_WEB;"

IF %ERRORLEVEL% NEQ 0 (
    echo [ERRORE] Impossibile creare il database.
    pause
    exit /b 1
)

echo Importazione dei dati da db_init\init.sql...
%MYSQL_CMD% Progetto_WEB < db_init\init.sql
IF %ERRORLEVEL% EQU 0 (
    echo [OK] Database configurato!
) ELSE (
    echo [ERRORE] Importazione del database fallita.
    pause
    exit /b 1
)

:: --- 2. COMPILAZIONE ---
echo.
echo [2/4] Compilazione del progetto Java...
call mvnw.cmd clean package

IF %ERRORLEVEL% NEQ 0 (
    echo [ERRORE] Errore durante la compilazione. Il deploy e' stato annullato.
    pause
    exit /b 1
)
echo [OK] Compilazione completata con successo!

:: --- 3. DEPLOY E AVVIO ---
echo.
echo [3/4] Deploy su Tomcat

IF NOT DEFINED TOMCAT_HOME (
    IF EXIST "apache-tomcat-9.0.87" (
        set TOMCAT_HOME=%CD%\apache-tomcat-9.0.87
    ) ELSE (
        echo [AVVISO] Variabile TOMCAT_HOME non definita. Scaricamento Tomcat locale in corso...
        powershell -Command "Invoke-WebRequest -Uri 'https://archive.apache.org/dist/tomcat/tomcat-9/v9.0.87/bin/apache-tomcat-9.0.87-windows-x64.zip' -OutFile 'tomcat.zip'"
        powershell -Command "Expand-Archive -Path 'tomcat.zip' -DestinationPath '.'"
        del tomcat.zip
        set TOMCAT_HOME=%CD%\apache-tomcat-9.0.87
    )
)

IF DEFINED TOMCAT_HOME (
    echo Copia del file .war nella cartella webapps di Tomcat...
    copy /Y target\progetto-web.war "%TOMCAT_HOME%\webapps\" >nul
    
    IF EXIST "%TOMCAT_HOME%\bin\startup.bat" (
        echo Avvio di Tomcat in corso...
        call "%TOMCAT_HOME%\bin\startup.bat"
    ) ELSE (
        echo [AVVISO] Non trovo startup.bat in %TOMCAT_HOME%\bin. Avvia Tomcat manualmente.
    )
    echo [OK] Deploy completato!
) ELSE (
    echo [AVVISO] Errore critico nel setup di Tomcat.
)

echo.
echo Vai a: http://localhost:8080/progetto-web
pause
