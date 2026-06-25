@echo off

:: ============================================================
::  AUTO-ELEVAZIONE AMMINISTRATORE (via VBScript)
:: ============================================================
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [INFO] Richiesta elevazione privilegi ^(popup UAC^)...
    echo Set UAC = CreateObject^("Shell.Application"^) > "%temp%\elevate_stop.vbs"
    echo UAC.ShellExecute "%~s0", "", "%~dp0", "runas", 1 >> "%temp%\elevate_stop.vbs"
    "%temp%\elevate_stop.vbs"
    del "%temp%\elevate_stop.vbs" >nul 2>&1
    exit /b
)

cd /d "%~dp0"
echo ========================================================
echo   Arresto del server Tomcat (Deploy Locale)
echo ========================================================

:: Chiude la finestra "Tomcat 9" aperta da run.bat
echo [INFO] Chiusura finestra Tomcat in corso...
taskkill /FI "WINDOWTITLE eq Tomcat 9" /F >nul 2>&1

:: Termina tutti i processi java (Tomcat)
taskkill /F /IM "java.exe" >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Tomcat arrestato.
) else (
    echo [INFO] Nessun processo Java trovato ^(Tomcat gia' spento^).
)

:: Ferma il servizio MySQL
echo [INFO] Arresto servizio MySQL in corso...
net stop MySQL84 >nul 2>&1
if %errorlevel% neq 0 net stop MySQL80 >nul 2>&1
if %errorlevel% neq 0 net stop MySQL >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] MySQL arrestato.
) else (
    echo [INFO] MySQL gia' spento o non trovato.
)

echo.
echo ========================================================
echo   Tutti i servizi arrestati.
echo ========================================================
pause
