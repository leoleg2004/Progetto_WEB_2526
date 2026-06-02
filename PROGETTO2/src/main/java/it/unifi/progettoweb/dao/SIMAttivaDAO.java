package it.unifi.progettoweb.dao;

import it.unifi.progettoweb.model.SIMAttiva;
import it.unifi.progettoweb.utils.DBConnection;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class SIMAttivaDAO {

    public int countSimAttive(String cercaCodice) {
        StringBuilder sql = new StringBuilder("SELECT COUNT(*) as totale FROM SIMAttiva WHERE 1=1");
        
        if (cercaCodice != null && !cercaCodice.trim().isEmpty()) {
            sql.append(" AND codice LIKE ?");
        }

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql.toString())) {
             
            if (cercaCodice != null && !cercaCodice.trim().isEmpty()) {
                stmt.setString(1, "%" + cercaCodice + "%");
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

    public List<SIMAttiva> findSimAttive(String cercaCodice, int limit, int offset) {
        List<SIMAttiva> list = new ArrayList<>();
        StringBuilder sql = new StringBuilder(
            "SELECT codice, tipoSIM, associataA, dataAttivazione " +
            "FROM SIMAttiva WHERE 1=1"
        );

        if (cercaCodice != null && !cercaCodice.trim().isEmpty()) {
            sql.append(" AND codice LIKE ?");
        }
        
        sql.append(" ORDER BY dataAttivazione DESC LIMIT ? OFFSET ?");

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql.toString())) {
             
            int paramIndex = 1;
            if (cercaCodice != null && !cercaCodice.trim().isEmpty()) {
                stmt.setString(paramIndex++, "%" + cercaCodice + "%");
            }
            
            stmt.setInt(paramIndex++, limit);
            stmt.setInt(paramIndex++, offset);

            try (ResultSet rs = stmt.executeQuery()) {
                while (rs.next()) {
                    SIMAttiva sim = new SIMAttiva();
                    sim.setCodice(rs.getString("codice"));
                    sim.setTipoSIM(rs.getString("tipoSIM"));
                    sim.setAssociataA(rs.getString("associataA"));
                    sim.setDataAttivazione(rs.getDate("dataAttivazione"));
                    list.add(sim);
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return list;
    }
}
