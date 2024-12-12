<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'rnzmanago');
if ($mysqli->connect_error) {
    die(json_encode(['error' => 'Błąd połączenia z bazą danych']));
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['NazwaFirmy'])) {
    echo json_encode(['error' => 'Wypełnij wymagane pola']);
    exit;
}

$firmId = $_GET['id'] ?? null;
if (!$firmId) {
    echo json_encode(['error' => 'Brak ID firmy']);
    exit;
}

$nazwaFirmy = $mysqli->real_escape_string($data['NazwaFirmy']);
$adresFirmy = $mysqli->real_escape_string($data['AdresFirmy']);
$telefon = $mysqli->real_escape_string($data['Telefon']);
$NIP = $mysqli->real_escape_string($data['NIP']);
$kolor = $mysqli->real_escape_string($data['kolor']);


$result = $mysqli->query("SELECT * FROM firma WHERE IdFirma = '$firmId'");
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Firma o podanym ID nie istnieje']);
    exit;
}

$query = "UPDATE firma 
          SET NazwaFirmy = '$nazwaFirmy', 
              AdresFirmy = '$adresFirmy', 
              Telefon = '$telefon', 
              NIP = '$NIP', 
              kolor = '$kolor' 
          WHERE IdFirma = '$firmId'";

if (!$mysqli->query($query)) {
    echo json_encode(['error' => 'Błąd podczas aktualizacji danych firmy']);
    exit;
}

echo json_encode(['message' => 'Dane firmy zostały zaktualizowane pomyślnie']);
$mysqli->close();
?>