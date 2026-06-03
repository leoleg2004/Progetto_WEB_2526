package it.unifi.progettoweb.dao;

import it.unifi.progettoweb.model.ContrattoTelefonico;
import it.unifi.progettoweb.utils.DBConnection;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class ContrattoTelefonicoDAO {

    public int countContratti(String cercaNum, String cercaTipo) {
        StringBuilder sql = new StringBuilder("SELECT COUNT(*) as totale FROM ContrattoTelefonico C WHERE 1=1");
        
        if (cercaNum != null && !cercaNum.trim().isEmpty()) {
            sql.append(" AND C.numero LIKE ?");
        }
        if (cercaTipo != null && !cercaTipo.trim().isEmpty()) {
            sql.append(" AND C.tipo = ?");
        }

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql.toString())) {
             
            int paramIndex = 1;
            if (cercaNum != null && !cercaNum.trim().isEmpty()) {
                stmt.setString(paramIndex++, "%" + cercaNum + "%");
            }
            if (cercaTipo != null && !cercaTipo.trim().isEmpty()) {
                stmt.setString(paramIndex++, cercaTipo);
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

    public List<ContrattoTelefonico> findContratti(String cercaNum, String cercaTipo, int limit, int offset) {
        List<ContrattoTelefonico> list = new ArrayList<>();
        StringBuilder sql = new StringBuilder(
            "SELECT C.numero, C.dataAttivazione, C.tipo, C.minutiResidui, C.creditoResiduo, " +
            "(SELECT COUNT(*) FROM Telefonata T WHERE T.effettuataDa = C.numero) as num_chiamate, " +
            "(SELECT codice FROM SIMAttiva WHERE associataA = C.numero LIMIT 1) as sim_attiva, " +
            "(SELECT GROUP_CONCAT(codice SEPARATOR ', ') FROM SIMDisattiva WHERE eraAssociataA = C.numero) as sim_disattivate " +
            "FROM ContrattoTelefonico C WHERE 1=1"
        );

        if (cercaNum != null && !cercaNum.trim().isEmpty()) {
            sql.append(" AND C.numero LIKE ?");
        }
        if (cercaTipo != null && !cercaTipo.trim().isEmpty()) {
            sql.append(" AND C.tipo = ?");
        }
        
        sql.append(" ORDER BY C.dataAttivazione DESC LIMIT ? OFFSET ?");

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql.toString())) {
             
            int paramIndex = 1;
            if (cercaNum != null && !cercaNum.trim().isEmpty()) {
                stmt.setString(paramIndex++, "%" + cercaNum + "%");
            }
            if (cercaTipo != null && !cercaTipo.trim().isEmpty()) {
                stmt.setString(paramIndex++, cercaTipo);
            }
            
            stmt.setInt(paramIndex++, limit);
            stmt.setInt(paramIndex++, offset);

            try (ResultSet rs = stmt.executeQuery()) {
                while (rs.next()) {
                    ContrattoTelefonico c = new ContrattoTelefonico();
                    c.setNumero(rs.getString("numero"));
                    c.setDataAttivazione(rs.getDate("dataAttivazione"));
                    c.setTipo(rs.getString("tipo"));
                    
                    int minuti = rs.getInt("minutiResidui");
                    if (rs.wasNull()) {
                        c.setMinutiResidui(null);
                    } else {
                        c.setMinutiResidui(minuti);
                    }
                    
                    double credito = rs.getDouble("creditoResiduo");
                    if (rs.wasNull()) {
                        c.setCreditoResiduo(null);
                    } else {
                        c.setCreditoResiduo(credito);
                    }

                    c.setNumChiamate(rs.getInt("num_chiamate"));
                    c.setSimAttiva(rs.getString("sim_attiva"));
                    c.setSimDisattivate(rs.getString("sim_disattivate"));

                    list.add(c);
                }
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return list;
    }
}
