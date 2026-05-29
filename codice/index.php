<?php
// Avvia connessione
require_once 'connessione.php';

// Rilevamento operazione pendente (sistema undo)
$has_pending = isset($_SESSION['pending_op']) && isset($_GET['pending']);
$pending_action = $has_pending ? $_SESSION['pending_op']['action'] : null;
$pending_label  = $has_pending ? $_SESSION['pending_op']['label']  : null;

// Filtro di ricerca opzionale
$cerca_codice = isset($_GET['cerca-codice']) ? $conn->real_escape_string($_GET['cerca-codice']) : '';
$cerca_tipo = isset($_GET['cerca-tipo']) ? $conn->real_escape_string($_GET['cerca-tipo']) : '';

// Parametri di impaginazione
$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Costruzione query Where dinamica per conteggio e recupero
$where_clause = " WHERE 1=1";
$params_url = "";
if (!empty($cerca_codice)) {
    $where_clause .= " AND S.codice LIKE '%$cerca_codice%'";
    $params_url .= "&cerca-codice=" . urlencode($_GET['cerca-codice']);
}
if (!empty($cerca_tipo)) {
    $where_clause .= " AND S.tipoSIM = '$cerca_tipo'";
    $params_url .= "&cerca-tipo=" . urlencode($_GET['cerca-tipo']);
}

// Conteggio Totale Righe per la paginazione
$sql_count = "SELECT COUNT(*) as totale FROM SIMAttiva S" . $where_clause;
$r_count = $conn->query($sql_count);
$row_count = $r_count->fetch_assoc();
$total_rows = $row_count['totale'];
$total_pages = ceil($total_rows / $limit);

// Esegui Query principale
$sql_query = "SELECT S.codice, S.tipoSIM, S.associataA, S.dataAttivazione 
              FROM SIMAttiva S 
              " . $where_clause . " 
              ORDER BY S.dataAttivazione DESC 
              LIMIT $limit OFFSET $offset";

