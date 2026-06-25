#!/bin/bash
cd "$(dirname "$0")"
echo "========================================================"
echo "  Arresto del server Tomcat (Deploy Locale)"
echo "========================================================"
echo "🛑 Arresto di Tomcat in corso..."

TOMCAT_BIN=""

if [ -n "$TOMCAT_HOME" ]; then
    TOMCAT_BIN="$TOMCAT_HOME/bin"
elif [ -d "/opt/homebrew/opt/tomcat@9/libexec/bin" ]; then
    TOMCAT_BIN="/opt/homebrew/opt/tomcat@9/libexec/bin"
elif [ -d "/usr/local/tomcat/bin" ]; then
    TOMCAT_BIN="/usr/local/tomcat/bin"
elif [ -d "apache-tomcat-9.0.87/bin" ]; then
    TOMCAT_BIN="$(pwd)/apache-tomcat-9.0.87/bin"
fi

if [ -n "$TOMCAT_BIN" ] && [ -x "$TOMCAT_BIN/shutdown.sh" ]; then
    "$TOMCAT_BIN/shutdown.sh"
    echo "✅ Tomcat arrestato con successo (Tramite script ufficiale)!"
else
    echo "⚠️ Impossibile trovare lo script shutdown.sh standard."
    echo "⏳ Termino forzatamente i processi Tomcat..."
    pkill -f tomcat
    echo "✅ Tomcat arrestato (Terminazione forzata)."
fi
