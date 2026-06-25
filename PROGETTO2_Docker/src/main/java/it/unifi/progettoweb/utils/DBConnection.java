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
        // Docker: usa la variabile d'ambiente DB_PASSWORD se presente
        String envPassword = System.getenv("DB_PASSWORD");
        if (envPassword != null) {
            return DriverManager.getConnection(URL, USER, envPassword);
        }

        // Deploy locale: prova prima senza password, poi con "leonardo"
        try {
            return DriverManager.getConnection(URL, USER, "");
        } catch (SQLException e1) {
            try {
                return DriverManager.getConnection(URL, USER, "leonardo");
            } catch (SQLException e2) {
                throw new SQLException("Impossibile connettersi a MySQL. " +
                    "Verifica che il servizio sia attivo e che root abbia password vuota o 'leonardo'.", e2);
            }
        }
    }
}
