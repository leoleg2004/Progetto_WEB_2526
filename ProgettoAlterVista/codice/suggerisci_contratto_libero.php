<?php
require_once 'connessione.php';

header('Content-Type: application/json');

if (!isset($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$q = $conn->real_escape_string($_GET['q']);

// Seleziona i contratti che NON sono presenti nella tabella SIMAttiva
$sql = "SELECT c.numero, c.tipo, c.dataAttivazione 
        FROM ContrattoTelefonico c 
        LEFT JOIN SIMAttiva s ON c.numero = s.associataA 
        WHERE s.associataA IS NULL AND c.numero LIKE '$q%'
        LIMIT 10";

$res = $conn->query($sql);
$data = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode($data);
?>
