package it.unifi.progettoweb.model;

import java.sql.Date;
import java.sql.Time;

public class Telefonata {
    private String id;
    private String effettuataDa;
    private Date data;
    private Time ora;
    private Integer durata; // in secondi
    private double costo;

    public String getId() { return id; }
    public void setId(String id) { this.id = id; }

    public String getEffettuataDa() { return effettuataDa; }
    public void setEffettuataDa(String effettuataDa) { this.effettuataDa = effettuataDa; }
    public java.sql.Date getData() { return data; }
    public void setData(java.sql.Date data) { this.data = data; }

    public java.sql.Time getOra() { return ora; }
    public void setOra(java.sql.Time ora) { this.ora = ora; }

    public int getDurata() { return durata; }
    public void setDurata(int durata) { this.durata = durata; }

    public double getCosto() { return costo; }
    public void setCosto(double costo) { this.costo = costo; }
}
