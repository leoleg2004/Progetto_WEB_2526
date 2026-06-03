#!/bin/bash
echo "🚀 Avvio compilazione Maven..."
mvn clean package

if [ $? -eq 0 ]; then
    echo "✅ Compilazione completata con successo!"
    echo "📦 Copia del file .war su Tomcat in corso..."
    cp target/progetto-web.war /opt/homebrew/opt/tomcat@9/libexec/webapps/
    
    # Controllo se Tomcat risponde sulla porta 8080, altrimenti lo avvio
    if ! nc -z localhost 8080 &>/dev/null; then
        echo "⚙️  Tomcat sembra spento. Lo sto accendendo in automatico..."
        /opt/homebrew/opt/tomcat@9/libexec/bin/startup.sh
    fi
    
    echo "🎉 Deploy completato! Tomcat sta ricaricando il progetto in background."
    echo "🌐 Vai a: http://localhost:8080/progetto-web"
    echo "🛑 Per spegnere il server usa: /opt/homebrew/opt/tomcat@9/libexec/bin/shutdown.sh"
else
    echo "❌ Errore durante la compilazione. Il deploy è stato annullato."
fi
