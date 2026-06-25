@echo off
cd /d "%~dp0"
echo ========================================================
echo   Arresto del server Tomcat (Deploy Locale)
echo ========================================================
echo [INFO] Arresto di Tomcat in corso...

set TOMCAT_BIN=
IF DEFINED TOMCAT_HOME (
    set TOMCAT_BIN=%TOMCAT_HOME%\bin
) ELSE IF EXIST "C:\Program Files\Apache Software Foundation\Tomcat 9.0\bin" (
    set TOMCAT_BIN=C:\Program Files\Apache Software Foundation\Tomcat 9.0\bin
) ELSE IF EXIST "apache-tomcat-9.0.87\bin" (
    set TOMCAT_BIN=%~dp0apache-tomcat-9.0.87\bin
)

IF DEFINED TOMCAT_BIN (
    IF EXIST "%TOMCAT_BIN%\shutdown.bat" (
        call "%TOMCAT_BIN%\shutdown.bat"
        echo [OK] Tomcat arrestato con successo (Tramite script ufficiale)!
        pause
        exit /b 0
    )
)

echo [AVVISO] Impossibile trovare shutdown.bat. 
echo [INFO] Termino i processi Java associati a Tomcat in modo forzato...
taskkill /F /FI "WINDOWTITLE eq Tomcat*" >nul 2>&1
echo [OK] Tomcat arrestato (Terminazione forzata).
pause
