package it.unifi.progettoweb.servlet;

import it.unifi.progettoweb.dao.ContrattoTelefonicoDAO;
import it.unifi.progettoweb.model.ContrattoTelefonico;
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

@WebServlet(name = "MainServlet", urlPatterns = {"", "/contratti"})
public class MainServlet extends HttpServlet {

    private ContrattoTelefonicoDAO dao;

    @Override
    public void init() throws ServletException {
        super.init();
        ThymeleafConfig.buildTemplateEngine(getServletContext());
        dao = new ContrattoTelefonicoDAO();
    }

    @Override
    protected void doGet(HttpServletRequest request, HttpServletResponse response) throws ServletException, IOException {
        String cercaNum = request.getParameter("cerca-num");
        String cercaTipo = request.getParameter("cerca-tipo");

        int limit = 50;
        int page = 1;
        String pageParam = request.getParameter("page");
        if (pageParam != null && !pageParam.isEmpty()) {
            try {
                page = Integer.parseInt(pageParam);
                if (page < 1) page = 1;
            } catch (NumberFormatException e) {
                page = 1;
            }
        }
        int offset = (page - 1) * limit;

        int totalRows = dao.countContratti(cercaNum, cercaTipo);
        int totalPages = (int) Math.ceil((double) totalRows / limit);

        List<ContrattoTelefonico> contratti = dao.findContratti(cercaNum, cercaTipo, limit, offset);

        response.setContentType("text/html;charset=UTF-8");

        TemplateEngine engine = ThymeleafConfig.getTemplateEngine();
        WebContext ctx = new WebContext(request, response, getServletContext(), request.getLocale());
        
        ctx.setVariable("contratti", contratti);
        ctx.setVariable("cercaNum", cercaNum == null ? "" : cercaNum);
        ctx.setVariable("cercaTipo", cercaTipo == null ? "" : cercaTipo);
        ctx.setVariable("page", page);
        ctx.setVariable("totalRows", totalRows);
        ctx.setVariable("totalPages", totalPages);

        engine.process("index", ctx, response.getWriter());
    }
}
