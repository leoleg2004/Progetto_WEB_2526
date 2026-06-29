<<<<<<< Updated upstream
# Manuale Utente

## Cosa si sta installando
L'applicazione "Centro Gestione Contratti" permette di gestire contratti telefonici, SIM e telefonate.
L'installazione prevede il caricamento del database MySQL e la distribuzione dell'applicazione Java nel server Tomcat.

---

## 1. Deploy Locale

### Prerequisiti
1. Installare Java Development Kit (versione 8 o 21).
2. Installare Apache Tomcat (versione 9 o 11).
3. Installare MySQL Server.
4. Avviare il demone MySQL.

### Procedura di Configurazione Credenziali
> [!NOTE]
> Il progetto è stato testato con l'utente `root` di MySQL senza password. In questa configurazione l'installazione procede in automatico senza richiedere modifiche.

Se MySQL richiede una password specifica, configurare le credenziali:
1. Aprire il file `run.sh` (Mac/Linux) o `run.bat` (Windows).
2. Inserire la password di MySQL nella variabile `DB_PASS`.
3. Aprire il file `src/main/java/it/unifi/progettoweb/utils/DBConnection.java`.
4. Inserire la password di MySQL nella variabile `PASSWORD`.

### Procedura di Installazione
1. Aprire un terminale.
2. Posizionarsi nella cartella principale del progetto.
3. Eseguire lo script `./run.sh` (Mac/Linux) oppure `run.bat` (Windows).
4. Attendere il completamento delle operazioni (creazione database, compilazione codice, copia su Tomcat, avvio server Tomcat).
5. Aprire un browser web.
6. Visitare l'indirizzo `http://localhost:8080/progetto-web`.

### Procedura di Arresto
1. Aprire un terminale.
2. Posizionarsi nella cartella principale del progetto.
3. Eseguire lo script `./stop.sh` (Mac/Linux) oppure `stop.bat` (Windows).

---

## 2. Deploy tramite Docker (Opzionale)

### Prerequisiti
1. Installare Docker Desktop.
2. Liberare le porte 8080 e 3306 disattivando eventuali servizi locali in ascolto.

### Procedura di Installazione
1. Avviare Docker Desktop.
2. Aprire un terminale.
3. Posizionarsi nella cartella principale del progetto.
4. Eseguire lo script `./deploy_docker.sh` (Mac/Linux) oppure `deploy_docker.bat` (Windows).
5. Attendere il completamento della procedura.
6. Aprire un browser web.
7. Visitare l'indirizzo `http://localhost:8080/progetto-web`.

### Procedura di Arresto
1. Aprire un terminale.
2. Posizionarsi nella cartella principale del progetto.
3. Eseguire il comando `docker-compose down`.

---

## Risoluzione dei Problemi

**Errore "Address already in use" all'avvio**
* Causa: La porta 8080 o 3306 risulta occupata.
* Soluzione: Individuare e terminare l'applicazione in ascolto sulla porta 8080 o 3306.

**Permesso negato (Permission denied) su sistemi Mac/Linux**
* Causa: Mancanza di privilegi di esecuzione.
* Soluzione: Eseguire il comando `chmod +x deploy_docker.sh run.sh stop.sh mvnw`.

**Errore "Access denied for user"**
* Causa: Credenziali del database errate.
* Soluzione: Eseguire la "Procedura di Configurazione Credenziali".

**Errore 500 o crash durante la navigazione**
* Causa: L'applicazione Java fallisce la connessione al database MySQL.
* Soluzione: Verificare l'esecuzione del Server MySQL sulla porta 3306. Controllare le credenziali nel file `DBConnection.java`.
=======
# Manuale Tecnico e Utente dell'Applicazione

Il presente documento descrive le procedure per l'installazione, il deploy e l'utilizzo dell'applicazione Web "Progetto_WEB". Il manuale è strutturato in un formato impersonale per garantire massima chiarezza, consentire un avvio rapido e fornire le soluzioni alle potenziali casistiche di errore.

---

## ⚡ Avvio Rapido (In Meno di 5 Minuti)

Il metodo più veloce e sicuro per avviare il progetto — senza la necessità di configurare manualmente ambienti Java, Tomcat o MySQL — prevede l'impiego di un ambiente virtualizzato Docker.

1. Assicurarsi che **Docker Desktop** sia in esecuzione sul sistema.
2. Aprire un terminale all'interno della directory principale del progetto.
3. Eseguire lo script di avvio rapido fornito:
   - Su ambienti **Mac/Linux**: `./deploy_docker.sh`
   - Su ambienti **Windows**: `deploy_docker.bat` (eseguibile anche tramite doppio clic)
4. Attendere il completamento del processo automatizzato (tempo stimato: 1-2 minuti).
5. Aprire un browser Web e navigare all'indirizzo: `http://localhost:8080/progetto-web`
6. Per terminare l'esecuzione e liberare le risorse, eseguire da terminale: `docker-compose down`.

---

## 1. Deploy Locale Tradizionale (Metodo Alternativo Senza Docker)

