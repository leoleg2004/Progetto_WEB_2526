#!/bin/bash
echo "🚀 Avvio compilazione Maven..."
mvn clean package

if [ $? -eq 0 ]; then
    echo "✅ Compilazione completata con successo!"
    echo "📦 Copia del file .war su Tomcat in corso..."
    cp target/progetto-web.war /opt/homebrew/opt/tomcat@9/libexec/webapps/
    
    echo "🎉 Deploy completato! Tomcat sta ricaricando il progetto in background."
    echo "🌐 Vai a: http://localhost:8080/progetto-web"
else
    echo "❌ Errore durante la compilazione. Il deploy è stato annullato."
fi
