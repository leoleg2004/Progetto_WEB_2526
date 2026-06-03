package it.unifi.progettoweb.utils;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

public class DBConnection {
    private static final String URL = "jdbc:mysql://localhost:3306/Progetto_WEB";
    private static final String USER = "root";
    private static final String PASSWORD = "leonardo";

    static {
        try {
            // Carica il driver per assicurare la compatibilità con Tomcat
            Class.forName("com.mysql.cj.jdbc.Driver");
        } catch (ClassNotFoundException e) {
            throw new RuntimeException("Driver MySQL non trovato", e);
        }
    }

    public static Connection getConnection() throws SQLException {
        return DriverManager.getConnection(URL, USER, PASSWORD);
    }
}
