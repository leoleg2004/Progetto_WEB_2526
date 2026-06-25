@echo off
setlocal
echo ========================================================
echo   Avvio compilazione Maven (Deploy Locale Automatico)
echo ========================================================

:: --- 1. CONFIGURAZIONE DATABASE ---
echo.
echo [1/3] Configurazione Database MySQL

set DB_USER=root
set DB_PASS=leonardo

echo Creazione del database 'Progetto_WEB' se non esiste...
:: Prova con password, se fallisce prova senza
mysql -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS Progetto_WEB;" >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    mysql -u %DB_USER% -e "CREATE DATABASE IF NOT EXISTS Progetto_WEB;" >nul 2>&1
)

IF %ERRORLEVEL% NEQ 0 (
    echo [ERRORE] Impossibile connettersi a MySQL. Assicurati che sia attivo e che la password di root sia 'leonardo' o assente.
    pause
    exit /b 1
)

echo Importazione dei dati da db_init\init.sql...
mysql -u %DB_USER% -p%DB_PASS% Progetto_WEB < db_init\init.sql >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    mysql -u %DB_USER% Progetto_WEB < db_init\init.sql >nul 2>&1
)
echo [OK] Database configurato!

:: --- 2. COMPILAZIONE ---
echo.
echo [2/3] Compilazione del progetto Java...
call mvnw.cmd clean package

IF %ERRORLEVEL% NEQ 0 (
    echo [ERRORE] Errore durante la compilazione. Il deploy e' stato annullato.
    pause
    exit /b 1
)
echo [OK] Compilazione completata con successo!

:: --- 3. DEPLOY E AVVIO ---
echo.
echo [3/3] Deploy su Tomcat
IF DEFINED TOMCAT_HOME (
    echo Copia del file .war nella cartella webapps di Tomcat...
    copy /Y target\progetto-web.war "%TOMCAT_HOME%\webapps\"
    
    IF EXIST "%TOMCAT_HOME%\bin\startup.bat" (
        echo Avvio di Tomcat in corso...
        call "%TOMCAT_HOME%\bin\startup.bat"
    ) ELSE (
        echo [AVVISO] Non trovo startup.bat in %TOMCAT_HOME%\bin. Avvia Tomcat manualmente.
    )
    echo [OK] Deploy completato!
) ELSE (
    echo [AVVISO] Variabile TOMCAT_HOME non definita.
    echo Copia manualmente target\progetto-web.war nella cartella webapps di Tomcat.
)

echo.
echo Vai a: http://localhost:8080/progetto-web
pause
