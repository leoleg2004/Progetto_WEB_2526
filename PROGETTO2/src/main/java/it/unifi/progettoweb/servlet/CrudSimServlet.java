package it.unifi.progettoweb.servlet;

import it.unifi.progettoweb.utils.DBConnection;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.HttpSession;

import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Map;

@WebServlet(urlPatterns = {"/crud_sim.php"})
public class CrudSimServlet extends HttpServlet {

    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        request.setCharacterEncoding("UTF-8");
        String action = request.getParameter("action");
        String statoSim = request.getParameter("stato_sim");
        if (statoSim == null) statoSim = "attiva";

        String redirectUrl = "index.php";
        if (statoSim.equals("disattiva")) redirectUrl = "sim_disattivate.php";
        else if (statoSim.equals("non_attiva")) redirectUrl = "sim_non_attive.php";

        if (action == null) {
            response.sendRedirect(redirectUrl);
            return;
        }

        try (Connection conn = DBConnection.getConnection()) {
            
            if (action.equals("create")) {
                String codice = request.getParameter("codice");
                String tipoSIM = request.getParameter("tipoSIM");
                String associataA = request.getParameter("associataA");
                String dataAttivazione = request.getParameter("dataAttivazione");
                String stato = request.getParameter("stato");

                if (!haFormatoValido(codice, statoSim)) {
                    response.sendRedirect(redirectUrl + "?err=invalid_format");
                    return;
                }
                
                if (!isCodiceValido(conn, codice, "")) {
                    response.sendRedirect(redirectUrl + "?err=duplicate_codice");
                    return;
                }

                String sql = "";
                if (statoSim.equals("attiva")) {
                    if (associataA == null || associataA.isEmpty()) {
                        response.sendRedirect(redirectUrl + "?err=foreign_key");
                        return;
                    }
                    if (contrattoGiaAssociato(conn, associataA, "")) {
                        response.sendRedirect(redirectUrl + "?err=duplicate_assoc");
                        return;
                    }
                    if (!contrattoEsiste(conn, associataA)) {
                        sql = "BEGIN; " +
                              "INSERT INTO ContrattoTelefonico (numero, dataAttivazione, tipo, minutiResidui, creditoResiduo) VALUES ('" + sanitize(associataA) + "', '" + sanitize(dataAttivazione) + "', 'ricarica', NULL, 0.00); " +
                              "INSERT INTO SIMAttiva (codice, tipoSIM, associataA, dataAttivazione) VALUES ('" + sanitize(codice) + "', '" + sanitize(tipoSIM) + "', '" + sanitize(associataA) + "', '" + sanitize(dataAttivazione) + "'); " +
                              "COMMIT;";
                    } else {
                        sql = "INSERT INTO SIMAttiva (codice, tipoSIM, associataA, dataAttivazione) VALUES ('" + sanitize(codice) + "', '" + sanitize(tipoSIM) + "', '" + sanitize(associataA) + "', '" + sanitize(dataAttivazione) + "')";
                    }
                } else if (statoSim.equals("non_attiva")) {
                    sql = "INSERT INTO SIMNonAttiva (codice, tipoSIM) VALUES ('" + sanitize(codice) + "', '" + sanitize(tipoSIM) + "')";
                } else if (statoSim.equals("disattiva")) {
                    String eraAssociataA = request.getParameter("eraAssociataA");
                    String dataDisattivazione = request.getParameter("dataDisattivazione");
                    if (eraAssociataA != null && !eraAssociataA.isEmpty() && !contrattoEsiste(conn, eraAssociataA)) {
                        sql = "BEGIN; " +
                              "INSERT INTO ContrattoTelefonico (numero, dataAttivazione, tipo, minutiResidui, creditoResiduo) VALUES ('" + sanitize(eraAssociataA) + "', '" + sanitize(dataAttivazione) + "', 'ricarica', NULL, 0.00); " +
                              "INSERT INTO SIMDisattiva (codice, tipoSIM, eraAssociataA, dataAttivazione, dataDisattivazione) VALUES ('" + sanitize(codice) + "', '" + sanitize(tipoSIM) + "', '" + sanitize(eraAssociataA) + "', '" + sanitize(dataAttivazione) + "', '" + sanitize(dataDisattivazione) + "'); " +
                              "COMMIT;";
                    } else {
                        sql = "INSERT INTO SIMDisattiva (codice, tipoSIM, eraAssociataA, dataAttivazione, dataDisattivazione) VALUES ('" + sanitize(codice) + "', '" + sanitize(tipoSIM) + "', '" + sanitize(eraAssociataA) + "', '" + sanitize(dataAttivazione) + "', '" + sanitize(dataDisattivazione) + "')";
                    }
                }

                savePendingAndRedirect(request, response, sql, "create", codice, redirectUrl);

            } else if (action.equals("update")) {
                String oldCodice = request.getParameter("old_codice");
                String codice = request.getParameter("codice");
                String tipoSIM = request.getParameter("tipoSIM");
                String associataA = request.getParameter("associataA");
                String dataAttivazione = request.getParameter("dataAttivazione");
                String stato = request.getParameter("stato");

                if (!haFormatoValido(codice, statoSim)) {
                    response.sendRedirect(redirectUrl + "?err=invalid_format");
                    return;
                }

                if (!isCodiceValido(conn, codice, oldCodice)) {
                    response.sendRedirect(redirectUrl + "?err=duplicate_codice");
                    return;
                }

                String sql = "";
                if (statoSim.equals("attiva")) {
                    if (associataA == null || associataA.isEmpty()) {
                        response.sendRedirect(redirectUrl + "?err=foreign_key");
                        return;
                    }
                    if (!contrattoEsiste(conn, associataA)) {
                        response.sendRedirect(redirectUrl + "?err=not_found");
                        return;
                    }
                    if (contrattoGiaAssociato(conn, associataA, oldCodice)) {
                        response.sendRedirect(redirectUrl + "?err=duplicate_assoc");
                        return;
                    }
                    sql = "UPDATE SIMAttiva SET codice='" + sanitize(codice) + "', tipoSIM='" + sanitize(tipoSIM) + "', associataA='" + sanitize(associataA) + "', dataAttivazione='" + sanitize(dataAttivazione) + "' WHERE codice='" + sanitize(oldCodice) + "'";
                } else if (statoSim.equals("non_attiva")) {
                    sql = "UPDATE SIMNonAttiva SET codice='" + sanitize(codice) + "', tipoSIM='" + sanitize(tipoSIM) + "' WHERE codice='" + sanitize(oldCodice) + "'";
                } else if (statoSim.equals("disattiva")) {
                    String eraAssociataA = request.getParameter("eraAssociataA");
                    String dataDisattivazione = request.getParameter("dataDisattivazione");
                    if (eraAssociataA != null && !eraAssociataA.isEmpty() && !contrattoEsiste(conn, eraAssociataA)) {
                        response.sendRedirect(redirectUrl + "?err=not_found");
                        return;
                    }
                    sql = "UPDATE SIMDisattiva SET codice='" + sanitize(codice) + "', tipoSIM='" + sanitize(tipoSIM) + "', eraAssociataA='" + sanitize(eraAssociataA) + "', dataAttivazione='" + sanitize(dataAttivazione) + "', dataDisattivazione='" + sanitize(dataDisattivazione) + "' WHERE codice='" + sanitize(oldCodice) + "'";
                }

                savePendingAndRedirect(request, response, sql, "update", codice, redirectUrl);

            } else if (action.equals("delete")) {
                String codice = request.getParameter("codice");
                String sql = "";
                if (statoSim.equals("attiva")) sql = "DELETE FROM SIMAttiva WHERE codice='" + sanitize(codice) + "'";
                else if (statoSim.equals("disattiva")) sql = "DELETE FROM SIMDisattiva WHERE codice='" + sanitize(codice) + "'";
                else if (statoSim.equals("non_attiva")) sql = "DELETE FROM SIMNonAttiva WHERE codice='" + sanitize(codice) + "'";

                savePendingAndRedirect(request, response, sql, "delete", codice, redirectUrl);

            } else if (action.equals("deactivate")) {
                String codice = request.getParameter("codice");
                
                String q = "SELECT tipoSIM, associataA, dataAttivazione FROM SIMAttiva WHERE codice=?";
                try (PreparedStatement stmt = conn.prepareStatement(q)) {
                    stmt.setString(1, codice);
                    try (ResultSet rs = stmt.executeQuery()) {
                        if (rs.next()) {
                            String tipoSIM = rs.getString("tipoSIM");
                            String associataA = rs.getString("associataA");
                            java.sql.Date dataAttivazione = rs.getDate("dataAttivazione");
                            java.sql.Date dataDisattivazione = new java.sql.Date(System.currentTimeMillis());
                            
                            String sql = "BEGIN; " +
                                         "INSERT INTO SIMDisattiva (codice, tipoSIM, eraAssociataA, dataAttivazione, dataDisattivazione) " +
                                         "VALUES ('" + sanitize(codice.replace("SIM-A-", "SIM-D-")) + "', '" + sanitize(tipoSIM) + "', '" + sanitize(associataA) + "', '" + dataAttivazione.toString() + "', '" + dataDisattivazione.toString() + "'); " +
                                         "DELETE FROM SIMAttiva WHERE codice = '" + sanitize(codice) + "'; " +
                                         "COMMIT;";
                                         
                            savePendingAndRedirect(request, response, sql, "deactivate", codice, redirectUrl);
                        } else {
                            response.sendRedirect(redirectUrl + "?err=not_found");
                        }
                    }
                }
            } else if (action.equals("activate")) {
                String codice = request.getParameter("codice");
                String associataA = request.getParameter("associataA");
                String tipoContratto = request.getParameter("tipo_contratto") != null ? request.getParameter("tipo_contratto") : "ricarica";
                String valoreInizialeStr = request.getParameter("valore_iniziale") != null ? request.getParameter("valore_iniziale") : "0.00";
                
                if (associataA == null || !associataA.matches("[0-9]{10}")) {
                    response.sendRedirect(redirectUrl + "?err=invalid_contract_format");
                    return;
                }
                
                String q = "SELECT tipoSIM FROM SIMNonAttiva WHERE codice=?";
                try (PreparedStatement stmt = conn.prepareStatement(q)) {
                    stmt.setString(1, codice);
                    try (ResultSet rs = stmt.executeQuery()) {
                        if (rs.next()) {
                            String tipoSIM = rs.getString("tipoSIM");
                            
                            // generate max ID
                            int maxNum = 1000;
                            try (PreparedStatement sMax = conn.prepareStatement("SELECT codice FROM (SELECT codice FROM SIMAttiva UNION ALL SELECT codice FROM SIMDisattiva UNION ALL SELECT codice FROM SIMNonAttiva) AS t");
                                 ResultSet rsMax = sMax.executeQuery()) {
                                while (rsMax.next()) {
                                    String c = rsMax.getString(1);
                                    java.util.regex.Matcher m = java.util.regex.Pattern.compile("([0-9]+)$").matcher(c);
                                    if (m.find()) {
                                        int num = Integer.parseInt(m.group(1));
                                        if (num > maxNum) maxNum = num;
                                    }
                                }
                            }
                            String codiceAttiva = "SIM-A-" + (maxNum + 1);
                            
                            // check if associataA is already assigned to a SIMAttiva
                            boolean isDupeAssoc = false;
                            try (PreparedStatement sChk = conn.prepareStatement("SELECT codice FROM SIMAttiva WHERE associataA=?")) {
                                sChk.setString(1, associataA);
                                try (ResultSet rsChk = sChk.executeQuery()) {
                                    if (rsChk.next()) isDupeAssoc = true;
                                }
                            }
                            if (isDupeAssoc) {
                                response.sendRedirect(redirectUrl + "?err=duplicate_assoc");
                                return;
                            }
                            
                            // check if contract exists
                            boolean contractExists = false;
                            try (PreparedStatement sContr = conn.prepareStatement("SELECT numero FROM ContrattoTelefonico WHERE numero=?")) {
                                sContr.setString(1, associataA);
                                try (ResultSet rsContr = sContr.executeQuery()) {
                                    if (rsContr.next()) contractExists = true;
                                }
                            }
                            
                            String dataAttivazione = new java.sql.Date(System.currentTimeMillis()).toString();
                            StringBuilder sqlBuilder = new StringBuilder("BEGIN; ");
                            
                            if (!contractExists) {
                                if (tipoContratto.equals("consumo")) {
                                    int minuti = (int) Double.parseDouble(valoreInizialeStr);
                                    sqlBuilder.append("INSERT INTO ContrattoTelefonico (numero, dataAttivazione, tipo, minutiResidui, creditoResiduo) VALUES ('")
                                              .append(sanitize(associataA)).append("', '").append(dataAttivazione).append("', 'consumo', ").append(minuti).append(", NULL); ");
                                } else {
                                    double credito = Double.parseDouble(valoreInizialeStr);
                                    sqlBuilder.append("INSERT INTO ContrattoTelefonico (numero, dataAttivazione, tipo, minutiResidui, creditoResiduo) VALUES ('")
                                              .append(sanitize(associataA)).append("', '").append(dataAttivazione).append("', 'ricarica', NULL, ").append(credito).append("); ");
                                }
                            }
                            
                            sqlBuilder.append("INSERT INTO SIMAttiva (codice, tipoSIM, associataA, dataAttivazione) VALUES ('")
                                      .append(sanitize(codiceAttiva)).append("', '").append(sanitize(tipoSIM)).append("', '").append(sanitize(associataA)).append("', '").append(dataAttivazione).append("'); ");
                            sqlBuilder.append("DELETE FROM SIMNonAttiva WHERE codice='").append(sanitize(codice)).append("'; ");
                            sqlBuilder.append("COMMIT;");
                            
                            savePendingAndRedirect(request, response, sqlBuilder.toString(), "activate", codice, redirectUrl);
                        } else {
                            response.sendRedirect(redirectUrl + "?err=not_found");
                        }
                    }
                }
            }

        } catch (SQLException e) {
            e.printStackTrace();
            response.sendRedirect(redirectUrl + "?err=db");
        }
    }

    private boolean isCodiceValido(Connection conn, String codice, String exclude) throws SQLException {
        String sql = "SELECT codice FROM (SELECT codice FROM SIMAttiva UNION ALL SELECT codice FROM SIMDisattiva UNION ALL SELECT codice FROM SIMNonAttiva) AS t WHERE codice = ?";
        if (exclude != null && !exclude.isEmpty()) {
            sql += " AND codice != ?";
        }
        
        try (PreparedStatement stmt = conn.prepareStatement(sql)) {
            stmt.setString(1, codice);
            if (exclude != null && !exclude.isEmpty()) {
                stmt.setString(2, exclude);
            }
            try (ResultSet rs = stmt.executeQuery()) {
                return !rs.next();
            }
        }
    }

    private boolean contrattoEsiste(Connection conn, String numeroContratto) throws SQLException {
        if (numeroContratto == null || numeroContratto.isEmpty()) return false;
        String sql = "SELECT numero FROM ContrattoTelefonico WHERE numero = ?";
        try (PreparedStatement stmt = conn.prepareStatement(sql)) {
            stmt.setString(1, numeroContratto);
            try (ResultSet rs = stmt.executeQuery()) {
                return rs.next();
            }
        }
    }

    private boolean contrattoGiaAssociato(Connection conn, String numeroContratto, String excludeCodice) throws SQLException {
        if (numeroContratto == null || numeroContratto.isEmpty()) return false;
        String sql = "SELECT codice FROM SIMAttiva WHERE associataA = ?";
        if (excludeCodice != null && !excludeCodice.isEmpty()) {
            sql += " AND codice != ?";
        }
        try (PreparedStatement stmt = conn.prepareStatement(sql)) {
            stmt.setString(1, numeroContratto);
            if (excludeCodice != null && !excludeCodice.isEmpty()) {
                stmt.setString(2, excludeCodice);
            }
            try (ResultSet rs = stmt.executeQuery()) {
                return rs.next();
            }
        }
    }

    private boolean haFormatoValido(String codice, String statoSim) {
        if (codice == null) return false;
        if ("attiva".equals(statoSim)) {
            return codice.matches("^SIM-A-[0-9]+$");
        } else if ("disattiva".equals(statoSim)) {
            return codice.matches("^SIM-D-[0-9]+$");
        } else if ("non_attiva".equals(statoSim)) {
            return codice.matches("^SIM-N-[0-9]+$");
        }
        return false;
    }

    private String sanitize(String input) {
        if (input == null) return "";
        return input.replace("'", "''"); // very basic SQL injection mitigation for concatenated strings
    }

    private void savePendingAndRedirect(HttpServletRequest request, HttpServletResponse response, String sql, String action, String label, String redirectUrl) throws IOException {
        HttpSession session = request.getSession();
        session.setAttribute("has_pending", true);
        session.setAttribute("pending_action", action);
        session.setAttribute("pending_label", label);
        session.setAttribute("pending_sql", sql);
        session.setAttribute("pending_redirect", redirectUrl);

        response.sendRedirect(redirectUrl + "?pending=1");
    }
}
