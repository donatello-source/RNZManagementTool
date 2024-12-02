<?php
header('Content-Type: application/json');
$mysqli = new mysqli('localhost', 'root', '', 'rnzmanago');
if ($mysqli->connect_error) die(json_encode(['error' => 'Błąd połączenia']));

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['firma'], $data['nazwaWydarzenia'], $data['miejsce'], $data['data-poczatek'])) {
    echo json_encode(['error' => 'Wypełnij wymagane pola']);
    exit;
}

error_log(json_encode($data));

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $firma = $mysqli->real_escape_string($data['firma']);
    $miejsce = $mysqli->real_escape_string($data['miejsce']);
    $nazwawydarzenia = $mysqli->real_escape_string($data['nazwaWydarzenia']);
    $hotel = $mysqli->real_escape_string($data['hotel']);
    $osobazarzadzajaca = $mysqli->real_escape_string($data['osoba-zarzadzajaca']);
    $dataPoczatek = $data['data-poczatek'];
    $dataKoniec = $data['data-koniec'] ?? $dataPoczatek;
    $komentarz = $mysqli->real_escape_string($data['komentarz'] ?? '');
    $pracownicy = $data['pracownicy'] ?? [];

    $result = $mysqli->query("SELECT * FROM wydarzenia WHERE IdWydarzenia = '$id'");
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Wydarzenie o podanym ID nie istnieje']);
        exit;
    }

    $resultFirma = $mysqli->query("SELECT IdFirma FROM firma WHERE NazwaFirmy = '$firma'");
    if ($resultFirma->num_rows === 0) {
        echo json_encode(['error' => 'Podana firma nie istnieje']);
        exit;
    }

    $idFirma = $resultFirma->fetch_assoc()['IdFirma'];

    $query = "UPDATE wydarzenia 
            SET IdFirma = '$idFirma', 
                Miejsce = '$miejsce', 
                NazwaWydarzenia = '$nazwawydarzenia', 
                DataPoczatek = '$dataPoczatek', 
                DataKoniec = '$dataKoniec', 
                Komentarz = '$komentarz',
                Hotel = '$hotel',
                OsobaZarzadzajaca = '$osobazarzadzajaca'
            WHERE IdWydarzenia = '$id'";

    if (!$mysqli->query($query)) {
        echo json_encode(['error' => 'Błąd podczas aktualizacji wydarzenia']);
        exit;
    }

    $mysqli->query("DELETE FROM wydarzeniapracownicy WHERE IdWydarzenia = '$id'");

    foreach ($pracownicy as $pracownik) {
        $mysqli->query("INSERT INTO wydarzeniapracownicy (IdWydarzenia, IdOsoba) 
                        VALUES ('$id', '$pracownik')");
    }

    echo json_encode(['message' => 'Wydarzenie zostało zaktualizowane pomyślnie']);
}
$mysqli->close();
?>