<?php
header('Content-Type: application/json');
$mysqli = new mysqli('localhost', 'root', '', 'rnzmanago');
if ($mysqli->connect_error) die(json_encode(['error' => 'Błąd połączenia']));

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['firma'], $data['miejsce'], $data['data-poczatek'])) {
    echo json_encode(['error' => 'Wypełnij wymagane pola']);
    exit;
}

error_log(json_encode($data));


$firma = $mysqli->real_escape_string($data['firma']);
$miejsce = $mysqli->real_escape_string($data['miejsce']);
$dataPoczatek = $data['data-poczatek'];
$dataKoniec = $data['data-koniec'] ?? $dataPoczatek;
$komentarz = $mysqli->real_escape_string($data['komentarz'] ?? '');
$pracownicy = $data['pracownicy'] ?? [];

// Pobierz ID firmy
$result = $mysqli->query("SELECT IdFirma FROM firma WHERE NazwaFirmy = '$firma'");
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Podana firma nie istnieje']);
    exit;
}

$idFirma = $result->fetch_assoc()['IdFirma'];

// Dodaj wydarzenie
$query = "INSERT INTO wydarzenia (IdFirma, Miejsce, DataPoczatek, DataKoniec, Komentarz) 
          VALUES ('$idFirma', '$miejsce', '$dataPoczatek', '$dataKoniec', '$komentarz')";

if (!$mysqli->query($query)) {
    echo json_encode(['error' => 'Błąd podczas dodawania wydarzenia']);
    exit;
}

$idWydarzenia = $mysqli->insert_id;

// Dodaj pracowników
foreach ($pracownicy as $pracownik) {
    $mysqli->query("INSERT INTO wydarzeniapracownicy (IdWydarzenia, IdOsoba) 
                    VALUES ('$idWydarzenia', '$pracownik')");
}

echo json_encode(['message' => 'Wydarzenie zostało utworzone pomyślnie']);
$mysqli->close();
?>