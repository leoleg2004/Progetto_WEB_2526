<?php
require_once 'connessione.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if (empty($term)) {
    echo json_encode([]);
    exit;
}

$term = $conn->real_escape_string($term);

// Cerca le SIM attive il cui codice contiene il termine cercato
$sql = "SELECT codice, tipoSIM, associataA, dataAttivazione 
        FROM SIMAttiva 
        WHERE codice LIKE '%$term%' 
        ORDER BY codice ASC 
        LIMIT 10";

$result = $conn->query($sql);
$suggestions = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'codice' => $row['codice'],
            'tipoSIM' => $row['tipoSIM'],
            'associataA' => $row['associataA'],
            'dataAttivazione' => $row['dataAttivazione']
        ];
    }
}

echo json_encode($suggestions);
exit;
?>
