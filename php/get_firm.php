<?php
// Ustawienie nagłówków CORS
header('Content-Type: application/json');
$host = 'localhost';
$dbname = 'rnzmanago';  // Nazwa Twojej bazy danych
$user = 'root';         // Domyślny użytkownik MySQL w XAMPP
$pass = '';             // Domyślne hasło w XAMPP (pusty)

// Sprawdzenie, czy parametr 'id' został przekazany w URL
if (isset($_GET['id'])) {
    $firmId = $_GET['id'];

    // Połączenie z bazą danych
    $mysqli = new mysqli($host, $user, $pass, $dbname);

    if ($mysqli->connect_error) {
        die(json_encode(['error' => 'Błąd połączenia z bazą danych'])); // Zwróć błąd w formacie JSON
    }

    // Zapytanie do bazy danych, aby pobrać dane konkretnego pracownika
    $query = "SELECT * FROM firma WHERE IdFirma = $firmId"; // Używaj prawidłowego idOsoba

    // Wykonanie zapytania
    $result = $mysqli->query($query);

    if ($result->num_rows > 0) {
        $pracownik = $result->fetch_assoc(); // Zwróć tylko jeden wiersz, bo szukasz konkretnego pracownika
        
        // Zwrócenie wyników w formacie JSON
        echo json_encode($pracownik);
    } else {
        echo json_encode(['message' => 'Brak firmy w bazie']);
    }

    // Zamknięcie połączenia z bazą danych
    $mysqli->close();
} else {
    echo json_encode(['error' => 'Brak parametru ID']);
}
?>