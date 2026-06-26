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
