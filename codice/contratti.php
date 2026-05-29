<?php
require_once 'connessione.php';

$cerca_num = isset($_GET['cerca-num']) ? $conn->real_escape_string($_GET['cerca-num']) : '';
$cerca_tipo = isset($_GET['cerca-tipo']) ? $conn->real_escape_string($_GET['cerca-tipo']) : '';

$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$where_clause = " WHERE 1=1";
$params_url = "";

if (!empty($cerca_num)) {
    $where_clause .= " AND C.numero LIKE '%$cerca_num%'";
    $params_url .= "&cerca-num=" . urlencode($_GET['cerca-num']);
}
if (!empty($cerca_tipo)) {
    $where_clause .= " AND C.tipo = '$cerca_tipo'";
    $params_url .= "&cerca-tipo=" . urlencode($_GET['cerca-tipo']);
}

// Conteggio Totale
$sql_count = "SELECT COUNT(*) as totale FROM ContrattoTelefonico C" . $where_clause;
$r_count = $conn->query($sql_count);
$row_count = $r_count->fetch_assoc();
$total_rows = $row_count['totale'];
$total_pages = ceil($total_rows / $limit);

// Esecuzione Dati Completa di subquery
$sql = "SELECT C.numero, C.dataAttivazione, C.tipo, C.minutiResidui, C.creditoResiduo,
        (SELECT COUNT(*) FROM Telefonata T WHERE T.effettuataDa = C.numero) as num_chiamate,
        (SELECT codice FROM SIMAttiva WHERE associataA = C.numero LIMIT 1) as sim_attiva,
        (SELECT GROUP_CONCAT(codice SEPARATOR ', ') FROM SIMDisattiva WHERE eraAssociataA = C.numero) as sim_disattivate
        FROM ContrattoTelefonico C 
        " . $where_clause . "
        ORDER BY C.dataAttivazione DESC 
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerca Contratti - Progetto Web</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="site-header">
        <h1>Centro Gestione Contratti</h1>
    </header>

    <nav class="site-nav">
        <ul>
            <li><a href="index.php">SIM Attive</a></li>
            <li><a href="sim_disattivate.php">SIM Disattivate</a></li>
            <li><a href="sim_non_attive.php">SIM Non Attive</a></li>
            <li><a href="contratti.php" class="active">Cerca Contratti</a></li>
            <li><a href="telefonate.php">Cerca Telefonate</a></li>
        </ul>
    </nav>

    <div class="main-container">
        <!-- Interfaccia 1: Filtro Ricerca -->
        <aside class="filtro-ricerca">
            <h2>Filtra Contratti</h2>
            <form action="contratti.php" method="GET">
                <div class="form-group">
                    <label for="cerca-num">Numero Telefono:</label>
                    <input type="text" id="cerca-num" name="cerca-num" placeholder="Es: 3331234567" value="<?php echo htmlspecialchars($cerca_num); ?>">
                </div>
                <div class="form-group">
                    <label for="cerca-tipo">Tipo Contratto:</label>
                    <select id="cerca-tipo" name="cerca-tipo">
                        <option value="">Tutti</option>
                        <option value="ricarica" <?php echo ($cerca_tipo == 'ricarica') ? 'selected' : ''; ?>>Ricarica</option>
                        <option value="consumo" <?php echo ($cerca_tipo == 'consumo') ? 'selected' : ''; ?>>Consumo</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtra Risultati</button>
                <a href="contratti.php" class="btn btn-secondary" style="display:block; text-align:center; margin-top:10px; color:var(--text-muted); text-decoration:underline;">Resetta</a>
            </form>
        </aside>

        <!-- Interfaccia 1: Contenuto / Risultati -->
        <main class="contenuto-risultati">
            <div class="header-risultati">
                <div>
                    <h2>Elenco Contratti Erogati</h2>
                    <span style="color:var(--text-muted); font-size: 0.9rem;">Risultati totali: <?php echo number_format($total_rows, 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <?php if ($result && $result->num_rows > 0): ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Num. Telefono</th>
                            <th>Data Attiv.</th>
                            <th>Piano</th>
                            <th>Credito/Minuti</th>
                            <th>SIM Associata (Attiva / Storico)</th>
                            <th>Azioni (Chiamate)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['numero']); ?></strong></td>
                                <td><?php echo date('d/m/Y', strtotime($row['dataAttivazione'])); ?></td>
                                <td><span style="font-size:0.85rem; padding:4px 8px; border-radius:4px; background:var(--bg-body);"><?php echo htmlspecialchars(ucfirst($row['tipo'])); ?></span></td>
                                <td>
                                    <?php 
                                    if ($row['tipo'] == 'ricarica') {
                                        echo "€ " . number_format($row['creditoResiduo'], 2);
                                    } else {
                                        echo htmlspecialchars($row['minutiResidui']) . " min";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['sim_attiva'])): ?>
                                        <div style="margin-bottom: 5px;">
                                            <a href="index.php?cerca-codice=<?php echo urlencode($row['sim_attiva']); ?>" style="text-decoration:none;">
                                                <span style="font-size:0.85rem; padding:4px 8px; border-radius:4px; background:var(--brand-color); color:black; font-weight: 500; display:inline-block;" title="Vai alla SIM Attiva">
                                                    <?php echo htmlspecialchars($row['sim_attiva']); ?> (Attiva)
                                                </span>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($row['sim_disattivate'])): ?>
                                        <?php 
                                        $disattivate = explode(', ', $row['sim_disattivate']);
                                        foreach ($disattivate as $sim_dis):
                                        ?>
                                        <div style="margin-bottom: 3px;">
                                            <a href="sim_disattivate.php?cerca-codice=<?php echo urlencode($sim_dis); ?>" style="text-decoration:none;">
                                                <span style="font-size:0.85rem; padding:4px 8px; border-radius:4px; background:#e0e0e0; color:#555; font-weight: 500; display:inline-block;" title="Vai alla SIM Disattivata">
                                                    <?php echo htmlspecialchars($sim_dis); ?> (Disattivata)
                                                </span>
                                            </a>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (empty($row['sim_attiva']) && empty($row['sim_disattivate'])): ?>
                                        <span style="color:var(--text-muted); font-size:0.85rem; font-style:italic;">Nessuna SIM trovata</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['num_chiamate'] > 0): ?>
                                        <a href="telefonate.php?cerca-effettuataDa=<?php echo urlencode($row['numero']); ?>" class="btn btn-sm btn-edit">Vedi (<?php echo $row['num_chiamate']; ?>) Chiamate</a>
                                    <?php else: ?>
                                        <span style="color:var(--text-muted); font-size:0.85rem; font-weight:500; padding:8px 0; display:inline-block;">Nessuna chiamata</span>
                                    <?php endif; ?>
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
                <p style="color:var(--text-muted); font-style:italic; margin-top:20px;">Nessun contratto trovato.</p>
            <?php endif; ?>
        </main>
    </div>

    <footer class="site-footer"></footer>
</body>
</html>
