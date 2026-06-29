<?php
require_once 'connessione.php';

$codice = 'SIM-A-9999';
$sql = "SELECT codice FROM (
    SELECT codice FROM SIMAttiva 
    UNION ALL 
    SELECT codice FROM SIMDisattiva 
    UNION ALL 
    SELECT codice FROM SIMNonAttiva
) AS tutte_le_sim WHERE codice = '$codice'";

$res = $conn->query($sql);
if ($res === false) {
    echo "SQL ERROR: " . $conn->error . "\n";
} else {
    echo "Query OK! Found: " . $res->num_rows . " rows.\n";
    if ($res->num_rows == 0) {
        echo "Code $codice is FREE (isCodiceValido returns true).\n";
    } else {
        echo "Code $codice is TAKEN (isCodiceValido returns false).\n";
    }
}
?>
