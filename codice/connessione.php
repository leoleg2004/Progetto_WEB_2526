<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "leonardounibg"; 
$password = ""; 
$dbname = "my_leonardounibg"; 

try {
    $conn = new mysqli($host, $user, $password);
    $conn->select_db($dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    // Stampiamo una pagina HTML vera e propria con il CSS caricato PRIMA di fermarci!
    echo '<!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Errore Database</title>
        <link rel="stylesheet" href="style.css?v=' . time() . '">
    </head>
    <body style="display:flex; justify-content:center; align-items:center; height:100vh; background:var(--bg-body);">
        <div class="modal-content" style="text-align:center; transform:scale(1);">
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <h3 style="margin-bottom:10px;">⚠️ Errore di Connessione DB</h3>
                <p>Non riesco a connettermi al MySQL.</p>
            </div>
            <p style="color:var(--text-strong); font-weight:bold; margin-bottom:10px;">Dettaglio tecnico:</p>
            <p style="color:var(--text-muted); font-size:0.9rem; padding:15px; background:rgba(0,0,0,0.05); border-radius:10px;">' . htmlspecialchars($e->getMessage()) . '</p>
            <hr style="border:0; border-top:1px solid var(--border-color); margin:20px 0;">
            <p style="color:var(--text-muted); font-size:0.85rem;">Se sei su <b>Altervista</b>, verifica se lo user è corretto. Se sei in <b>Locale (XAMPP)</b> apri <code>connessione.php</code> e imposta l\'utente a <code>root</code>.</p>
        </div>
    </body>
    </html>';
    die();
}
?>