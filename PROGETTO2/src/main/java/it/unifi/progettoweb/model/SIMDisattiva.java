package it.unifi.progettoweb.model;

import java.sql.Date;

public class SIMDisattiva {
    private String codice;
    private String tipoSIM;
    private String eraAssociataA;
    private Date dataAttivazione;
    private Date dataDisattivazione;

    public String getCodice() { return codice; }
    public void setCodice(String codice) { this.codice = codice; }

    public String getTipoSIM() { return tipoSIM; }
    public void setTipoSIM(String tipoSIM) { this.tipoSIM = tipoSIM; }

    public String getEraAssociataA() { return eraAssociataA; }
    public void setEraAssociataA(String eraAssociataA) { this.eraAssociataA = eraAssociataA; }

    public Date getDataAttivazione() { return dataAttivazione; }
    public void setDataAttivazione(Date dataAttivazione) { this.dataAttivazione = dataAttivazione; }

    public Date getDataDisattivazione() { return dataDisattivazione; }
    public void setDataDisattivazione(Date dataDisattivazione) { this.dataDisattivazione = dataDisattivazione; }
}
