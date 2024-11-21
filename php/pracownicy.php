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
$query = "SELECT idOsoba, imie, nazwisko, numertelefonu, kolor FROM osoby";

// Wykonanie zapytania
$result = $mysqli->query($query);

// Sprawdzenie, czy zapytanie zwróciło wyniki
if ($result->num_rows > 0) {
    $pracownicy = [];
    
    // Pobranie wszystkich wyników
    while ($row = $result->fetch_assoc()) {
        $pracownicy[] = $row;
    }

    // Zwrócenie wyników w formacie JSON
    echo json_encode($pracownicy);
} else {
    echo json_encode(['message' => 'Brak pracowników w bazie']);
}

// Zamknięcie połączenia z bazą danych
$mysqli->close();
?>