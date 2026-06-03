#!/bin/bash
echo "🚀 Avvio deploy del progetto in ambiente virtuale (Docker)..."

# Controllo se Docker è installato
if ! command -v docker &> /dev/null
then
    echo "❌ Errore: Docker non è installato su questa macchina."
    echo "Scarica e installa Docker Desktop da https://www.docker.com/products/docker-desktop"
    exit 1
fi

# Controllo se il demone Docker è in esecuzione, altrimenti provo ad avviarlo
if ! docker info > /dev/null 2>&1; then
    echo "🐳 Avvio di Docker Desktop in corso..."
    if [[ "$OSTYPE" == "darwin"* ]]; then
        open -a Docker
    else
        echo "⚠️ Avvia Docker Desktop manualmente e riprova."
        exit 1
    fi
    
    echo "⏳ Attesa che Docker sia pronto (potrebbe richiedere qualche secondo)..."
    while ! docker info > /dev/null 2>&1; do
        sleep 2
    done
    echo "✅ Docker è ora attivo!"
fi

echo "📦 Costruzione dell'immagine e avvio dei container (Database e Server Web)..."
docker-compose up --build -d

if [ $? -eq 0 ]; then
    echo "✅ Deploy completato con successo!"
    echo "⏳ Attendi qualche secondo affinché MySQL e Tomcat si avviino completamente."
    echo "🌐 Il progetto sarà disponibile all'indirizzo: http://localhost:8080/progetto-web"
    echo "Per spegnere l'ambiente esegui: docker-compose down"
else
    echo "❌ Errore durante la creazione dei container. Il deploy è stato annullato."
fi
