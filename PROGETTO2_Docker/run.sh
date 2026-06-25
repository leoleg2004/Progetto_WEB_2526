#!/bin/bash
echo "========================================================"
echo "  Avvio compilazione Maven (Deploy Locale Automatico)"
echo "========================================================"

echo "🔍 Fase 0: Controllo requisiti di sistema..."

# Controllo Java
if ! command -v java &> /dev/null; then
    echo "⚠️ Java non trovato. Tento l'installazione automatica..."
    if command -v brew &> /dev/null; then
        brew install openjdk@21
    elif command -v apt-get &> /dev/null; then
        sudo apt-get update && sudo apt-get install -y default-jdk
    else
        echo "❌ Impossibile installare Java in automatico. Procedo comunque."
    fi
else
    echo "✅ Java è installato."
fi

# Controllo MySQL
if ! command -v mysql &> /dev/null; then
    echo "⚠️ MySQL non trovato. Tento l'installazione automatica..."
    if command -v brew &> /dev/null; then
        brew install mysql
        brew services start mysql
    elif command -v apt-get &> /dev/null; then
        sudo apt-get update && sudo apt-get install -y mysql-server
    else
        echo "❌ Impossibile installare MySQL in automatico. Procedo comunque."
    fi
else
    echo "✅ MySQL è installato."
fi

# --- 1. CONFIGURAZIONE DATABASE ---
echo ""
echo "🗄️  Fase 1: Configurazione Database MySQL"

# Credenziali di default del progetto
DB_USER="root"
DB_PASS="leonardo"

echo "⏳ Creazione del database 'Progetto_WEB' se non esiste..."
# Prova prima con la password 'leonardo', se fallisce prova senza password
mysql -u $DB_USER -p$DB_PASS -e "CREATE DATABASE IF NOT EXISTS Progetto_WEB;" 2>/dev/null || mysql -u $DB_USER -e "CREATE DATABASE IF NOT EXISTS Progetto_WEB;" 2>/dev/null

if [ $? -ne 0 ]; then
    echo "❌ Errore durante la connessione a MySQL. Impossibile creare il database."
    echo "👉 Assicurati che MySQL sia in esecuzione e che la password di root sia 'leonardo' (o vuota)."
    exit 1
fi

echo "⏳ Importazione dei dati da db_init/init.sql..."
mysql -u $DB_USER -p$DB_PASS Progetto_WEB < db_init/init.sql 2>/dev/null || mysql -u $DB_USER Progetto_WEB < db_init/init.sql 2>/dev/null
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

if [ -z "$TOMCAT_WEBAPPS" ] || [ ! -d "$TOMCAT_WEBAPPS" ]; then
    echo "⚠️ Tomcat non trovato nel sistema. Scaricamento versione locale in corso..."
    if [ ! -d "apache-tomcat-9.0.87" ]; then
        curl -O https://archive.apache.org/dist/tomcat/tomcat-9/v9.0.87/bin/apache-tomcat-9.0.87.tar.gz
        tar -xzf apache-tomcat-9.0.87.tar.gz
        rm apache-tomcat-9.0.87.tar.gz
    fi
    TOMCAT_HOME="$(pwd)/apache-tomcat-9.0.87"
    TOMCAT_WEBAPPS="$TOMCAT_HOME/webapps"
    TOMCAT_BIN="$TOMCAT_HOME/bin"
    chmod +x "$TOMCAT_BIN"/*.sh
    echo "✅ Tomcat installato localmente."
fi

if [ -n "$TOMCAT_WEBAPPS" ] && [ -d "$TOMCAT_WEBAPPS" ]; then
    echo "Copia del file .war in $TOMCAT_WEBAPPS in corso..."
    cp target/progetto-web.war "$TOMCAT_WEBAPPS/"
    
    if [ -d "$TOMCAT_BIN" ] && [ -x "$TOMCAT_BIN/startup.sh" ]; then
        echo "Avvio di Tomcat in corso..."
        "$TOMCAT_BIN/startup.sh"
    fi
    
    echo "🎉 Deploy completato!"
else
    echo "❌ Errore critico nel setup di Tomcat."
fi

echo ""
echo "🌐 Sito disponibile a: http://localhost:8080/progetto-web"
