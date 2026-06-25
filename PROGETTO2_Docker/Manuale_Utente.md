# Manuale Tecnico di Installazione e Deploy

Il presente documento descrive le procedure per l'installazione e il deploy dell'applicazione Web "Progetto_WEB". È possibile scegliere tra un'installazione tramite ambiente virtuale Docker (consigliata) e un'installazione locale tradizionale.

---

## 1. Deploy tramite Docker (Consigliato)

Questa modalità installa e configura automaticamente il database MySQL, il Web Server Tomcat e l'applicazione isolandoli dal resto del sistema.

### Prerequisiti
1. Installare **Docker Desktop**.
2. Verificare che le porte `8080` e `3306` non siano in uso da altri servizi locali.

### Procedura di Installazione
1. Avviare Docker Desktop.
2. Aprire un terminale e posizionarsi nella directory principale del progetto.
3. Eseguire lo script di avvio in base al sistema operativo:
   - **Mac/Linux**: Eseguire `./deploy_docker.sh`
   - **Windows**: Eseguire `deploy_docker.bat`
4. Attendere il termine del processo di build (creazione container, compilazione e popolamento database).
5. Accedere all'applicazione tramite browser all'indirizzo: `http://localhost:8080/progetto-web`

### Terminare l'esecuzione
Per arrestare i servizi, eseguire da terminale nella cartella del progetto:
`docker-compose down`

---

## 2. Deploy Locale Senza Docker

Questa modalità compila il sorgente tramite Maven Wrapper e distribuisce il pacchetto nell'installazione locale di Tomcat.

### Prerequisiti
1. Installare **Java Development Kit** (versione 8 o 21).
2. Installare **Apache Tomcat** (versione 9 o 11).
3. Installare **MySQL Server** e assicurarsi che il demone sia in esecuzione.

### Procedura di Configurazione Credenziali (Importante)
Il progetto è configurato di default per connettersi a MySQL con utente `root` e nessuna password. Se l'installazione MySQL locale possiede credenziali differenti (es. una password specifica):
1. Aprire il file `run.sh` (Mac/Linux) o `run.bat` (Windows) e modificare i valori della variabile `DB_PASS` inserendo la propria password, oppure lasciare invariato.
2. Aprire il file `src/main/java/it/unifi/progettoweb/utils/DBConnection.java` e aggiornare il fallback per la propria password.

### Procedura di Installazione
1. Aprire un terminale e posizionarsi nella directory principale del progetto.
2. Eseguire lo script di deploy:
   - **Mac/Linux**: Eseguire `./run.sh`
   - **Windows**: Eseguire `run.bat`
3. Attendere l'esecuzione automatica dello script. Il processo si occuperà di:
   - Creare il database `Progetto_WEB` e importare la struttura tramite `db_init/init.sql`.
   - Scaricare le dipendenze e compilare il sorgente Java tramite Maven Wrapper.
   - Copiare l'archivio compilato `target/progetto-web.war` all'interno della directory `webapps` di Tomcat.
   - Avviare Tomcat.
4. (Opzionale) Qualora lo script non riesca a individuare la directory di Tomcat o ad avviarlo, copiare manualmente il file `target/progetto-web.war` in `<TOMCAT_HOME>/webapps` ed eseguire `startup.sh` (o `startup.bat`).
5. Accedere all'applicazione tramite browser all'indirizzo: `http://localhost:8080/progetto-web`

### Terminare l'esecuzione
Per arrestare il server Tomcat avviato dagli script di installazione locale, è possibile utilizzare gli script di spegnimento forniti:
- **Mac/Linux**: Fare doppio clic su `stop.sh` oppure eseguire da terminale `./stop.sh`
- **Windows**: Fare doppio clic su `stop.bat` oppure eseguire da terminale `stop.bat`

In alternativa, se è stato effettuato un avvio manuale personalizzato, recarsi nella directory `bin` del proprio Tomcat ed eseguire lo script ufficiale `shutdown.sh` (o `shutdown.bat`).

---

## 3. Risoluzione dei Problemi

* **Problema: Errore "Address already in use" all'avvio**
  * **Causa**: La porta 8080 o 3306 è occupata da un altro processo.
  * **Soluzione**: Individuare e terminare l'applicazione in ascolto sulla porta in conflitto.

* **Problema: Permesso negato (Permission denied) su sistemi Mac/Linux**
  * **Causa**: Mancanza di privilegi di esecuzione sugli script.
  * **Soluzione**: Eseguire dal terminale: `chmod +x deploy_docker.sh run.sh mvnw`

* **Problema: Errore "Access denied for user" durante la configurazione del DB in locale**
  * **Causa**: Le credenziali per accedere al database MySQL fornite al progetto sono errate.
  * **Soluzione**: Eseguire i passaggi descritti nella sezione "Procedura di Configurazione Credenziali" per allineare il codice ai parametri del proprio MySQL locale.

* **Problema: Errore 500 o crash durante la navigazione**
  * **Causa**: Impossibilità di connettersi al database MySQL da parte dell'applicazione Java.
  * **Soluzione**: Verificare che MySQL Server sia attivo e in esecuzione sulla porta 3306. Verificare la correttezza del file `DBConnection.java`.