$result = $conn->query($sql_query);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione SIM - Progetto Web</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="site-header">
        <h1>Centro Gestione Contratti</h1>
    </header>

    <nav class="site-nav">
        <ul>
            <li><a href="index.php" class="active">SIM Attive</a></li>
            <li><a href="sim_disattivate.php">SIM Disattivate</a></li>
            <li><a href="sim_non_attive.php">SIM Non Attive</a></li>
            <li><a href="contratti.php">Cerca Contratti</a></li>
            <li><a href="telefonate.php">Cerca Telefonate</a></li>
        </ul>
    </nav>

    <div class="main-container">
        <!-- Interfaccia 1: Filtro Ricerca -->
        <aside class="filtro-ricerca">
            <h2>Filtra SIM</h2>
            <form action="index.php" method="GET">
                <div class="form-group">
                    <label for="cerca-codice">Codice SIM:</label>
                    <input type="text" id="cerca-codice" name="cerca-codice" placeholder="Es: SIM-A-1001" value="<?php echo htmlspecialchars($cerca_codice); ?>">
                </div>
                <div class="form-group">
                    <label for="cerca-tipo">Tipo SIM:</label>
                    <select id="cerca-tipo" name="cerca-tipo">
                        <option value="">Tutti i tipi</option>
                        <option value="Nano" <?php echo ($cerca_tipo == 'Nano') ? 'selected' : ''; ?>>Nano</option>
                        <option value="Micro" <?php echo ($cerca_tipo == 'Micro') ? 'selected' : ''; ?>>Micro</option>
                        <option value="Standard" <?php echo ($cerca_tipo == 'Standard') ? 'selected' : ''; ?>>Standard</option>
                        <option value="eSIM" <?php echo ($cerca_tipo == 'eSIM') ? 'selected' : ''; ?>>eSIM</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtra Risultati</button>
                <a href="index.php" class="btn btn-secondary" style="display:block; text-align:center; margin-top:10px; color:var(--text-muted); text-decoration:underline;">Resetta</a>
            </form>
        </aside>

        <!-- Interfaccia 1: Contenuto / Risultati -->
        <main class="contenuto-risultati">
            <!-- Messaggi di sistema -->
            <?php if (isset($_GET['msg'])): ?>
                <?php if ($_GET['msg'] == 'created'): ?>
                    <div class="alert alert-success">SIM registrata con successo!</div>
                <?php elseif ($_GET['msg'] == 'updated'): ?>
                    <div class="alert alert-success">Dati SIM aggiornati con successo!</div>
                <?php elseif ($_GET['msg'] == 'deleted'): ?>
                    <div class="alert alert-success">SIM eliminata correttamente dal sistema.</div>
                <?php elseif ($_GET['msg'] == 'deactivated'): ?>
                    <div class="alert alert-success">SIM disattivata con successo!</div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['err'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    if ($_GET['err'] == 'duplicate_assoc') echo "Errore: Esiste già una SIM associata a questo contratto (oppure il codice SIM esiste già).";
                    elseif ($_GET['err'] == 'duplicate_codice') echo "Errore: Il codice inserito esiste già in un'altra tabella SIM (Attiva, Disattiva o Non Attiva). I codici devono essere disgiunti.";
                    elseif ($_GET['err'] == 'foreign_key') echo "Errore: Il numero di contratto indicato come associato NON ESISTE! Devi inserire un contratto valido.";
                    elseif ($_GET['err'] == 'deactivate_failed') echo "Errore durante la disattivazione della SIM.";
                    elseif ($_GET['err'] == 'not_found') echo "Errore: SIM non trovata.";
                    elseif ($_GET['err'] == 'insert_failed') echo "Errore durante l'inserimento nel database.";
                    else echo "Si è verificato un errore database.";
                    ?>
                </div>
            <?php endif; ?>

            <div class="header-risultati">
                <div>
                    <h2>Elenco SIM Attive</h2>
                    <span style="color:var(--text-muted); font-size: 0.9rem;">Risultati totali: <?php echo number_format($total_rows, 0, ',', '.'); ?></span>
                </div>
                <!-- Il pulsante "Nuova SIM" rimane in vista grazie al design della card a destra -->
                <button class="btn btn-primary add-btn" onclick="openCreateModal()">+ Nuova SIM</button>
            </div>
            
            <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-wrapper">
                <table class="data-table data-table-attiva">
                    <thead>
                        <tr>
                            <th>Codice SIM</th>
                            <th>Tipo</th>
                            <th>N. Contratto (Ref)</th>
                            <th>Data Attiv.</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['codice']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['tipoSIM']); ?></td>
                                <td>
                                    <a href="contratti.php?cerca-num=<?php echo urlencode($row['associataA']); ?>" style="color:var(--brand-active); font-weight:bold;">
                                        <?php echo htmlspecialchars($row['associataA']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['dataAttivazione'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-sm btn-edit" onclick="openUpdateModal('<?php echo addslashes($row['codice']); ?>', '<?php echo addslashes($row['tipoSIM']); ?>', '<?php echo addslashes($row['associataA']); ?>', '<?php echo $row['dataAttivazione']; ?>')">Modifica</button>
                                        <button class="btn btn-sm btn-deactivate" onclick="openDeactivateModal('<?php echo addslashes($row['codice']); ?>')">Disattiva</button>
                                        <button class="btn btn-sm btn-delete" onclick="openDeleteModal('<?php echo addslashes($row['codice']); ?>')">Elimina</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page-1) . $params_url; ?>">&laquo; Precedente</a>
                <?php endif; ?>
                
                <?php
                // Mostra un range di pagine per evitare di stampare 1000 link
                $start_p = max(1, $page - 2);
                $end_p = min($total_pages, $page + 2);
                
                if ($start_p > 1) { echo "<a href='?page=1$params_url'>1</a>"; if ($start_p > 2) echo "<span class='dots'>...</span>"; }
                for ($i = $start_p; $i <= $end_p; $i++) {
                    $cls = ($i == $page) ? "current" : "";
                    echo "<a class='$cls' href='?page=$i$params_url'>$i</a>";
                }
                if ($end_p < $total_pages) { if ($end_p < $total_pages - 1) echo "<span class='dots'>...</span>"; echo "<a href='?page=$total_pages$params_url'>$total_pages</a>"; }
                ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo ($page+1) . $params_url; ?>">Successiva &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
                <p style="color:var(--text-muted); font-style:italic; margin-top:20px;">Nessuna SIM trovata per i criteri specificati.</p>
            <?php endif; ?>
        </main>
    </div>

    <footer class="site-footer"></footer>

    <!-- MODAL CREATE -->
    <div id="modal-create" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Aggiungi SIM Attiva</h2>
                <button class="close-btn" type="button" onclick="closeModal('modal-create')">&times;</button>
            </div>
            <form action="crud_sim.php" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="stato_sim" value="attiva">
                <div class="form-group">
                    <label>Codice SIM</label>
                        <input type="text" 
                            name="codice" 
                            placeholder="Es: SIM-A-1001" 
                            pattern="^SIM-A-[0-9]+$" 
                            title="Il formato deve essere esplicitamente 'SIM-A-' seguito da uno o più numeri (es. SIM-A-1001)" 
                            required>
                </div>
                <div class="form-group">
                    <label>Tipo SIM</label>
                    <select name="tipoSIM" required>
                        <option value="Nano">Nano</option>
                        <option value="Micro">Micro</option>
                        <option value="Standard">Standard</option>
                        <option value="eSIM">eSIM</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Numero Contratto Associato</label>
                    <input type="text" 
                        name="associataA" 
                        pattern="[0-9]{10}"
                        inputmode="numeric"
                        title="Il numero di contratto deve essere composto da esattamente 10 cifre (es. 3330000001)"
                        placeholder="Es. 3330000001">
                </div>
                <div class="form-group">
                    <label>Data Attivazione</label>
                    <input type="date" name="dataAttivazione" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Salva SIM</button>
            </form>
        </div>
    </div>

    <!-- MODAL UPDATE -->
    <div id="modal-update" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Modifica SIM Attiva</h2>
                <button class="close-btn" type="button" onclick="closeModal('modal-update')">&times;</button>
            </div>
            <form action="crud_sim.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="stato_sim" value="attiva">
                <input type="hidden" name="old_codice" id="upd_old_codice">
                <div class="form-group">
                    <label>Codice SIM</label>
                    <input type="text" 
                        name="codice" 
                        id="upd_codice" 
                        placeholder="Es: SIM-A-1001" 
                        pattern="^SIM-A-[0-9]+$" 
                        title="Il formato deve essere esplicitamente 'SIM-A-' seguito da uno o più numeri (es. SIM-A-1001)" 
                        required>
                </div>
                <div class="form-group">
                    <label>Tipo SIM</label>
                    <select name="tipoSIM" id="upd_tipoSIM" required>
                        <option value="Nano">Nano</option>
                        <option value="Micro">Micro</option>
                        <option value="Standard">Standard</option>
                        <option value="eSIM">eSIM</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Numero Contratto Associato</label>
                    <input type="text" 
                        name="associataA" 
                        id="upd_associataA" 
                        pattern="[0-9]{10}" 
                        inputmode="numeric"
                        title="Il numero di contratto deve essere composto da esattamente 10 cifre (es. 3330000001)" 
                        required 
                        placeholder="Es. 3330000001">
                </div>
                <div class="form-group">
                    <label>Data Attivazione</label>
                    <input type="date" name="dataAttivazione" id="upd_dataAttivazione" required>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Aggiorna Dati</button>
            </form>
        </div>
    </div>

    <!-- MODAL DELETE -->
    <div id="modal-delete" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="color: #E74C3C;">Elimina SIM</h2>
                <button class="close-btn" type="button" onclick="closeModal('modal-delete')">&times;</button>
            </div>
            <form action="crud_sim.php" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="stato_sim" value="attiva">
                <input type="hidden" name="codice" id="del_codice">
                <p style="margin-bottom: 20px;">Sei sicuro di voler eliminare la SIM con codice <strong id="del_codice_display"></strong>? L'operazione non è reversibile.</p>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-edit" style="flex:1;" onclick="closeModal('modal-delete')">Annulla</button>
                    <button type="submit" class="btn btn-delete" style="flex:1;">Conferma Elimina</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DEACTIVATE -->
    <div id="modal-deactivate" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 style="color: #E67E22;">Disattiva SIM</h2>
                <button class="close-btn" type="button" onclick="closeModal('modal-deactivate')">&times;</button>
            </div>
            <form action="crud_sim.php" method="POST">
                <input type="hidden" name="action" value="deactivate">
                <input type="hidden" name="stato_sim" value="attiva">
                <input type="hidden" name="codice" id="deact_codice">
                <p style="margin-bottom: 20px;">Sei sicuro di voler disattivare la SIM con codice <strong id="deact_codice_display"></strong>? Verrà spostata tra le SIM disattivate con data odierna.</p>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-edit" style="flex:1;" onclick="closeModal('modal-deactivate')">Annulla</button>
                    <button type="submit" class="btn btn-primary" style="flex:1; background-color: #E67E22; border-color: #D35400;">Conferma Disattiva</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() { document.getElementById('modal-create').classList.add('active'); }
        function openUpdateModal(codice, tipo, assoc, data) {
            document.getElementById('upd_old_codice').value = codice;
            document.getElementById('upd_codice').value = codice;
            document.getElementById('upd_tipoSIM').value = tipo;
            document.getElementById('upd_associataA').value = assoc;
            document.getElementById('upd_dataAttivazione').value = data;
            document.getElementById('modal-update').classList.add('active');
        }
        function openDeleteModal(codice) {
            document.getElementById('del_codice').value = codice;
            document.getElementById('del_codice_display').innerText = codice;
            document.getElementById('modal-delete').classList.add('active');
        }
        function openDeactivateModal(codice) {
            document.getElementById('deact_codice').value = codice;
            document.getElementById('deact_codice_display').innerText = codice;
            document.getElementById('modal-deactivate').classList.add('active');
        }
        function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('active');
            }
        }
    </script>

    <?php if ($has_pending): ?>
    <!-- ===== UNDO TOAST ===== -->
    <div id="undo-toast" class="undo-toast">
        <div class="undo-toast-progress" id="undo-progress"></div>
        <div class="undo-toast-body">
            <span class="undo-message">
                <?php
                if ($pending_action === 'create')      echo "SIM <strong>$pending_label</strong> creata.";
                elseif ($pending_action === 'update')  echo "SIM <strong>$pending_label</strong> aggiornata.";
                elseif ($pending_action === 'delete')  echo "SIM <strong>$pending_label</strong> eliminata.";
                elseif ($pending_action === 'deactivate') echo "SIM <strong>$pending_label</strong> disattivata.";
                ?>
            </span>
            <button id="undo-btn" class="undo-btn">↩ Annulla</button>
        </div>
    </div>
    <script>
        (function() {
            const UNDO_SECONDS = 7; //timer per annullare l'operazione per modificare o aggiungere una tupla nel database
            let committed = false;

            const toast    = document.getElementById('undo-toast');
            const progress = document.getElementById('undo-progress');
            const undoBtn  = document.getElementById('undo-btn');

            // Mostra il toast con animazione
            requestAnimationFrame(() => {
                toast.classList.add('active');
                // Avvia animazione barra: passa da 100% a 0% in UNDO_SECONDS
                progress.style.transition = 'width ' + UNDO_SECONDS + 's linear';
                requestAnimationFrame(() => { progress.style.width = '0%'; });
            });

            // --- Funzione di commit dell'operazione ---
            function doCommit() {
                if (committed) return;
                committed = true;
                fetch('commit.php').catch(() => {});
                toast.classList.remove('active');
                toast.classList.add('dismissed');
            }

            // Auto-commit allo scadere del timer
            const timer = setTimeout(doCommit, UNDO_SECONDS * 1000);

            // Se l'utente chiude la pagina prima dello scadere dei 7 secondi, esegue il commit dell'operazione
            window.addEventListener('beforeunload', function() {
                if (!committed) {
                    committed = true;
                    clearTimeout(timer);
                    navigator.sendBeacon('commit.php');
                }
            });

            // Undo manuale: clicca il pulsante
            undoBtn.addEventListener('click', function() {
                if (committed) return;
                committed = true;
                clearTimeout(timer);
                // Rimuoviamo il listener beforeunload per non committare mentre navighiamo a undo.php
                window.onbeforeunload = null;
                window.location.href = 'undo.php';
            });
        })();
    </script>
    <?php endif; ?>

</body>
</html>