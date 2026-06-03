# Manuale Utente - Installazione e Deploy

Questo progetto supporta due modalità di installazione:
1. **[CONSIGLIATA] Deploy Automatico (Ambiente Virtuale con Docker)** - Zero configurazioni manuali.
2. **Deploy Manuale** - Per chi preferisce installare e configurare individualmente ogni componente.

---

## 1. Deploy Automatico (CONSIGLIATO)

Questa modalità utilizza **Docker** per creare un ambiente isolato contenente il database MySQL, il server Tomcat e l'applicazione già compilata. Non andrà in conflitto con eventuali software già installati sulla macchina.

### Prerequisiti
- Installare **Docker Desktop** (scaricabile da [docker.com](https://www.docker.com/products/docker-desktop)).
- (Opzionale ma raccomandato) Assicurarsi che le porte `8080` (Tomcat) e `3306` (MySQL) siano libere, spegnendo eventuali server locali in esecuzione.

### Avvio dell'ambiente
1. Aprire Docker Desktop e assicurarsi che sia in esecuzione (icona della balena nella barra di sistema).
2. Aprire il terminale (o prompt dei comandi) e posizionarsi nella cartella principale del progetto.
3. Eseguire lo script di avvio corrispondente al proprio sistema operativo:
   - **Mac/Linux**: Eseguire dal terminale `./deploy_docker.sh`
   - **Windows**: Fare doppio clic sul file `deploy_docker.bat`
4. Attendere il completamento delle operazioni (il database verrà popolato automaticamente e il progetto compilato).
5. Aprire il browser web e collegarsi a: `http://localhost:8080/progetto-web`

**Per spegnere il server:** aprire il terminale nella cartella del progetto e digitare `docker-compose down`.

---

## 2. Deploy Manuale (Tradizionale)

### Prerequisiti
1. Installare Java Development Kit (JDK 21).
2. Installare Apache Tomcat (versione 9).
3. Installare MySQL Server.
4. Installare Apache Maven.
5. Configurare le variabili di ambiente JAVA_HOME e M2_HOME e aggiungere i percorsi alla variabile PATH.

### Configurazione Database
1. Avviare il server MySQL.
2. Aprire il terminale e digitare il comando: `mysql -u root -p < db_init/init.sql`.
3. Inserire la password (es. `leonardo`) alla richiesta.
4. Assicurarsi che nel file `src/main/java/it/unifi/progettoweb/utils/DBConnection.java` username e password corrispondano al proprio database.

### Compilazione e Deploy
1. Aprire il terminale nella cartella del progetto (dove si trova `pom.xml`).
2. Digitare il comando: `mvn clean package` e attendere il messaggio BUILD SUCCESS.
3. Copiare il file generato `target/progetto-web.war`.
4. Incollarlo all'interno della cartella `webapps` di Apache Tomcat.
5. Avviare Tomcat tramite lo script `startup.bat` (Windows) o `startup.sh` (Mac/Linux).
6. Aprire il browser e digitare: `http://localhost:8080/progetto-web`.

---

## Risoluzione Problemi Frequenti

* **Problema: Errore "Address already in use" (Avvio Docker)**
  * **Soluzione:** Hai un'altra applicazione (probabilmente un Tomcat locale) che occupa già la porta 8080. Spegnila prima di lanciare lo script Docker.

* **Problema: Errore "mvn: command not found" (Installazione Manuale)**
  * **Soluzione:** Verificare l'installazione di Maven. Controllare la configurazione della variabile di ambiente PATH.

* **Problema: Errore di connessione al database oppure "Access denied" (Installazione Manuale)**
  * **Soluzione:** Aprire il file `DBConnection.java`. Correggere username e password in base alla propria installazione locale ed eseguire di nuovo `mvn clean package`.
