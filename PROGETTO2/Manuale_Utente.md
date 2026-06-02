# Manuale Utente - Installazione e Deploy

## Prerequisiti
1. Installare Java Development Kit (JDK 8 o 21).
2. Installare Apache Tomcat (versione 9).
3. Installare MySQL Server.
4. Installare Apache Maven.
5. Configurare le variabili di ambiente JAVA_HOME e M2_HOME.
6. Aggiungere i percorsi bin di Maven e Java alla variabile PATH.

## Configurazione Database
1. Avviare il server MySQL.
2. Aprire il terminale.
3. Digitare il comando: `mysql -u root -p < database.sql`.
4. Inserire la password in seguito alla richiesta.
5. Verificare l'esistenza del database Progetto_WEB.

## Compilazione
1. Aprire il terminale.
2. Cambiare directory verso la cartella del progetto contenente il file pom.xml.
3. Digitare il comando: `mvn clean package`.
4. Premere il tasto Invio.
5. Attendere il messaggio BUILD SUCCESS.

## Deploy su Apache Tomcat
1. Aprire la cartella target creata all'interno del progetto.
2. Copiare il file progetto-web.war.
3. Incollare il file progetto-web.war all'interno della cartella webapps di Apache Tomcat.
4. Avviare Apache Tomcat tramite lo script startup.bat (Windows) o startup.sh (Mac/Linux) presente nella cartella bin.
5. Aprire il browser web.
6. Digitare l'indirizzo http://localhost:8080/progetto-web.
7. Premere il tasto Invio.

## Risoluzione Problemi

* Problema: Errore "mvn: command not found"
  * Soluzione: Verificare l'installazione di Maven. Controllare la configurazione della variabile di ambiente PATH.

* Problema: Errore di connessione al database oppure "Access denied"
  * Soluzione: Verificare lo stato del server MySQL. Il server deve essere in esecuzione. Aprire il file DBConnection.java. Correggere username e password in base all'installazione locale. Eseguire di nuovo la compilazione tramite il comando `mvn clean package`.

* Problema: Errore 404 durante l'accesso da browser web
  * Soluzione: Verificare l'avvio corretto di Tomcat tramite i log del file catalina.out. Verificare la presenza del file .war nella cartella webapps. Controllare la correttezza dell'indirizzo URL.
