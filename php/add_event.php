<?php
header('Content-Type: application/json');
$mysqli = new mysqli('localhost', 'root', '', 'rnzmanago');
if ($mysqli->connect_error) die(json_encode(['error' => 'Błąd połączenia']));

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['firma'], $data['nazwaWydarzenia'],$data['miejsce'], $data['data-poczatek'])) {
    echo json_encode(['error' => 'Wypełnij wymagane pola']);
    exit;
}

error_log(json_encode($data));


$firma = $mysqli->real_escape_string($data['firma']);
$miejsce = $mysqli->real_escape_string($data['miejsce']);
$nazwawydarzenia = $mysqli->real_escape_string($data['nazwaWydarzenia']);
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
$query = "INSERT INTO wydarzenia (IdFirma, NazwaWydarzenia, Miejsce, DataPoczatek, DataKoniec, Komentarz) 
          VALUES ('$idFirma', '$nazwawydarzenia','$miejsce', '$dataPoczatek', '$dataKoniec', '$komentarz')";

if (!$mysqli->query($query)) {
    echo json_encode(['error' => 'Błąd podczas dodawania wydarzenia']);
    exit;
}

$idWydarzenia = $mysqli->insert_id;

foreach ($pracownicy as $pracownik) {
    $dniPracownika = $data['dni'][$pracownik] ?? []; // Pobierz dni dla danego pracownika

    if (!empty($dniPracownika)) {
        // Dla każdego dnia dodaj osobny rekord
        foreach ($dniPracownika as $dzien) {
            $dzien = $mysqli->real_escape_string($dzien); // Zabezpieczenie danych
            $mysqli->query("INSERT INTO wydarzeniapracownicy (IdWydarzenia, IdOsoba, Dzien) 
                            VALUES ('$idWydarzenia', '$pracownik', '$dzien')");
        }
    } else {
        // Jeśli brak przypisanych dni, dodaj z Dzien = '0'
        $mysqli->query("INSERT INTO wydarzeniapracownicy (IdWydarzenia, IdOsoba, Dzien) 
                        VALUES ('$idWydarzenia', '$pracownik', '0')");
    }
}

echo json_encode(['message' => 'Wydarzenie zostało utworzone pomyślnie']);
$mysqli->close();
?>