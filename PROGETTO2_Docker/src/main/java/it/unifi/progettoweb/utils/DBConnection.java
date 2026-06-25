package it.unifi.progettoweb.utils;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public class DBConnection {
    private static final String URL = System.getenv("DB_URL") != null ? System.getenv("DB_URL") : "jdbc:mysql://localhost:3306/Progetto_WEB";
    private static final String USER = System.getenv("DB_USER") != null ? System.getenv("DB_USER") : "root";
    private static final String PASSWORD = System.getenv("DB_PASSWORD") != null ? System.getenv("DB_PASSWORD") : "";

    static {
        try {
            // Carica il driver per assicurare la compatibilità con Tomcat
            Class.forName("com.mysql.cj.jdbc.Driver");
        } catch (ClassNotFoundException e) {
            throw new RuntimeException("Driver MySQL non trovato", e);
        }
    }

    public static Connection getConnection() throws SQLException {
        // Se c'è una password esplicita nelle variabili d'ambiente (Docker), usa quella.
        String envPassword = System.getenv("DB_PASSWORD");
        if (envPassword != null) {
            return DriverManager.getConnection(URL, USER, envPassword);
        }

        // Deploy locale: Prova prima con la password 'leonardo', se fallisce prova senza password.
        try {
            return DriverManager.getConnection(URL, USER, "leonardo");
        } catch (SQLException e) {
            // Se fallisce per accesso negato, riprova con password vuota
            return DriverManager.getConnection(URL, USER, "");
        }
    }
}
