# Documento di Progetto - Web 2 (Caso A)

## Architettura e Scelte Tecnologiche

Il progetto adotta un'architettura classica basata su pattern MVC (Model-View-Controller) minimale, senza l'uso di framework enterprise come Spring o ORM come Hibernate, garantendo leggerezza e pieno controllo del flusso di esecuzione.

- **Maven**: Scelto come tool di build e gestione dipendenze. Permette di standardizzare la struttura del progetto (`src/main/java`, `src/main/webapp`), facilitando la compilazione e la pacchettizzazione (`mvn clean package`) in un archivio `.war` senza alcuna dipendenza dall'IDE.
- **JDBC Puro e Pattern DAO**: L'interazione con il database MySQL avviene tramite chiamate JDBC standard, incapsulate nel pattern DAO (`ContrattoTelefonicoDAO`). Questa scelta, unita a un modello Java Bean (`ContrattoTelefonico`), garantisce la separazione delle responsabilità (Separation of Concerns) isolando la logica di accesso ai dati. La classe `DBConnection` astrae il caricamento del Driver e fornisce le connessioni.
- **Servlet (Controller)**: Il livello di controllo è demandato alla Servlet (`MainServlet`). Essa intercetta le richieste, valida l'input (es. filtri di ricerca e parametri di paginazione), orchestra l'interrogazione tramite il DAO e infine popola il contesto web da inoltrare alla View.
- **Thymeleaf (View)**: La presentazione è delegata a Thymeleaf come motore di template. Grazie alla sua sintassi naturale integrata nei tag HTML, il file `index.html` (collocato in `WEB-INF/templates` per prevenirne l'accesso diretto via URL) si occupa unicamente della renderizzazione dinamica dei dati (cicli for per la tabella, condizioni per visualizzare i bottoni). Mantiene inoltre la coerenza grafica con l'interfaccia originale referenziando il contenuto della cartella `css`, disaccoppiando completamente logica di business e presentazione.
