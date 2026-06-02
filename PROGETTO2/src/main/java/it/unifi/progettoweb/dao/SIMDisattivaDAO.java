package it.unifi.progettoweb.dao;

import it.unifi.progettoweb.model.SIMDisattiva;
import it.unifi.progettoweb.utils.DBConnection;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class SIMDisattivaDAO {

    public int countSimDisattivate(String cercaCodice) {
        StringBuilder sql = new StringBuilder("SELECT COUNT(*) as totale FROM SIMDisattiva WHERE 1=1");
        
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

    public List<SIMDisattiva> findSimDisattivate(String cercaCodice, int limit, int offset) {
        List<SIMDisattiva> list = new ArrayList<>();
        StringBuilder sql = new StringBuilder(
            "SELECT codice, tipoSIM, eraAssociataA, dataAttivazione, dataDisattivazione " +
            "FROM SIMDisattiva WHERE 1=1"
        );

        if (cercaCodice != null && !cercaCodice.trim().isEmpty()) {
            sql.append(" AND codice LIKE ?");
        }
        
        sql.append(" ORDER BY dataDisattivazione DESC LIMIT ? OFFSET ?");

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
                    SIMDisattiva sim = new SIMDisattiva();
                    sim.setCodice(rs.getString("codice"));
                    sim.setTipoSIM(rs.getString("tipoSIM"));
                    sim.setEraAssociataA(rs.getString("eraAssociataA"));
                    sim.setDataAttivazione(rs.getDate("dataAttivazione"));
                    sim.setDataDisattivazione(rs.getDate("dataDisattivazione"));
                    list.add(sim);
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return list;
    }
}
