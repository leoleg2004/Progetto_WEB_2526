FROM maven:3.9-eclipse-temurin-21 AS build
WORKDIR /app
COPY pom.xml .
# Esegue un download preventivo delle dipendenze per usare la cache di Docker
RUN mvn dependency:go-offline

COPY src ./src
RUN mvn clean package -DskipTests

# Stage 2: Esecuzione su Tomcat
FROM tomcat:9-jdk21
# Rimuove le app di default di Tomcat per un ambiente più pulito
RUN rm -rf /usr/local/tomcat/webapps/*
COPY --from=build /app/target/progetto-web.war /usr/local/tomcat/webapps/
EXPOSE 8080
CMD ["catalina.sh", "run"]
