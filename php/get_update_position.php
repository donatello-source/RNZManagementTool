<?php
header('Content-Type: application/json');
$host = 'localhost';
$dbname = 'rnzmanago';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $dbname);

if ($mysqli->connect_error) {
    die(json_encode(['error' => 'Błąd połączenia z bazą danych']));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Pobranie danych do tabeli
    $queryPracownicy = "SELECT o.IdOsoba, o.Imie, o.Nazwisko FROM osoby o";
    $queryStanowiska = "SELECT IdStanowiska, NazwaStanowiska FROM stanowiska";
    $queryPowiazania = "SELECT IdOsoba, IdStanowiska FROM stanowiskoosoba";

    $pracownicy = $mysqli->query($queryPracownicy)->fetch_all(MYSQLI_ASSOC);
    $stanowiska = $mysqli->query($queryStanowiska)->fetch_all(MYSQLI_ASSOC);
    $powiazania = $mysqli->query($queryPowiazania)->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'pracownicy' => $pracownicy,
        'stanowiska' => $stanowiska,
        'powiazania' => $powiazania,
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['powiazania']) && !isset($input['usunPowiazania'])) {
        echo json_encode(['error' => 'Brak danych do zapisania']);
        exit;
    }

    $response = [];

    if (isset($input['powiazania'])) {
        foreach ($input['powiazania'] as $powiazanie) {
            $idOsoba = intval($powiazanie['IdOsoba']);
            $idStanowiska = intval($powiazanie['IdStanowiska']);

            $checkQuery = "
                SELECT * FROM stanowiskoosoba
                WHERE IdOsoba = $idOsoba AND IdStanowiska = $idStanowiska
            ";
            $checkResult = $mysqli->query($checkQuery);

            if ($checkResult->num_rows === 0) {
                $insertQuery = "
                    INSERT INTO stanowiskoosoba (IdOsoba, IdStanowiska)
                    VALUES ($idOsoba, $idStanowiska)
                ";
                if ($mysqli->query($insertQuery)) {
                    $response[] = "Dodano powiązanie: Osoba $idOsoba -> Stanowisko $idStanowiska.";
                } else {
                    $response[] = "Błąd przy dodawaniu powiązania: " . $mysqli->error;
                }
            }
        }
    }

    if (isset($input['usunPowiazania'])) {
        foreach ($input['usunPowiazania'] as $powiazanie) {
            $idOsoba = intval($powiazanie['IdOsoba']);
            $idStanowiska = intval($powiazanie['IdStanowiska']);

            $deleteQuery = "
                DELETE FROM stanowiskoosoba
                WHERE IdOsoba = $idOsoba AND IdStanowiska = $idStanowiska
            ";
            if ($mysqli->query($deleteQuery)) {
                $response[] = "Usunięto powiązanie: Osoba $idOsoba -> Stanowisko $idStanowiska.";
            } else {
                $response[] = "Błąd przy usuwaniu powiązania: " . $mysqli->error;
            }
        }
    }

    echo json_encode([
        'message' => 'Dane zaktualizowane pomyślnie',
        'details' => $response,
    ]);
}

$mysqli->close();
?>