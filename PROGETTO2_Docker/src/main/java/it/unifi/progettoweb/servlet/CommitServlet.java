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
import java.sql.Statement;

@WebServlet(urlPatterns = {"/commit.php"})
public class CommitServlet extends HttpServlet {

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        processCommit(request, response);
    }
    
    @Override
    protected void doPost(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        processCommit(request, response);
    }
    
    private void processCommit(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        HttpSession session = request.getSession();
        Boolean hasPending = (Boolean) session.getAttribute("has_pending");
        
        if (hasPending != null && hasPending) {
            String sql = (String) session.getAttribute("pending_sql");
            String action = (String) session.getAttribute("pending_action");
            String redirectUrl = (String) session.getAttribute("pending_redirect");
            
            try (Connection conn = DBConnection.getConnection();
                 Statement stmt = conn.createStatement()) {
                 
                // If it's a multiple statements (like BEGIN; INSERT; DELETE; COMMIT;)
                if (sql.contains(";")) {
                    String[] queries = sql.split(";");
                    for (String q : queries) {
                        if (!q.trim().isEmpty()) {
                            stmt.execute(q.trim());
                        }
                    }
                } else {
                    stmt.executeUpdate(sql);
                }
                
                // Clear session
                session.removeAttribute("has_pending");
                session.removeAttribute("pending_action");
                session.removeAttribute("pending_label");
                session.removeAttribute("pending_sql");
                session.removeAttribute("pending_redirect");
                
                String actionMapping = "";
                if (action.equals("create")) actionMapping = "created";
                else if (action.equals("update")) actionMapping = "updated";
                else if (action.equals("delete")) actionMapping = "deleted";
                else if (action.equals("deactivate")) actionMapping = "deactivated";
                
                response.sendRedirect(redirectUrl + "?msg=" + actionMapping);
                return;
                
            } catch (Exception e) {
                e.printStackTrace();
                session.removeAttribute("has_pending");
                response.sendRedirect(redirectUrl != null ? redirectUrl + "?err=db" : "index.php?err=db");
                return;
            }
        }
        response.sendRedirect("index.php");
    }
}