Si consiglia l'adozione di questa procedura qualora non sia possibile o non si desideri utilizzare la virtualizzazione tramite Docker. Gli script automatizzati forniti provvederanno alla compilazione e al setup di eventuali requisiti mancanti (tra cui il server Apache Tomcat).

### 1.1 Prerequisiti Minimi del Sistema
Per l'avvio locale è necessaria l'installazione e l'esecuzione preventiva dei seguenti servizi:
- **Java Development Kit** (è garantita la compatibilità sia per la versione 8 che 21).
- **MySQL Server** (il demone del database deve risultare attivo e in ascolto).

### 1.2 Procedura di Installazione e Avvio
1. Aprire un terminale all'interno della cartella principale.
2. Invocare lo script di configurazione e deploy automatico:
   - Su **Mac/Linux**: Eseguire `./run.sh`
   - Su **Windows**: Eseguire `run.bat`
3. Il sistema si occuperà autonomamente di:
   - Eseguire controlli diagnostici sui servizi richiesti.
   - Creare il database `Progetto_WEB` e iniettare i dati iniziali di prova.
   - Compilare i file sorgente Java tramite l'utility Maven Wrapper.
   - Ricercare un'istanza di Tomcat (o scaricarne una in formato portatile) e avviare il Web Server.
4. L'accesso al gestionale avverrà tramite l'indirizzo: `http://localhost:8080/progetto-web`

### 1.3 Gestione delle Credenziali del Database
L'applicativo vanta un sistema di adattamento automatico per l'ambiente locale: tenta la connessione a MySQL utilizzando l'utente `root` valutando, in modo silente, sia la password predefinita pregressa (`leonardo`) sia l'assenza di password (stringa vuota). 
Qualora il database del proprio sistema risulti protetto da una password differente, si rende necessaria la configurazione manuale:
- Modificare i file `run.sh` (o `run.bat`) valorizzando la variabile contenente la password con la stringa opportuna.
- Modificare il file `src/main/java/it/unifi/progettoweb/utils/DBConnection.java` affinché la connessione Java adotti le credenziali corrette.

### 1.4 Chiusura del Server Locale
Per arrestare in totale sicurezza il server Tomcat invocato dagli script locali, si faccia uso delle utility di terminazione preconfigurate:
- Su **Mac/Linux**: Eseguire `./stop.sh`
- Su **Windows**: Eseguire `stop.bat`

---

## 2. Risoluzione delle Problematiche Comuni (Troubleshooting)

Si riporta di seguito l'elenco delle anomalie riscontrabili e le relative procedure di risoluzione:

* **Problema: Errore "Address already in use" durante l'avvio**
  * **Causa**: La porta di rete `8080` (utilizzata da Tomcat) o la porta `3306` (utilizzata da MySQL) risulta già occupata da un processo concorrente sul sistema.
  * **Soluzione**: Identificare e terminare l'applicazione in ascolto sulla porta segnalata in conflitto. Spesso la problematica è riconducibile a istanze precedenti del server Tomcat non terminate correttamente (si consiglia l'uso frequente degli script `stop.sh` / `stop.bat`).

* **Problema: Permesso negato ("Permission denied") in ambiente Unix/Mac**
  * **Causa**: Mancanza dei permessi di esecuzione a livello di file system sugli script forniti.
  * **Soluzione**: Assegnare i diritti di esecuzione tramite il comando: `chmod +x *.sh mvnw`

* **Problema: Errore "Impossibile creare il database" / "MySQL non risponde"**
  * **Causa**: Il servizio di background MySQL non è avviato, l'installazione è corrotta, oppure le credenziali dell'utente `root` del sistema non coincidono con quelle testate automaticamente (`leonardo` oppure stringa vuota).
  * **Soluzione**: Verificare preventivamente che il servizio MySQL sia avviato (es. tramite l'interfaccia XAMPP, `systemctl start mysql`, o `brew services start mysql`). Se il problema persiste, allineare le credenziali seguendo le istruzioni riportate nella sezione 1.3 del presente documento.

* **Problema: Errore 404 (Pagina Non Trovata) aprendo il link locale**
  * **Causa**: L'applicativo Java compilato (file `.war`) non è stato esposto correttamente dal server Tomcat.
  * **Soluzione**: Verificare la presenza di errori nei log del terminale inerenti ai privilegi di scrittura. In extremis, prelevare manualmente il file generato `target/progetto-web.war`, inserirlo all'interno della cartella `webapps` del proprio Tomcat di fiducia, ed avviare il servizio tramite i comandi ufficiali (`startup.sh` / `startup.bat`).

* **Problema: Errore 500 (Internal Server Error) o crash interno all'applicativo**
  * **Causa**: Si è verificata un'interruzione anomala della comunicazione tra il server Web e il Database, o vi è un'alterazione critica nello schema dei dati.
  * **Soluzione**: Assicurarsi che MySQL non sia andato in stato di timeout o sia stato chiuso. Per ripristinare la coerenza dei dati d'esempio, arrestare il server e lanciare un nuovo ciclo completo di build, il quale rieseguirà le direttive del file `init.sql`.
>>>>>>> Stashed changes
