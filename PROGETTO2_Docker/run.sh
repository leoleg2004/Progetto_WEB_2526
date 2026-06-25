#!/bin/bash
echo "========================================================"
echo "  Avvio compilazione Maven (Deploy Locale Automatico)"
echo "========================================================"

# --- 1. CONFIGURAZIONE DATABASE ---
echo "🗄️  Fase 1: Configurazione Database MySQL"

# Credenziali di default del progetto
DB_USER="root"
DB_PASS="leonardo"

echo "⏳ Creazione del database 'Progetto_WEB' se non esiste..."
# Prova prima con la password 'leonardo', se fallisce prova senza password
mysql -u $DB_USER -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS Progetto_WEB;" 2>/dev/null || mysql -u $DB_USER -e "CREATE DATABASE IF NOT EXISTS Progetto_WEB;"

if [ $? -ne 0 ]; then
    echo "❌ Errore durante la connessione a MySQL. Impossibile creare il database."
    echo "👉 Assicurati che MySQL sia in esecuzione e che la password di root sia 'leonardo' (o senza password)."
    exit 1
fi

echo "⏳ Importazione dei dati da db_init/init.sql..."
mysql -u $DB_USER -p$DB_PASS Progetto_WEB < db_init/init.sql 2>/dev/null || mysql -u $DB_USER Progetto_WEB < db_init/init.sql
echo "✅ Database configurato con successo!"

# --- 2. COMPILAZIONE ---
echo ""
echo "🚀 Fase 2: Compilazione del progetto Java..."
./mvnw clean package

if [ $? -ne 0 ]; then
    echo "❌ Errore durante la compilazione. Il deploy è stato annullato."
    exit 1
fi
echo "✅ Compilazione completata con successo!"

# --- 3. DEPLOY SU TOMCAT E AVVIO ---
echo ""
echo "📦 Fase 3: Deploy su Tomcat"
TOMCAT_WEBAPPS=""
TOMCAT_BIN=""

if [ -n "$TOMCAT_HOME" ]; then
    TOMCAT_WEBAPPS="$TOMCAT_HOME/webapps"
    TOMCAT_BIN="$TOMCAT_HOME/bin"
elif [ -d "/opt/homebrew/opt/tomcat@9/libexec/webapps" ]; then
    TOMCAT_WEBAPPS="/opt/homebrew/opt/tomcat@9/libexec/webapps"
    TOMCAT_BIN="/opt/homebrew/opt/tomcat@9/libexec/bin"
elif [ -d "/usr/local/tomcat/webapps" ]; then
    TOMCAT_WEBAPPS="/usr/local/tomcat/webapps"
    TOMCAT_BIN="/usr/local/tomcat/bin"
fi

if [ -n "$TOMCAT_WEBAPPS" ] && [ -d "$TOMCAT_WEBAPPS" ]; then
    echo "Copia del file .war in $TOMCAT_WEBAPPS in corso..."
    cp target/progetto-web.war "$TOMCAT_WEBAPPS/"
    
    if [ -d "$TOMCAT_BIN" ] && [ -x "$TOMCAT_BIN/startup.sh" ]; then
        echo "Avvio di Tomcat in corso..."
        "$TOMCAT_BIN/startup.sh"
    else
        echo "⚠️ Impossibile avviare Tomcat automaticamente. Assicurati di avviarlo manualmente."
    fi
    
    echo "🎉 Deploy completato!"
else
    echo "⚠️ Cartella webapps di Tomcat non trovata automaticamente."
    echo "👉 Copia manualmente il file target/progetto-web.war nella cartella webapps del tuo Tomcat."
fi

echo ""
echo "🌐 Sito disponibile a: http://localhost:8080/progetto-web"
