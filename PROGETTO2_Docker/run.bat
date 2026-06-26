@echo off
setlocal EnableDelayedExpansion

:: ============================================================
::  AUTO-ELEVAZIONE AMMINISTRATORE (via VBScript)
:: ============================================================
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [INFO] Richiesta elevazione privilegi ^(popup UAC^)...
    echo Set UAC = CreateObject^("Shell.Application"^) > "%temp%\elevate_run.vbs"
    echo UAC.ShellExecute "%~s0", "", "%~dp0", "runas", 1 >> "%temp%\elevate_run.vbs"
    "%temp%\elevate_run.vbs"
    del "%temp%\elevate_run.vbs" >nul 2>&1
    exit /b
)

echo ========================================================
echo   Avvio compilazione Maven (Deploy Locale Automatico)
echo ========================================================

:: Spostati nella cartella dello script
cd /d "%~dp0"

:: ============================================================
:: [0/4] CONTROLLO REQUISITI
:: ============================================================
echo.
echo [0/4] Controllo requisiti di sistema...

java -version >nul 2>&1
if %errorlevel% neq 0 (
    echo [AVVISO] Java non trovato. Tento installazione con winget...
    winget install --id Oracle.JavaRuntimeEnvironment --accept-source-agreements --accept-package-agreements >nul 2>&1
) else (
    echo [OK] Java e' installato.
)

:: Trova mysql.exe nel PATH o nei percorsi standard
set "MYSQL_EXE=mysql"
set "MYSQLD_EXE=mysqld"
mysql --version >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe" (
        set "MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe"
        set "MYSQLD_EXE=C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe"
        echo [OK] MySQL 8.4 trovato nel percorso standard.
    ) else if exist "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" (
        set "MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
        set "MYSQLD_EXE=C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe"
        echo [OK] MySQL 8.0 trovato nel percorso standard.
    ) else (
        echo [AVVISO] MySQL non trovato. Tento installazione con winget...
        winget install --id Oracle.MySQL --accept-source-agreements --accept-package-agreements >nul 2>&1
    )
) else (
    echo [OK] MySQL e' installato e nel PATH.
)

:: ============================================================
:: [1/4] CONFIGURAZIONE DATABASE
:: ============================================================
echo.
echo [1/4] Configurazione Database MySQL...

:: Inizializza la directory dati se non esiste (prima configurazione)
if not exist "C:\ProgramData\MySQL\MySQL Server 8.4\Data" (
    echo [INFO] Prima configurazione: inizializzo la directory dati MySQL...
    "%MYSQLD_EXE%" --initialize-insecure --console 2>&1 | findstr /i "error\|Warning\|ready" 
    echo [INFO] Inizializzazione completata.
)

:: Avvia il servizio MySQL (registralo se non esiste ancora)
sc.exe query MySQL84 >nul 2>&1
if %errorlevel% neq 0 (
    echo [INFO] Registro il servizio MySQL84...
    "%MYSQLD_EXE%" --install MySQL84 >nul 2>&1
)

:: Avvia il servizio
net start MySQL84 >nul 2>&1
if %errorlevel% neq 0 (
    :: Prova nomi alternativi
    net start MySQL80 >nul 2>&1
    if %errorlevel% neq 0 net start MySQL >nul 2>&1
)

echo [INFO] Attendo avvio MySQL...
timeout /t 6 /nobreak >nul

:: ---- Prova connessione: senza password, poi con "leonardo" ----
set "DB_USER=root"
set "DB_PASS="
set "MYSQL_CMD="

"%MYSQL_EXE%" -u %DB_USER% --connect-timeout=5 -e "SELECT 1;" >nul 2>&1
if %errorlevel% equ 0 (
    set "DB_PASS="
    goto :mysql_ok
)

"%MYSQL_EXE%" -u %DB_USER% -pleonardo --connect-timeout=5 -e "SELECT 1;" >nul 2>&1
if %errorlevel% equ 0 (
    set "DB_PASS=leonardo"
    goto :mysql_ok
)

