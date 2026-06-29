<?php
require_once 'connessione.php';

$cerca_effettuataDa = isset($_GET['cerca-effettuataDa']) ? $conn->real_escape_string($_GET['cerca-effettuataDa']) : '';

$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$where_clause = " WHERE 1=1";
$params_url = "";

if (!empty($cerca_effettuataDa)) {
    $where_clause .= " AND T.effettuataDa LIKE '%$cerca_effettuataDa%'";
    $params_url .= "&cerca-effettuataDa=" . urlencode($_GET['cerca-effettuataDa']);
}

// Conteggio Totale
$sql_count = "SELECT COUNT(*) as totale FROM Telefonata T" . $where_clause;
$r_count = $conn->query($sql_count);
$row_count = $r_count->fetch_assoc();
$total_rows = $row_count['totale'];
$total_pages = ceil($total_rows / $limit);

// Esecuzione Paginata
$sql = "SELECT T.id, T.effettuataDa, T.data, T.ora, T.durata, T.costo, C.tipo 
        FROM Telefonata T 
        JOIN ContrattoTelefonico C ON T.effettuataDa = C.numero 
        " . $where_clause . " 
        ORDER BY T.data DESC, T.ora DESC 
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cerca Telefonate - Progetto Web</title>
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
            <li><a href="contratti.php">Cerca Contratti</a></li>
            <li><a href="telefonate.php" class="active">Cerca Telefonate</a></li>
        </ul>
    </nav>

    <div class="main-container">
        <!-- Interfaccia 1: Filtro Ricerca -->
        <aside class="filtro-ricerca">
            <h2>Filtra Telefonate</h2>
            <form action="telefonate.php" method="GET">
                <div class="form-group">
                    <label for="cerca-effettuataDa">Numero Chiamante:</label>
                    <input type="text" id="cerca-effettuataDa" name="cerca-effettuataDa" placeholder="Es: 3331234567" value="<?php echo htmlspecialchars($cerca_effettuataDa); ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filtra Risultati</button>
                <a href="telefonate.php" class="btn btn-secondary" style="display:block; text-align:center; margin-top:10px; color:var(--text-muted); text-decoration:underline;">Resetta</a>
            </form>
        </aside>

        <!-- Interfaccia 1: Contenuto / Risultati -->
        <main class="contenuto-risultati">
            <div class="header-risultati">
                <div>
                    <h2>Registro Telefonate (Log)</h2>
                    <span style="color:var(--text-muted); font-size: 0.9rem;">Risultati totali: <?php echo number_format($total_rows, 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <?php if ($result && $result->num_rows > 0): ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID. Chiamata</th>
                            <th>Effettuata Da</th>
                            <th>Data e Ora</th>
                            <th>Durata</th>
                            <th>Addebito</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><span style="color:var(--text-muted); font-size:0.85rem;">#<?php echo $row['id']; ?></span></td>
                                <td>
                                    <a href="contratti.php?cerca-num=<?php echo urlencode($row['effettuataDa']); ?>" style="color:var(--brand-active); font-weight:bold;">
                                        <?php echo htmlspecialchars($row['effettuataDa']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['data'])); ?> <span style="color:var(--text-muted); font-size:0.85rem;"><?php echo date('H:i', strtotime($row['ora'])); ?></span></td>
                                <td>
                                    <?php 
                                        $minuti = floor($row['durata'] / 60);
                                        $secondi = $row['durata'] % 60;
                                        echo $minuti . "m " . $secondi . "s";
                                    ?>
                                </td>
                                <td>
                                    <?php if ($row['costo'] > 0): ?>
                                        <strong style="color: #E74C3C;">€ <?php echo number_format($row['costo'], 2); ?></strong>
                                    <?php else: ?>
                                        <span style="color: #2ECC71; font-weight: 500;">Gratis (Piano/Consumo)</span>
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
                <p style="color:var(--text-muted); font-style:italic; margin-top:20px;">Nessuna telefonata registrata.</p>
            <?php endif; ?>
        </main>
    </div>

    <footer class="site-footer"></footer>
</body>
</html>
