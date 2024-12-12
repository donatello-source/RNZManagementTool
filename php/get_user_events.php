<?php
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Sesja nie istnieje lub brak użytkownika']);
    exit();
}

// Dane logowania do bazy danych
$host = 'localhost';
$dbname = 'rnzmanago';  // Nazwa Twojej bazy danych
$user = 'root';         // Domyślny użytkownik MySQL w XAMPP
$pass = '';             // Domyślne hasło w XAMPP (pusty)

// Połączenie z bazą danych
$mysqli = new mysqli($host, $user, $pass, $dbname);

// Sprawdzenie połączenia
if ($mysqli->connect_error) {
    die("Błąd połączenia z bazą danych: " . $mysqli->connect_error);
}

// Pobranie identyfikatora zalogowanego użytkownika
$userId = $_SESSION['user']['id'];
$today = date('Y-m-d');

// Zapytanie do bazy danych
$query = "
SELECT 
    w.IdWydarzenia, 
    w.NazwaWydarzenia, 
    w.DataPoczatek, 
    w.DataKoniec, 
    w.Miejsce, 
    f.NazwaFirmy,
    wp.Dzien, 
    wp.StawkaDzienna, 
    wp.Nadgodziny, 
    wp.IdStanowiska
FROM wydarzeniapracownicy wp
JOIN wydarzenia w ON wp.IdWydarzenia = w.IdWydarzenia
JOIN firma f ON w.IdFirma = f.IdFirma
WHERE wp.IdOsoba = $userId AND wp.Dzien <= '$today' AND wp.Dzien != 0
ORDER BY w.DataKoniec DESC, w.DataPoczatek DESC
";

// Wykonanie zapytania
$result = $mysqli->query($query);

// Sprawdzenie, czy zapytanie zwróciło wyniki
if ($result->num_rows > 0) {
    $events = [];

    // Przetwarzanie wyników
    while ($row = $result->fetch_assoc()) {
        $eventId = $row['IdWydarzenia'];

        // Grupowanie danych dla poszczególnych wydarzeń
        if (!isset($events[$eventId])) {
            $events[$eventId] = [
                'IdWydarzenia' => $row['IdWydarzenia'],
                'NazwaWydarzenia' => $row['NazwaWydarzenia'],
                'Miejsce' => $row['Miejsce'],
                'NazwaFirmy' => $row['NazwaFirmy'],
                'DataPoczatek' => $row['DataPoczatek'],
                'DataKoniec' => $row['DataKoniec'],
                'DniPracy' => []
            ];
        }

        // Dodanie dnia pracy do wydarzenia
        $events[$eventId]['DniPracy'][] = [
            'Dzien' => $row['Dzien'],
            'StawkaDzienna' => $row['StawkaDzienna'],
            'Nadgodziny' => $row['Nadgodziny'],
            'IdStanowiska' => $row['IdStanowiska']
        ];
    }

    // Konwersja do JSON
    echo json_encode(array_values($events));
} else {
    echo json_encode(['message' => 'Brak wydarzeń przypisanych do użytkownika']);
}

// Zamknięcie połączenia z bazą danych
$mysqli->close();
?>