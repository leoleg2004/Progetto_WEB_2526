package it.unifi.progettoweb.model;

import java.sql.Date;

public class ContrattoTelefonico {
    private String numero;
    private Date dataAttivazione;
    private String tipo;
    private Integer minutiResidui;
    private Double creditoResiduo;

    // Aggiungo campi per replicare i dati aggregati della UI in contratti.php
    private int numChiamate;
    private String simAttiva;
    private String simDisattivate;

    public ContrattoTelefonico() {
    }

    public String getNumero() {
        return numero;
    }

    public void setNumero(String numero) {
        this.numero = numero;
    }

    public Date getDataAttivazione() {
        return dataAttivazione;
    }

    public void setDataAttivazione(Date dataAttivazione) {
        this.dataAttivazione = dataAttivazione;
    }

    public String getTipo() {
        return tipo;
    }

    public void setTipo(String tipo) {
        this.tipo = tipo;
    }

    public Integer getMinutiResidui() {
        return minutiResidui;
    }

    public void setMinutiResidui(Integer minutiResidui) {
        this.minutiResidui = minutiResidui;
    }

    public Double getCreditoResiduo() {
        return creditoResiduo;
    }

    public void setCreditoResiduo(Double creditoResiduo) {
        this.creditoResiduo = creditoResiduo;
    }

    public int getNumChiamate() {
        return numChiamate;
    }

    public void setNumChiamate(int numChiamate) {
        this.numChiamate = numChiamate;
    }

    public String getSimAttiva() {
        return simAttiva;
    }

    public void setSimAttiva(String simAttiva) {
        this.simAttiva = simAttiva;
    }

    public String getSimDisattivate() {
        return simDisattivate;
    }

    public void setSimDisattivate(String simDisattivate) {
        this.simDisattivate = simDisattivate;
    }
}
