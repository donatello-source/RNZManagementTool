<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'rnzmanago');
if ($mysqli->connect_error) {
    die(json_encode(['error' => 'Błąd połączenia z bazą danych']));
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['Imie'], $data['Nazwisko'], $data['NumerTelefonu'], $data['Email'], $data['AdresZamieszkania'], $data['stanowiska'])) {
    echo json_encode(['error' => 'Wypełnij wymagane pola']);
    exit;
}

$employeeId = $_GET['id'] ?? null;
if (!$employeeId) {
    echo json_encode(['error' => 'Brak ID pracownika']);
    exit;
}

$imie = $mysqli->real_escape_string($data['Imie']);
$nazwisko = $mysqli->real_escape_string($data['Nazwisko']);
$telefon = $mysqli->real_escape_string($data['NumerTelefonu']);
$email = $mysqli->real_escape_string($data['Email']);
$adres = $mysqli->real_escape_string($data['AdresZamieszkania']);
$stanowiska = $data['stanowiska'] ?? [];


$result = $mysqli->query("SELECT * FROM osoby WHERE IdOsoba = '$employeeId'");
if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Pracownik o podanym ID nie istnieje']);
    exit;
}

$query = "UPDATE osoby 
          SET Imie = '$imie', 
              Nazwisko = '$nazwisko', 
              NumerTelefonu = '$telefon', 
              Email = '$email', 
              AdresZamieszkania = '$adres' 
          WHERE IdOsoba = '$employeeId'";

if (!$mysqli->query($query)) {
    echo json_encode(['error' => 'Błąd podczas aktualizacji danych osobowych']);
    exit;
}

foreach ($stanowiska as $stanowisko) {
    $idStanowiska = $mysqli->real_escape_string($stanowisko['IdStanowiska']);
    $stawka = $mysqli->real_escape_string($stanowisko['Stawka']);

    $updateStanowiskoQuery = "UPDATE stanowiskoosoba 
                              SET Stawka = '$stawka' 
                              WHERE IdOsoba = '$employeeId' AND IdStanowiska = '$idStanowiska'";

    if (!$mysqli->query($updateStanowiskoQuery)) {
        echo json_encode(['error' => 'Błąd aktualizacji stawki stanowiska']);
        exit;
    }
}

echo json_encode(['message' => 'Dane pracownika zostały zaktualizowane pomyślnie']);
$mysqli->close();
?>