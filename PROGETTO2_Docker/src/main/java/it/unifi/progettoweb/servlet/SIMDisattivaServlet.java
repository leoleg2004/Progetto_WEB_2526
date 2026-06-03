package it.unifi.progettoweb.servlet;

import it.unifi.progettoweb.dao.SIMDisattivaDAO;
import it.unifi.progettoweb.model.SIMDisattiva;
import it.unifi.progettoweb.utils.ThymeleafConfig;
import org.thymeleaf.TemplateEngine;
import org.thymeleaf.context.WebContext;

import javax.servlet.ServletException;
import javax.servlet.annotation.WebServlet;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;
import javax.servlet.http.HttpSession;

import java.io.IOException;
import java.util.List;

@WebServlet(urlPatterns = {"/sim_disattivate.php"})
public class SIMDisattivaServlet extends HttpServlet {
    
    private SIMDisattivaDAO dao;

    @Override
    public void init() throws ServletException {
        super.init();
        ThymeleafConfig.buildTemplateEngine(getServletContext());
        dao = new SIMDisattivaDAO();
    }

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        request.setCharacterEncoding("UTF-8");
        response.setContentType("text/html;charset=UTF-8");

        String cercaCodice = request.getParameter("cerca-codice");
        
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

        int totalRows = dao.countSimDisattivate(cercaCodice);
        int totalPages = (int) Math.ceil((double) totalRows / limit);

        List<SIMDisattiva> sim = dao.findSimDisattivate(cercaCodice, limit, offset);

        StringBuilder paramsUrl = new StringBuilder();
        if (cercaCodice != null && !cercaCodice.isEmpty()) {
            paramsUrl.append("&cerca-codice=").append(cercaCodice);
        }

        WebContext ctx = new WebContext(request, response, getServletContext());
        
        ctx.setVariable("sim", sim);
        ctx.setVariable("cercaCodice", cercaCodice == null ? "" : cercaCodice);
        ctx.setVariable("page", page);
        ctx.setVariable("totalRows", totalRows);
        ctx.setVariable("totalPages", totalPages);
        ctx.setVariable("paramsUrl", paramsUrl.toString());

        HttpSession session = request.getSession();
        Boolean hasPending = (Boolean) session.getAttribute("has_pending");
        if (hasPending != null && hasPending) {
            ctx.setVariable("has_pending", true);
            ctx.setVariable("pending_action", session.getAttribute("pending_action"));
            ctx.setVariable("pending_label", session.getAttribute("pending_label"));
        } else {
            ctx.setVariable("has_pending", false);
        }

        TemplateEngine templateEngine = ThymeleafConfig.getTemplateEngine();
        templateEngine.process("sim_disattivate", ctx, response.getWriter());
    }
}
