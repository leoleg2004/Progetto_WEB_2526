@echo off
chcp 65001 >nul
:: Imposta qui il percorso in cui è installato Tomcat sul tuo computer Windows
:: (Attenzione: rimuovi l'ultima barra rovesciata \ dal percorso)
set "TOMCAT_DIR=C:\Program Files\Apache Software Foundation\Tomcat 9.0"

echo 🚀 Avvio compilazione Maven...
call mvn clean package

IF %ERRORLEVEL% EQU 0 (
    echo ✅ Compilazione completata con successo!
    echo 📦 Copia del file .war su Tomcat in corso...
    
    :: Se la cartella Tomcat non esiste, avvisa l'utente
    IF NOT EXIST "%TOMCAT_DIR%\webapps\" (
        echo ❌ ERRORE: La cartella di Tomcat non e' stata trovata in "%TOMCAT_DIR%". 
        echo Per favore apri il file deploy.bat con blocco note e modifica la variabile TOMCAT_DIR con il percorso corretto.
        goto fine
    )
    
    copy "target\progetto-web.war" "%TOMCAT_DIR%\webapps\" /Y >nul
    
    :: Controllo se c'è un processo in ascolto sulla porta 8080 (Tomcat)
    netstat -ano | find "8080" | find "LISTEN" >nul
    IF %ERRORLEVEL% NEQ 0 (
        echo ⚙️  Tomcat sembra spento. Lo sto accendendo in automatico...
        start "" "%TOMCAT_DIR%\bin\startup.bat"
    )
    
    echo 🎉 Deploy completato! Tomcat sta ricaricando il progetto in background.
    echo 🌐 Vai a: http://localhost:8080/progetto-web
    echo 🛑 Per spegnere il server usa: %TOMCAT_DIR%\bin\shutdown.bat
) ELSE (
    echo ❌ Errore durante la compilazione. Il deploy e' stato annullato.
)

:fine
pause
