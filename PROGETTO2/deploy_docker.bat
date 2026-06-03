@echo off
echo ========================================================
echo   Avvio deploy del progetto in ambiente virtuale (Docker)
echo ========================================================

:: Controllo se Docker è installato
docker --version >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo [ERRORE] Docker non e' installato su questa macchina.
    echo Scarica e installa Docker Desktop da https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)

:: Controllo se Docker e' in esecuzione, altrimenti lo avvio
docker info >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo [INFO] Docker Desktop non e' in esecuzione. Avvio in corso...
    start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    echo [INFO] Attesa che Docker sia pronto (potrebbe richiedere un minuto)...
:wait_docker
    timeout /t 3 /nobreak >nul
    docker info >nul 2>&1
    IF %ERRORLEVEL% NEQ 0 GOTO wait_docker
    echo [OK] Docker e' ora attivo!
)

echo.
echo [INFO] Costruzione dell'immagine e avvio dei container (Database e Server Web)...
docker-compose up --build -d

IF %ERRORLEVEL% EQU 0 (
    echo.
    echo [OK] Deploy completato con successo!
    echo [INFO] Attendi qualche secondo affinche' MySQL e Tomcat si avviino completamente.
    echo [INFO] Il progetto sara' disponibile all'indirizzo: http://localhost:8080/progetto-web
    echo [INFO] Per spegnere l'ambiente esegui: docker-compose down
) ELSE (
    echo.
    echo [ERRORE] Si e' verificato un problema durante la creazione dei container. Deploy annullato.
)

pause
