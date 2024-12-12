<?php
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'rnzmanago';
$user = 'root';
$pass = '';

if (isset($_GET['id'])) {
    $firmId = intval($_GET['id']);

    $mysqli = new mysqli($host, $user, $pass, $dbname);
    if ($mysqli->connect_error) {
        die(json_encode(['error' => 'Błąd połączenia z bazą danych']));
    }

    $stmt = $mysqli->prepare("DELETE FROM firma WHERE IdFirma=?");
    $stmt->bind_param('i', $firmId);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Błąd usuwania firmy']);
        exit;
    }

    $mysqli->close();
    echo json_encode(['success' => true, 'message' => 'Pracownik został usunięty']);
} else {
    echo json_encode(['error' => 'Brak parametru ID']);
}
?>