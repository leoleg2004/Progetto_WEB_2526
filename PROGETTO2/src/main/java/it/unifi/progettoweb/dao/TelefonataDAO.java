package it.unifi.progettoweb.dao;

import it.unifi.progettoweb.model.Telefonata;
import it.unifi.progettoweb.utils.DBConnection;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class TelefonataDAO {

    public int countTelefonate(String effettuataDa) {
        StringBuilder sql = new StringBuilder("SELECT COUNT(*) as totale FROM Telefonata WHERE 1=1");
        
        if (effettuataDa != null && !effettuataDa.trim().isEmpty()) {
            sql.append(" AND effettuataDa LIKE ?");
        }

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql.toString())) {
             
            int paramIndex = 1;
            if (effettuataDa != null && !effettuataDa.trim().isEmpty()) {
                stmt.setString(paramIndex++, "%" + effettuataDa + "%");
            }

            try (ResultSet rs = stmt.executeQuery()) {
                if (rs.next()) {
                    return rs.getInt("totale");
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return 0;
    }

    public List<Telefonata> findTelefonate(String effettuataDa, int limit, int offset) {
        List<Telefonata> list = new ArrayList<>();
        StringBuilder sql = new StringBuilder(
            "SELECT id, effettuataDa, data, ora, durata, costo " +
            "FROM Telefonata WHERE 1=1"
        );

        if (effettuataDa != null && !effettuataDa.trim().isEmpty()) {
            sql.append(" AND effettuataDa LIKE ?");
        }
        
        sql.append(" ORDER BY data DESC, ora DESC LIMIT ? OFFSET ?");

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql.toString())) {
             
            int paramIndex = 1;
            if (effettuataDa != null && !effettuataDa.trim().isEmpty()) {
                stmt.setString(paramIndex++, "%" + effettuataDa + "%");
            }
            
            stmt.setInt(paramIndex++, limit);
            stmt.setInt(paramIndex++, offset);

            try (ResultSet rs = stmt.executeQuery()) {
                while (rs.next()) {
                    Telefonata t = new Telefonata();
                    t.setId(rs.getString("id"));
                    t.setEffettuataDa(rs.getString("effettuataDa"));
                    t.setData(rs.getDate("data"));
                    t.setOra(rs.getTime("ora"));
                    t.setDurata(rs.getInt("durata"));
                    t.setCosto(rs.getDouble("costo"));
                    list.add(t);
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return list;
    }
}
