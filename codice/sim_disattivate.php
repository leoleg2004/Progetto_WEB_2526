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
$sql_count = "SELECT COUNT(*) as totale FROM SIMDisattiva S" . $where_clause;
$r_count = $conn->query($sql_count);
$row_count = $r_count->fetch_assoc();
$total_rows = $row_count['totale'];
$total_pages = ceil($total_rows / $limit);

// Esegui Query principale
$sql_query = "SELECT S.codice, S.tipoSIM, S.eraAssociataA, S.dataAttivazione, S.dataDisattivazione 
              FROM SIMDisattiva S 
              " . $where_clause . " 
              ORDER BY S.dataDisattivazione DESC 
              LIMIT $limit OFFSET $offset";

$result = $conn->query($sql_query);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione SIM Disattivate - Progetto Web</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="site-header">
        <h1>Centro Gestione Contratti</h1>
    </header>

    <nav class="site-nav">
        <ul>
            <li><a href="index.php">SIM Attive</a></li>
            <li><a href="sim_disattivate.php" class="active">SIM Disattivate</a></li>
            <li><a href="sim_non_attive.php">SIM Non Attive</a></li>
            <li><a href="contratti.php">Cerca Contratti</a></li>
            <li><a href="telefonate.php">Cerca Telefonate</a></li>
        </ul>
    </nav>

    <div class="main-container">
        <!-- Interfaccia 1: Filtro Ricerca -->
        <aside class="filtro-ricerca">
            <h2>Filtra SIM</h2>
            <form action="sim_disattivate.php" method="GET">
                <div class="form-group">
                    <label for="cerca-codice">Codice SIM:</label>
                    <input type="text" id="cerca-codice" name="cerca-codice" placeholder="Es: SIM-D-1001" value="<?php echo htmlspecialchars($cerca_codice); ?>">
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
                <a href="sim_disattivate.php" class="btn btn-secondary" style="display:block; text-align:center; margin-top:10px; color:var(--text-muted); text-decoration:underline;">Resetta</a>
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
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($_GET['err'])): ?>
                <div style="margin-bottom: 20px;">
            <?php if ($_GET['err'] === 'invalid_format'): ?>
                <div class="alert alert-danger" style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;">
                    <strong>⚠️ Errore di Formato:</strong> Il codice inserito non rispetta le specifiche stabilite in base allo stato della SIM.
                    <br><small>Usa <code>SIM-A-numero</code> per le Attive, <code>SIM-D-numero</code> per le Disattivate, e <code>SIM-N-numero</code> per le Non Attive.</small>
                </div>
            <?php elseif ($_GET['err'] === 'duplicate_codice'): ?>
                <div class="alert alert-danger">Il codice SIM inserito è già esistente.</div>
            <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="header-risultati">
                <div>
                    <h2>Elenco SIM Disattivate</h2>
                    <span style="color:var(--text-muted); font-size: 0.9rem;">Risultati totali: <?php echo number_format($total_rows, 0, ',', '.'); ?></span>
                </div>
                <button class="btn btn-primary add-btn" onclick="openCreateModal()">+ Nuova SIM Disattiva</button>
            </div>
            
            <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-wrapper">
                <table class="data-table data-table-disattiva">
                    <thead>
                        <tr>
                            <th>Codice SIM</th>
                            <th>Tipo</th>
                            <th>Ex-Contratto (Ref)</th>
                            <th>Data Attiv.</th>
                            <th>Data Disattiv.</th>
                            <th>Azioni (CRUD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['codice']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['tipoSIM']); ?></td>
                                <td>
                                    <a href="contratti.php?cerca-num=<?php echo urlencode($row['eraAssociataA']); ?>" style="color:var(--brand-active); font-weight:bold;">
                                        <?php echo htmlspecialchars($row['eraAssociataA']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['dataAttivazione'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['dataDisattivazione'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-sm btn-edit" onclick="openUpdateModal('<?php echo addslashes($row['codice']); ?>', '<?php echo addslashes($row['tipoSIM']); ?>', '<?php echo addslashes($row['eraAssociataA']); ?>', '<?php echo $row['dataAttivazione']; ?>', '<?php echo $row['dataDisattivazione']; ?>')">Modifica</button>
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
                <h2>Aggiungi SIM Disattiva</h2>
                <button class="close-btn" type="button" onclick="closeModal('modal-create')">&times;</button>
            </div>
            <form action="crud_sim.php" method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="stato_sim" value="disattiva">
                <div class="form-group">
                    <label>Codice SIM</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" 
                           name="codice" 
                           id="create_codice"
                           placeholder="Es: SIM-D-1001" 
                           pattern="^SIM-D-[0-9]+$" 
                           title="Il formato deve essere esplicitamente 'SIM-D-' seguito da uno o più numeri (es. SIM-D-1001)" 
                           required
                           autocomplete="off">
                        <div id="suggestions-box" class="autocomplete-suggestions" style="display: none;"></div>
                    </div>
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
                    <label>Numero Contratto Ex-Associato</label>
                    <input type="text" 
                        name="eraAssociataA" 
                        pattern="[0-9]{10}" 
                        inputmode="numeric"
                        title="Il numero di contratto deve essere composto da esattamente 10 cifre (es. 3330000001)" 
                        required 
                        placeholder="Es. 3330000001">
                </div>
                <div class="form-group">
                    <label>Data Attivazione</label>
                    <input type="date" name="dataAttivazione" required value="<?php echo date('Y-m-d', strtotime('-1 year')); ?>">
                </div>
                <div class="form-group">
                    <label>Data Disattivazione</label>
                    <input type="date" name="dataDisattivazione" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 10px;">Salva SIM</button>
            </form>
        </div>
    </div>

    <!-- MODAL UPDATE -->
    <div id="modal-update" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Modifica SIM Disattiva</h2>
                <button class="close-btn" type="button" onclick="closeModal('modal-update')">&times;</button>
            </div>
            <form action="crud_sim.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="stato_sim" value="disattiva">
                <input type="hidden" name="old_codice" id="upd_old_codice">
                <div class="form-group">
                    <label>Codice SIM</label>
                    <input type="text" 
                        name="codice" 
                        id="upd_codice" 
                        placeholder="Es: SIM-D-1001" 
                        pattern="^SIM-D-[0-9]+$" 
                        title="Il formato deve essere esplicitamente 'SIM-D-' seguito da uno o più numeri (es. SIM-D-1001)" 
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
                    <label>Numero Contratto Ex-Associato</label>
                    <input type="text" 
                        name="eraAssociataA" 
                        id="upd_eraAssociataA" 
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
                <div class="form-group">
                    <label>Data Disattivazione</label>
                    <input type="date" name="dataDisattivazione" id="upd_dataDisattivazione" required>
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
                <input type="hidden" name="stato_sim" value="disattiva">
                <input type="hidden" name="codice" id="del_codice">
                <p style="margin-bottom: 20px;">Sei sicuro di voler eliminare la SIM con codice <strong id="del_codice_display"></strong>? L'operazione non è reversibile.</p>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn btn-edit" style="flex:1;" onclick="closeModal('modal-delete')">Annulla</button>
                    <button type="submit" class="btn btn-delete" style="flex:1;">Conferma Elimina</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() { document.getElementById('modal-create').classList.add('active'); }
        function openUpdateModal(codice, tipo, assoc, dataA, dataD) {
            document.getElementById('upd_old_codice').value = codice;
            document.getElementById('upd_codice').value = codice;
            document.getElementById('upd_tipoSIM').value = tipo;
            document.getElementById('upd_eraAssociataA').value = assoc;
            document.getElementById('upd_dataAttivazione').value = dataA;
            document.getElementById('upd_dataDisattivazione').value = dataD;
            document.getElementById('modal-update').classList.add('active');
        }
        function openDeleteModal(codice) {
            document.getElementById('del_codice').value = codice;
            document.getElementById('del_codice_display').innerText = codice;
            document.getElementById('modal-delete').classList.add('active');
        }
        function closeModal(modalId) { document.getElementById(modalId).classList.remove('active'); }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('active');
            }
        }

        // Script Autocomplete per l'inserimento manuale delle SIM Disattivate
        const createCodiceInput = document.getElementById('create_codice');
        const suggestionsBox = document.getElementById('suggestions-box');
        const createTipoSIM = document.querySelector('#modal-create select[name="tipoSIM"]');
        const createEraAssociataA = document.querySelector('#modal-create input[name="eraAssociataA"]');
        const createDataAttivazione = document.querySelector('#modal-create input[name="dataAttivazione"]');

        createCodiceInput.addEventListener('input', function() {
            const term = this.value.trim();
            if (term.length < 2) {
                suggestionsBox.innerHTML = '';
                suggestionsBox.style.display = 'none';
                return;
            }

            fetch('suggerisci_sim.php?term=' + encodeURIComponent(term))
                .then(response => response.json())
                .then(data => {
                    suggestionsBox.innerHTML = '';
                    if (data.length === 0) {
                        suggestionsBox.style.display = 'none';
                        return;
                    }

                    data.forEach(item => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-suggestion';
                        div.innerHTML = `<strong>${item.codice}</strong> (Contratto: ${item.associataA})`;
                        div.addEventListener('click', function() {
                            // Converti codice da SIM-A-xxx a SIM-D-xxx
                            const codeD = item.codice.replace(/^SIM-?A-?/i, 'SIM-D-');
                            createCodiceInput.value = codeD;
                            createTipoSIM.value = item.tipoSIM;
                            createEraAssociataA.value = item.associataA;
                            createDataAttivazione.value = item.dataAttivazione;
                            
                            suggestionsBox.innerHTML = '';
                            suggestionsBox.style.display = 'none';
                        });
                        suggestionsBox.appendChild(div);
                    });
                    suggestionsBox.style.display = 'block';
                })
                .catch(err => {
                    console.error('Errore durante il recupero dei suggerimenti:', err);
                });
        });

        // Chiudi i suggerimenti al click esterno
        document.addEventListener('click', function(e) {
            if (e.target !== createCodiceInput && e.target !== suggestionsBox) {
                suggestionsBox.style.display = 'none';
            }
        });
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
            const UNDO_SECONDS = 7;
            let committed = false;

            const toast    = document.getElementById('undo-toast');
            const progress = document.getElementById('undo-progress');
            const undoBtn  = document.getElementById('undo-btn');

            // Mostra il toast con animazione
            requestAnimationFrame(() => {
                toast.classList.add('active');
                progress.style.transition = 'width ' + UNDO_SECONDS + 's linear';
                requestAnimationFrame(() => { progress.style.width = '0%'; });
            });

            // --- Funzione di commit ---
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
                window.onbeforeunload = null;
                window.location.href = 'undo.php';
            });
        })();
    </script>
    <?php endif; ?>

</body>
</html>
