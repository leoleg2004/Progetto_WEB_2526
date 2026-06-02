package it.unifi.progettoweb.servlet;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.HttpSession;

import java.io.IOException;

@WebServlet(urlPatterns = {"/undo.php"})
public class UndoServlet extends HttpServlet {

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        HttpSession session = request.getSession();
        
        String redirectUrl = (String) session.getAttribute("pending_redirect");
        if (redirectUrl == null) redirectUrl = "index.php";

        session.removeAttribute("has_pending");
        session.removeAttribute("pending_action");
        session.removeAttribute("pending_label");
        session.removeAttribute("pending_sql");
        session.removeAttribute("pending_redirect");
        
        response.sendRedirect(redirectUrl);
    }
}
