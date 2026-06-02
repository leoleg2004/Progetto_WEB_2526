package it.unifi.progettoweb.servlet;

import it.unifi.progettoweb.dao.TelefonataDAO;
import it.unifi.progettoweb.model.Telefonata;
import it.unifi.progettoweb.utils.ThymeleafConfig;
import org.thymeleaf.TemplateEngine;
import org.thymeleaf.context.WebContext;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import java.io.IOException;
import java.util.List;

@WebServlet(urlPatterns = {"/telefonate.php"})
public class TelefonataServlet extends HttpServlet {
    
    private TelefonataDAO dao;

    @Override
    public void init() throws ServletException {
        super.init();
        ThymeleafConfig.buildTemplateEngine(getServletContext());
        dao = new TelefonataDAO();
    }

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        request.setCharacterEncoding("UTF-8");
        response.setContentType("text/html;charset=UTF-8");

        String cercaEffettuataDa = request.getParameter("cerca-effettuataDa");
        
        int page = 1;
        int limit = 10;
        
        String pageParam = request.getParameter("page");
        if (pageParam != null && !pageParam.trim().isEmpty()) {
            try {
                page = Integer.parseInt(pageParam);
                if (page < 1) page = 1;
            } catch (NumberFormatException e) {
                page = 1;
            }
        }

        int offset = (page - 1) * limit;

        int totalRows = dao.countTelefonate(cercaEffettuataDa);
        int totalPages = (int) Math.ceil((double) totalRows / limit);

        List<Telefonata> telefonate = dao.findTelefonate(cercaEffettuataDa, limit, offset);

        StringBuilder paramsUrl = new StringBuilder();
        if (cercaEffettuataDa != null && !cercaEffettuataDa.isEmpty()) {
            paramsUrl.append("&cerca-effettuataDa=").append(cercaEffettuataDa);
        }

        WebContext ctx = new WebContext(request, response, getServletContext());
        
        ctx.setVariable("telefonate", telefonate);
        ctx.setVariable("cercaEffettuataDa", cercaEffettuataDa == null ? "" : cercaEffettuataDa);
        ctx.setVariable("page", page);
        ctx.setVariable("totalRows", totalRows);
        ctx.setVariable("totalPages", totalPages);
        ctx.setVariable("paramsUrl", paramsUrl.toString());

        TemplateEngine templateEngine = ThymeleafConfig.getTemplateEngine();
        templateEngine.process("telefonate", ctx, response.getWriter());
    }
}
