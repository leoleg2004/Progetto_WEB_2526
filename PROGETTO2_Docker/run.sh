#!/bin/bash
echo "========================================================"
echo "  Avvio compilazione Maven (Deploy Locale Automatico)"
echo "========================================================"

# Spostati nella cartella dello script (utile se lanciato con doppio clic)
cd "$(dirname "$0")"

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

echo "⏳ Verifica stato MySQL in corso..."
if mysql -u $DB_USER -e "SELECT 1;" &> /dev/null; then
    MYSQL_CMD="mysql -u $DB_USER"
else
    echo "⚠️ MySQL non risponde. Tento di avviare il servizio in automatico..."
    if [[ "$OSTYPE" == "darwin"* ]]; then
        brew services start mysql &> /dev/null
    elif command -v systemctl &> /dev/null; then
        sudo systemctl start mysql 2>/dev/null || sudo systemctl start mysqld 2>/dev/null
    elif command -v service &> /dev/null; then
        sudo service mysql start 2>/dev/null || sudo service mysqld start 2>/dev/null
    fi
    
    echo "⏳ Attesa riavvio MySQL..."
    sleep 3
    
    # Riprovo
    if mysql -u $DB_USER -e "SELECT 1;" &> /dev/null; then
        MYSQL_CMD="mysql -u $DB_USER"
    else
        echo "❌ Errore critico: Impossibile avviare o connettersi a MySQL."
        echo "👉 Assicurati che MySQL sia installato, avviato e che l'utente 'root' non abbia alcuna password."
        exit 1
    fi
fi

echo "⏳ Creazione del database 'Progetto_WEB' se non esiste..."
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS Progetto_WEB;"

if [ $? -ne 0 ]; then
    echo "❌ Errore fatale durante la creazione del database."
    exit 1
fi

echo "⏳ Importazione dei dati da db_init/init.sql..."
$MYSQL_CMD Progetto_WEB < db_init/init.sql
if [ $? -eq 0 ]; then
    echo "✅ Database configurato con successo!"
else
    echo "❌ Si è verificato un errore durante l'importazione del file init.sql."
    exit 1
fi

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
