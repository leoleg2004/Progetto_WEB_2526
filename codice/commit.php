<?php
/**
 * commit.php
 * Eseguito da JavaScript (fetch o sendBeacon) allo scadere del timer di undo.
 * Prende la query salvata in sessione, la esegue sul database e pulisce la sessione.
 */
require_once 'connessione.php';

header('Content-Type: application/json');

if (isset($_SESSION['pending_op']) && !empty($_SESSION['pending_op']['sql'])) {
    $op = $_SESSION['pending_op'];
    try {
        if (is_array($op['sql'])) {
            $conn->begin_transaction();
            foreach ($op['sql'] as $query) {
                $conn->query($query);
            }
            $conn->commit();
        } else {
            $conn->query($op['sql']);
        }
        unset($_SESSION['pending_op']);
        echo json_encode(['ok' => true]);
    } catch (mysqli_sql_exception $e) {
        if (is_array($op['sql'])) {
            try { $conn->rollback(); } catch (Exception $rollEx) {}
        }
        unset($_SESSION['pending_op']);
        echo json_encode(['ok' => false, 'reason' => $e->getMessage()]);
    }
} else {
    // Nessuna operazione pendente (già committata o già annullata)
    echo json_encode(['ok' => false, 'reason' => 'no_pending']);
}
?>
