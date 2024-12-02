<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
// Ustawienia połączenia z bazą danych
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


// Zapytanie do bazy danych o wydarzenia
$query = "SELECT w.IdWydarzenia, w.NazwaWydarzenia,w.IdFirma, w.DataPoczatek, w.DataKoniec, w.Miejsce, w.Komentarz, f.NazwaFirmy
    FROM wydarzenia w
    JOIN firma f ON w.IdFirma = f.IdFirma
    ORDER BY w.DataPoczatek";

// Wykonanie zapytania
$result = $mysqli->query($query);

// Sprawdzenie, czy zapytanie zwróciło wyniki
if ($result->num_rows > 0) {
    $events = [];

    // Pobranie wszystkich wyników
    while ($row = $result->fetch_assoc()) {
        // Dodanie wydarzenia do tablicy
        $events[] = [
            'IdWydarzenia' => $row['IdWydarzenia'],
            'NazwaWydarzenia' => $row['NazwaWydarzenia'],
            'Miejsce' => $row['Miejsce'],
            'NazwaFirmy' => $row['NazwaFirmy'],
            'DataPoczatek' => $row['DataPoczatek'],
            'DataKoniec' => $row['DataKoniec'],
            'Komentarz' => $row['Komentarz']
        ];
    }

    // Zwrócenie wyników w formacie JSON
    echo json_encode($events);
} else {
    echo json_encode(['message' => 'Brak wydarzeń w bazie']);
}

// Zamknięcie połączenia z bazą danych
$mysqli->close();
?>