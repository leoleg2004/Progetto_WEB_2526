<?php
/**
 * crud_sim.php
 * Gestisce tutte le operazioni CRUD (Create, Read, Update, Delete) sulle SIM.
 *
 * Flusso "Delayed Commit" con sistema di Undo:
 *   1. Il form HTML invia i dati via POST a questo script.
 *   2. La query SQL viene costruita ma NON eseguita subito.
 *   3. La query viene salvata in $_SESSION['pending_op'] insieme ai metadati.
 *   4. L'utente viene rediretto alla pagina con ?pending=1, che mostra il toast di undo.
 *   5. Se l'utente non annulla entro 7s, JavaScript chiama commit.php che esegue la query.
 *   6. Se l'utente clicca "Annulla", JavaScript chiama undo.php che svuota la sessione.
 *   7. Se l'utente dovesse cliccare un altro link nel browser prima dello scadere dei 7 secondi,
 *      viene mandata in background una richiesta per il commit dei dati in modo da salvarli nel database.
 */
require_once 'connessione.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $action = $_POST['action'];
    $stato_sim = isset($_POST['stato_sim']) ? $_POST['stato_sim'] : 'attiva';
    
    // Imposta la pagina di redirect in base allo stato
    if ($stato_sim === 'attiva') $redirect_url = 'index.php';
    elseif ($stato_sim === 'disattiva') $redirect_url = 'sim_disattivate.php';
    elseif ($stato_sim === 'non_attiva') $redirect_url = 'sim_non_attive.php';
    else $redirect_url = 'index.php';
    
    /**
     * Verifica se il codice SIM rispetta il formato richiesto in base allo stato.
     * Formati ammessi:
     * - attiva: SIM-A-numero (es. SIM-A-12345)
     * - disattiva: SIM-D-numero (es. SIM-D-987)
     * - non_attiva: SIM-N-numero (es. SIM-N-001)
     *
     * @param string $codice    Il codice inserito dall'utente.
     * @param string $stato_sim Lo stato della SIM ('attiva', 'disattiva', 'non_attiva').
     * @return bool             true se il formato è corretto, false altrimenti.
     */
    function haFormatoValido($codice, $stato_sim) {
        if ($stato_sim === 'attiva') {
            // Cerca la corrispondenza esatta per SIM-A- seguito da uno o più numeri
            return preg_match('/^SIM-A-[0-9]+$/', $codice);
        } elseif ($stato_sim === 'disattiva') {
            return preg_match('/^SIM-D-[0-9]+$/', $codice);
        } elseif ($stato_sim === 'non_attiva') {
            return preg_match('/^SIM-N-[0-9]+$/', $codice);
        }
        return false;
    }

    /**
     * Verifica che il codice SIM sia univoco tra TUTTE e tre le tabelle SIM.
     * Il vincolo delle chiavi disgiunte impone che lo stesso codice
     * non possa esistere contemporaneamente in SIMAttiva, SIMDisattiva e SIMNonAttiva.
     *
     * @param mysqli $conn     Connessione al database.
     * @param string $codice   Codice da verificare.
     * @param string $exclude  Codice da escludere dal controllo (usato in UPDATE
     *                         per non bloccare il record che si sta modificando).
     * @return bool            true se il codice è disponibile, false se già in uso.
     */
    function isCodiceValido($conn, $codice, $exclude = '') {
        $q_codice = $conn->real_escape_string($codice);
        $q_exclude = $conn->real_escape_string($exclude);
        $where = "WHERE codice = '$q_codice'";
        if ($exclude !== '') {
            // Escludi il record corrente così l'UPDATE non viene bloccato da se stesso
            $where .= " AND codice != '$q_exclude'";
        }
        // Cerca il codice nell'unione delle tre tabelle SIM
        $sql = "SELECT codice FROM (
            SELECT codice FROM SIMAttiva 
            UNION ALL 
            SELECT codice FROM SIMDisattiva 
            UNION ALL 
            SELECT codice FROM SIMNonAttiva
        ) AS tutte_le_sim $where";
        $res = $conn->query($sql);
        if ($res === false) {
            return false;
        }
        
        return ($res->num_rows == 0); // 0 risultati = codice libero
    }

    /**
     * Salva l'operazione CRUD in sessione e redirige con ?pending=1.
     * La query NON viene eseguita qui: sarà commit.php a farlo dopo il timer.
     *
     * @param string $sql          Query SQL pronta da eseguire (già sanificata).
     * @param string $action       Tipo di azione: 'create', 'update' o 'delete'.
     * @param string $stato_sim    Tabella di destinazione: 'attiva', 'disattiva', 'non_attiva'.
     * @param string $label        Codice SIM da mostrare nel messaggio del toast.
     * @param string $redirect_url URL della pagina a cui tornare dopo il commit/undo.
     */
    function savePendingAndRedirect($sql, $action, $stato_sim, $label, $redirect_url) {
        $_SESSION['pending_op'] = [
            'sql'          => $sql,           // Query da eseguire al commit
            'action'       => $action,        // Usato dal toast per il messaggio
            'stato_sim'    => $stato_sim,     // Tabella coinvolta
            'label'        => $label,         // Codice SIM per il messaggio
            'redirect_ok'  => $redirect_url . '?msg=' . $action . 'd', // Redirect dopo commit
            'redirect_undo'=> $redirect_url,  // Redirect dopo annullamento
        ];
        header("Location: $redirect_url?pending=1"); // pending=1 attiva il toast nella pagina
        exit;
    }
    
            // AZIONE: CREATE
    if ($action === 'create') {
        $codice = $_POST['codice']; // Raccogliamo il codice grezzo per la validazione regex
        $tipoSIM = $conn->real_escape_string($_POST['tipoSIM']);
        
        // 1. Controllo del FORMATO del codice (Nuovo controllo)
        if (!haFormatoValido($codice, $stato_sim)) {
            header("Location: $redirect_url?err=invalid_format");
            exit;
        }
        
        // controllo sulla validazione numerica del contratto associato ---
        if ($stato_sim === 'attiva' && isset($_POST['associataA'])) {
            if (!ctype_digit($_POST['associataA'])) {
                header("Location: $redirect_url?err=invalid_contract_format");
                exit;
            }
        } elseif ($stato_sim === 'disattiva' && isset($_POST['eraAssociataA'])) {
            if (!ctype_digit($_POST['eraAssociataA'])) {
                header("Location: $redirect_url?err=invalid_contract_format");
                exit;
            }
        }
        
        // Applichiamo l'escape solo dopo aver validato il formato
        $codice = $conn->real_escape_string($codice);
        
        // 2. Controllo dell'UNIVOCITÀ del codice tra tutte le tabelle SIM
        if (!isCodiceValido($conn, $codice)) {
            header("Location: $redirect_url?err=duplicate_codice");
            exit;
        }
        
        try {
            if ($stato_sim === 'attiva') {
                $associataA = $conn->real_escape_string($_POST['associataA']);
                $dataAttivazione = $conn->real_escape_string($_POST['dataAttivazione']);
                $sql = "INSERT INTO SIMAttiva (codice, tipoSIM, associataA, dataAttivazione) 
                        VALUES ('$codice', '$tipoSIM', '$associataA', '$dataAttivazione')";
            } elseif ($stato_sim === 'disattiva') {
                $eraAssociataA = $conn->real_escape_string($_POST['eraAssociataA']);
                $dataAttivazione = $conn->real_escape_string($_POST['dataAttivazione']);
                $dataDisattivazione = $conn->real_escape_string($_POST['dataDisattivazione']);
                $sql = "INSERT INTO SIMDisattiva (codice, tipoSIM, eraAssociataA, dataAttivazione, dataDisattivazione) 
                        VALUES ('$codice', '$tipoSIM', '$eraAssociataA', '$dataAttivazione', '$dataDisattivazione')";
            } elseif ($stato_sim === 'non_attiva') {
                $sql = "INSERT INTO SIMNonAttiva (codice, tipoSIM) 
                        VALUES ('$codice', '$tipoSIM')";
            }
            
            savePendingAndRedirect($sql, 'create', $stato_sim, htmlspecialchars($codice), $redirect_url);

        } catch (mysqli_sql_exception $e) {
            // Errore 1062: violazione UNIQUE (codice o associazione duplicata)
            if ($e->getCode() == 1062) {
                header("Location: $redirect_url?err=duplicate_assoc");
            // Errore 1452: violazione FOREIGN KEY (contratto associato inesistente)
            } elseif ($e->getCode() == 1452) {
                header("Location: $redirect_url?err=foreign_key");
            } else {
                header("Location: $redirect_url?err=insert_failed");
            }
        }
        exit;
    }
    
        // AZIONE: UPDATE
    if ($action === 'update') {
        $old_codice = $conn->real_escape_string($_POST['old_codice']);
        $codice = $_POST['codice'];
        $tipoSIM = $conn->real_escape_string($_POST['tipoSIM']);
        
        // Controllo del FORMATO del codice in fase di modifica
        if (!haFormatoValido($codice, $stato_sim)) {
            header("Location: $redirect_url?err=invalid_format");
            exit;
        }
        
        // controllo validazione numerica del contratto associato in fase di UPDATE 
        if ($stato_sim === 'attiva' && isset($_POST['associataA'])) {
            if (!ctype_digit($_POST['associataA'])) {
                header("Location: $redirect_url?err=invalid_contract_format");
                exit;
            }
        } elseif ($stato_sim === 'disattiva' && isset($_POST['eraAssociataA'])) {
            if (!ctype_digit($_POST['eraAssociataA'])) {
                header("Location: $redirect_url?err=invalid_contract_format");
                exit;
            }
        }
        
        $codice = $conn->real_escape_string($codice);
        
        if (!isCodiceValido($conn, $codice, $old_codice)) {
            header("Location: $redirect_url?err=duplicate_codice");
            exit;
        }
        
        try {
            if ($stato_sim === 'attiva') {
                $associataA = $conn->real_escape_string($_POST['associataA']);
                $dataAttivazione = $conn->real_escape_string($_POST['dataAttivazione']);
                $sql = "UPDATE SIMAttiva 
                        SET codice='$codice', tipoSIM='$tipoSIM', associataA='$associataA', dataAttivazione='$dataAttivazione' 
                        WHERE codice='$old_codice'";
            } elseif ($stato_sim === 'disattiva') {
                $eraAssociataA = $conn->real_escape_string($_POST['eraAssociataA']);
                $dataAttivazione = $conn->real_escape_string($_POST['dataAttivazione']);
                $dataDisattivazione = $conn->real_escape_string($_POST['dataDisattivazione']);
                $sql = "UPDATE SIMDisattiva 
                        SET codice='$codice', tipoSIM='$tipoSIM', eraAssociataA='$eraAssociataA', dataAttivazione='$dataAttivazione', dataDisattivazione='$dataDisattivazione' 
                        WHERE codice='$old_codice'";
            } elseif ($stato_sim === 'non_attiva') {
                $sql = "UPDATE SIMNonAttiva 
                        SET codice='$codice', tipoSIM='$tipoSIM'
                        WHERE codice='$old_codice'";
            }
            
            savePendingAndRedirect($sql, 'update', $stato_sim, htmlspecialchars($codice), $redirect_url);

        } catch (mysqli_sql_exception $e) {
            // Errore 1062: violazione UNIQUE
            if ($e->getCode() == 1062) {
                header("Location: $redirect_url?err=duplicate_assoc");
            // Errore 1452: violazione FOREIGN KEY
            } elseif ($e->getCode() == 1452) {
                header("Location: $redirect_url?err=foreign_key");
            } else {
                header("Location: $redirect_url?err=update_failed");
            }
        }
        exit;
    }
    
    // AZIONE: DELETE
    if ($action === 'delete') {
        $codice = $conn->real_escape_string($_POST['codice']);
        
        try {
            if ($stato_sim === 'attiva') {
                $sql = "DELETE FROM SIMAttiva WHERE codice='$codice'";
            } elseif ($stato_sim === 'disattiva') {
                $sql = "DELETE FROM SIMDisattiva WHERE codice='$codice'";
            } elseif ($stato_sim === 'non_attiva') {
                $sql = "DELETE FROM SIMNonAttiva WHERE codice='$codice'";
            }

            savePendingAndRedirect($sql, 'delete', $stato_sim, htmlspecialchars($codice), $redirect_url);

        } catch (mysqli_sql_exception $e) {
            header("Location: $redirect_url?err=delete_failed");
        }
        exit;
    }

    // AZIONE: DEACTIVATE (Disattiva SIM Attiva)
    if ($action === 'deactivate') {
        $codice = $conn->real_escape_string($_POST['codice']);
        
        try {
            $sql_select = "SELECT tipoSIM, associataA, dataAttivazione FROM SIMAttiva WHERE codice = '$codice'";
            $res_select = $conn->query($sql_select);
            if ($res_select && $res_select->num_rows > 0) {
                $row = $res_select->fetch_assoc();
                $tipoSIM = $row['tipoSIM'];
                $eraAssociataA = $row['associataA'];
                $dataAttivazione = $row['dataAttivazione'];
                $dataDisattivazione = date('Y-m-d');
                
                // Converti il prefisso del codice da SIM-A- (o SIMA-) a SIM-D- per consistenza
                $codice_disattiva = preg_replace('/^SIM-?A-?/i', 'SIM-D-', $codice);
                $codice_disattiva = $conn->real_escape_string($codice_disattiva);
                
                if (!isCodiceValido($conn, $codice_disattiva, $codice)) {
                    header("Location: $redirect_url?err=duplicate_codice");
                    exit;
                }

                $sql_delete = "DELETE FROM SIMAttiva WHERE codice = '$codice'";
                $sql_insert = "INSERT INTO SIMDisattiva (codice, tipoSIM, eraAssociataA, dataAttivazione, dataDisattivazione) 
                               VALUES ('$codice_disattiva', '$tipoSIM', '$eraAssociataA', '$dataAttivazione', '$dataDisattivazione')";
                
                savePendingAndRedirect([$sql_insert, $sql_delete], 'deactivate', 'attiva', htmlspecialchars($codice), $redirect_url);
            } else {
                header("Location: $redirect_url?err=not_found");
            }
        } catch (mysqli_sql_exception $e) {
            header("Location: $redirect_url?err=deactivate_failed");
        }
        exit;
    }

    // AZIONE: ACTIVATE (Attiva SIM Non Attiva)
    if ($action === 'activate') {
        $codice = $conn->real_escape_string($_POST['codice']);
        $associataA = $conn->real_escape_string($_POST['associataA']);
        $dataAttivazione = $conn->real_escape_string($_POST['dataAttivazione']);
        
        // Nuovi campi per creazione automatica contratto
        $tipo_contratto = isset($_POST['tipo_contratto']) ? $conn->real_escape_string($_POST['tipo_contratto']) : 'ricarica';
        $valore_iniziale = isset($_POST['valore_iniziale']) ? (float)$_POST['valore_iniziale'] : 0.00;
        
        if (!ctype_digit($associataA)) {
            header("Location: $redirect_url?err=invalid_contract_format");
            exit;
        }
        
        try {
            $sql_select = "SELECT tipoSIM FROM SIMNonAttiva WHERE codice = '$codice'";
            $res_select = $conn->query($sql_select);
            if ($res_select && $res_select->num_rows > 0) {
                $row = $res_select->fetch_assoc();
                $tipoSIM = $row['tipoSIM'];
                
                // Generazione di un codice univoco basato sul massimo assoluto nel sistema
                $sql_max = "SELECT codice FROM (
                    SELECT codice FROM SIMAttiva 
                    UNION ALL 
                    SELECT codice FROM SIMDisattiva 
                    UNION ALL 
                    SELECT codice FROM SIMNonAttiva
                ) AS tutte_le_sim";
                $res_max = $conn->query($sql_max);
                $max_num = 1000; // Valore minimo di partenza
                if ($res_max) {
                    while ($row_max = $res_max->fetch_assoc()) {
                        if (preg_match('/([0-9]+)$/', $row_max['codice'], $m)) {
                            $num = (int)$m[1];
                            if ($num > $max_num) {
                                $max_num = $num;
                            }
                        }
                    }
                }
                $nuovo_numero = $max_num + 1;
                $codice_attiva = "SIM-A-" . $nuovo_numero;
                $codice_attiva = $conn->real_escape_string($codice_attiva);
                
                // Verifica se esiste già una SIM attiva su questo contratto (univocità di associataA)
                $sql_chk_assoc = "SELECT codice FROM SIMAttiva WHERE associataA = '$associataA'";
                $res_chk_assoc = $conn->query($sql_chk_assoc);
                if ($res_chk_assoc && $res_chk_assoc->num_rows > 0) {
                    header("Location: $redirect_url?err=duplicate_assoc");
                    exit;
                }
                
                // Verifica se il contratto esiste
                $sql_chk_contr = "SELECT numero FROM ContrattoTelefonico WHERE numero = '$associataA'";
                $res_chk_contr = $conn->query($sql_chk_contr);
                
                $queries = [];
                
                if (!$res_chk_contr || $res_chk_contr->num_rows === 0) {
                    // Il contratto non esiste, lo creiamo automaticamente per consistenza referenziale
                    if ($tipo_contratto === 'consumo') {
                        $minuti = (int)$valore_iniziale;
                        $sql_contr = "INSERT INTO ContrattoTelefonico (numero, dataAttivazione, tipo, minutiResidui, creditoResiduo) 
                                      VALUES ('$associataA', '$dataAttivazione', 'consumo', $minuti, NULL)";
                    } else { // ricarica
                        $credito = number_format($valore_iniziale, 2, '.', '');
                        $sql_contr = "INSERT INTO ContrattoTelefonico (numero, dataAttivazione, tipo, minutiResidui, creditoResiduo) 
                                      VALUES ('$associataA', '$dataAttivazione', 'ricarica', NULL, $credito)";
                    }
                    $queries[] = $sql_contr;
                }

                $queries[] = "INSERT INTO SIMAttiva (codice, tipoSIM, associataA, dataAttivazione) 
                              VALUES ('$codice_attiva', '$tipoSIM', '$associataA', '$dataAttivazione')";
                
                $queries[] = "DELETE FROM SIMNonAttiva WHERE codice = '$codice'";
                
                savePendingAndRedirect($queries, 'activate', 'non_attiva', htmlspecialchars($codice), $redirect_url);
            } else {
                header("Location: $redirect_url?err=not_found");
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                header("Location: $redirect_url?err=duplicate_assoc");
            } elseif ($e->getCode() == 1452) {
                header("Location: $redirect_url?err=foreign_key");
            } else {
                header("Location: $redirect_url?err=activate_failed");
            }
        }
        exit;
    }
}

// Fallback: nessuna action valida ricevuta, torna alla homepage
header("Location: index.php");
exit;
?>