echo [ERRORE] Impossibile connettersi a MySQL con root (senza password o con "leonardo").
echo Apri MySQL Workbench o mysql_configurator.exe per verificare le credenziali.
pause
exit /b 1

:mysql_ok
echo [OK] Connessione a MySQL riuscita!

:: Argomenti di connessione (separati dall'eseguibile per gestire spazi nel path)
set "MYSQL_ARGS=-u %DB_USER%"
if not "%DB_PASS%"=="" set "MYSQL_ARGS=%MYSQL_ARGS% -p%DB_PASS%"

echo Creazione database 'Progetto_WEB' se non esiste...
"%MYSQL_EXE%" %MYSQL_ARGS% -e "CREATE DATABASE IF NOT EXISTS Progetto_WEB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
if %errorlevel% neq 0 (
    echo [ERRORE] Impossibile creare il database.
    pause
    exit /b 1
)
:: Forza la collation corretta anche se il DB esisteva gia' con quella sbagliata
:: (risolve incompatibilita' FK tra MySQL 8.4 Windows e dump generato su macOS/MySQL 9.x)
"%MYSQL_EXE%" %MYSQL_ARGS% -e "ALTER DATABASE Progetto_WEB CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

echo Importazione dati da db_init\init.sql...
"%MYSQL_EXE%" %MYSQL_ARGS% --default-character-set=utf8mb4 Progetto_WEB < "db_init\init.sql"
if %errorlevel% equ 0 (
    echo [OK] Database configurato con successo!
) else (
    echo [ERRORE] Importazione del database fallita.
    pause
    exit /b 1
)

:: ============================================================
:: [2/4] COMPILAZIONE MAVEN
:: ============================================================
echo.
echo [2/4] Compilazione del progetto Java...
call mvnw.cmd clean package -q
if %errorlevel% neq 0 (
    echo [ERRORE] Errore durante la compilazione Maven.
    pause
    exit /b 1
)
echo [OK] Compilazione completata con successo!

:: ============================================================
:: [3/4] DEPLOY SU TOMCAT
:: ============================================================
echo.
echo [3/4] Deploy su Tomcat...

if not defined TOMCAT_HOME (
    if exist "apache-tomcat-9.0.87" (
        set "TOMCAT_HOME=%CD%\apache-tomcat-9.0.87"
    ) else (
        echo [AVVISO] TOMCAT_HOME non definita. Scaricamento Tomcat in corso...
        powershell -Command "Invoke-WebRequest -Uri 'https://archive.apache.org/dist/tomcat/tomcat-9/v9.0.87/bin/apache-tomcat-9.0.87-windows-x64.zip' -OutFile 'tomcat.zip'"
        powershell -Command "Expand-Archive -Path 'tomcat.zip' -DestinationPath '.'"
        del tomcat.zip
        set "TOMCAT_HOME=%CD%\apache-tomcat-9.0.87"
    )
)

if defined TOMCAT_HOME (
    echo Copia del file .war in Tomcat\webapps...
    copy /Y "target\progetto-web.war" "%TOMCAT_HOME%\webapps\" >nul
    if exist "%TOMCAT_HOME%\bin\catalina.bat" (
        echo Avvio Tomcat in corso...
        set "CATALINA_HOME=%TOMCAT_HOME%"
        set "CATALINA_BASE=%TOMCAT_HOME%"
        start "Tomcat 9" cmd /k ""%TOMCAT_HOME%\bin\catalina.bat" run"
        echo [OK] Tomcat avviato! Attendi qualche secondo poi apri il browser.
    ) else (
        echo [AVVISO] catalina.bat non trovato in %TOMCAT_HOME%\bin
    )
    echo [OK] Deploy completato!
) else (
    echo [AVVISO] Errore critico nel setup di Tomcat.
)

echo.
echo ========================================================
echo   Applicazione disponibile su: http://localhost:8080/progetto-web
echo ========================================================
pause
