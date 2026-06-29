package it.unifi.progettoweb.utils;

import java.io.InputStream;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.Properties;

public class DBConnection {
    private static final String URL = System.getenv("DB_URL") != null ? System.getenv("DB_URL") : "jdbc:mysql://localhost:3306/Progetto_WEB";
    private static final String USER = System.getenv("DB_USER") != null ? System.getenv("DB_USER") : "root";
    
    private static String getPassword() {
        // 1. Variabile d'ambiente (usata da Docker)
        String envPassword = System.getenv("DB_PASSWORD");
        if (envPassword != null) {
            return envPassword;
        }
        
        // 2. File di properties locale (creato dagli script run.sh / run.bat)
        try (InputStream input = DBConnection.class.getClassLoader().getResourceAsStream("db.properties")) {
            if (input != null) {
                Properties prop = new Properties();
                prop.load(input);
                return prop.getProperty("db.password", "");
            }
        } catch (Exception e) {
            // Ignora e usa password vuota
        }
        
        // 3. Fallback a password vuota
        return "";
    }

    static {
        try {
            Class.forName("com.mysql.cj.jdbc.Driver");
        } catch (ClassNotFoundException e) {
            throw new RuntimeException("Driver MySQL non trovato", e);
        }
    }

    public static Connection getConnection() throws SQLException {
        String password = getPassword();
        try {
            return DriverManager.getConnection(URL, USER, password);
        } catch (SQLException e) {
            throw new SQLException("Impossibile connettersi a MySQL. " +
                "Verifica che il servizio sia attivo e che la password sia corretta.", e);
        }
    }
}
