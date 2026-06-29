<?php
/**
 * undo.php
 * Chiamato da JS quando l'utente clicca "Annulla" nel toast.
 * NON esegue la query: svuota semplicemente la sessione e redirige alla pagina originale.
 */
require_once 'connessione.php';

$redirect = isset($_SESSION['pending_op']['redirect_undo'])
    ? $_SESSION['pending_op']['redirect_undo']
    : 'index.php';

unset($_SESSION['pending_op']);

header("Location: $redirect");
exit;
?>
