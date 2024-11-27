<?php
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

// Zapytanie do bazy danych
$query = "SELECT IdFirma, NazwaFirmy, AdresFirmy, NIP, Telefon FROM firma";

// Wykonanie zapytania
$result = $mysqli->query($query);

// Sprawdzenie, czy zapytanie zwróciło wyniki
if ($result->num_rows > 0) {
    $firm = [];
    
    // Pobranie wszystkich wyników
    while ($row = $result->fetch_assoc()) {
        $firm[] = $row;
    }

    // Zwrócenie wyników w formacie JSON
    echo json_encode($firm);
} else {
    echo json_encode(['message' => 'Brak pracowników w bazie']);
}

// Zamknięcie połączenia z bazą danych
$mysqli->close();
?>