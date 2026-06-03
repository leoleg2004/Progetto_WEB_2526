package it.unifi.progettoweb.servlet;

import it.unifi.progettoweb.utils.DBConnection;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.io.PrintWriter;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

@WebServlet(urlPatterns = {"/suggerisci_contratto_libero.php"})
public class SuggerisciContrattoLiberoServlet extends HttpServlet {

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        response.setContentType("application/json");
        response.setCharacterEncoding("UTF-8");

        String q = request.getParameter("q");
        if (q == null || q.trim().isEmpty()) {
            response.getWriter().write("[]");
            return;
        }

        String sql = "SELECT c.numero, c.tipo, c.dataAttivazione " +
                     "FROM ContrattoTelefonico c " +
                     "LEFT JOIN SIMAttiva s ON c.numero = s.associataA " +
                     "WHERE s.associataA IS NULL AND c.numero LIKE ? " +
                     "LIMIT 10";

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {

            stmt.setString(1, q + "%");
            
            try (ResultSet rs = stmt.executeQuery(); PrintWriter out = response.getWriter()) {
                out.write("[");
                boolean first = true;
                while (rs.next()) {
                    if (!first) {
                        out.write(",");
                    }
                    out.write("{");
                    out.write("\"numero\":\"" + escapeJson(rs.getString("numero")) + "\",");
                    out.write("\"tipo\":\"" + escapeJson(rs.getString("tipo")) + "\",");
                    out.write("\"dataAttivazione\":\"" + escapeJson(rs.getString("dataAttivazione")) + "\"");
                    out.write("}");
                    first = false;
                }
                out.write("]");
            }
        } catch (SQLException e) {
            e.printStackTrace();
            response.getWriter().write("[]");
        }
    }

    private String escapeJson(String str) {
        if (str == null) return "";
        return str.replace("\\", "\\\\").replace("\"", "\\\"");
    }
}
