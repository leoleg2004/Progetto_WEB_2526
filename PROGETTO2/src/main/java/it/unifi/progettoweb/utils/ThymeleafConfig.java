package it.unifi.progettoweb.utils;

import org.thymeleaf.TemplateEngine;
import org.thymeleaf.templatemode.TemplateMode;
import org.thymeleaf.templateresolver.ServletContextTemplateResolver;

import javax.servlet.ServletContext;

public class ThymeleafConfig {
    private static TemplateEngine templateEngine;

    public static void buildTemplateEngine(ServletContext context) {
        if (templateEngine == null) {
            ServletContextTemplateResolver templateResolver = new ServletContextTemplateResolver(context);
            templateResolver.setTemplateMode(TemplateMode.HTML);
            templateResolver.setPrefix("/WEB-INF/templates/");
            templateResolver.setSuffix(".html");
            templateResolver.setCacheTTLMs(3600000L);
            templateResolver.setCacheable(true);
            templateResolver.setCharacterEncoding("UTF-8");

            templateEngine = new TemplateEngine();
            templateEngine.setTemplateResolver(templateResolver);
        }
    }

    public static TemplateEngine getTemplateEngine() {
        return templateEngine;
    }
}
