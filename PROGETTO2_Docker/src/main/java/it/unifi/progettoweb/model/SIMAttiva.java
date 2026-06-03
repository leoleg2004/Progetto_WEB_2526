package it.unifi.progettoweb.model;

import java.sql.Date;

public class SIMAttiva {
    private String codice;
    private String tipoSIM;
    private String associataA;
    private Date dataAttivazione;
    private String numeroAssociato; // join from ContrattoTelefonico

    // Getters and Setters
    public String getCodice() { return codice; }
    public void setCodice(String codice) { this.codice = codice; }

    public String getTipoSIM() { return tipoSIM; }
    public void setTipoSIM(String tipoSIM) { this.tipoSIM = tipoSIM; }

    public String getAssociataA() { return associataA; }
    public void setAssociataA(String associataA) { this.associataA = associataA; }

    public Date getDataAttivazione() { return dataAttivazione; }
    public void setDataAttivazione(Date dataAttivazione) { this.dataAttivazione = dataAttivazione; }

    public String getNumeroAssociato() { return numeroAssociato; }
    public void setNumeroAssociato(String numeroAssociato) { this.numeroAssociato = numeroAssociato; }
}
