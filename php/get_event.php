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

if (isset($_GET['id'])) {
    $eventId = $_GET['id'];

    // Zapytanie do bazy danych o wydarzenia
    $query = "SELECT w.IdWydarzenia, w.NazwaWydarzenia,w.IdFirma, w.DataPoczatek, w.DataKoniec, w.Miejsce, w.Komentarz, f.NazwaFirmy
        FROM wydarzenia w
        JOIN firma f ON w.IdFirma = f.IdFirma
        WHERE w.IdWydarzenia=$eventId";

    // Wykonanie zapytania
    $result = $mysqli->query($query);

    // Sprawdzenie, czy zapytanie zwróciło wyniki
    if ($result->num_rows > 0) {
        $events = [];

        // Pobranie wszystkich wyników
        while ($row = $result->fetch_assoc()) {
            // Zapytanie o pracowników przypisanych do wydarzenia
            $eventId = $row['IdWydarzenia'];
            $employeeQuery = "SELECT o.Imie, o.Nazwisko, o.IdOsoba, o.kolor, wp.Dzien
                FROM wydarzeniapracownicy wp
                JOIN osoby o ON wp.IdOsoba = o.IdOsoba
                WHERE wp.IdWydarzenia = $eventId";
                
            $employeeResult = $mysqli->query($employeeQuery);
            $employees = [];

            // Pobranie pracowników przypisanych do wydarzenia
            while ($employeeRow = $employeeResult->fetch_assoc()) {
                $employees[] = [
                    'IdOsoba' => $employeeRow['IdOsoba'],
                    'Imie' => $employeeRow['Imie'],
                    'Nazwisko' => $employeeRow['Nazwisko'],
                    'kolor' => $employeeRow['kolor'],
                    'Dzien' => $employeeRow['Dzien']
                ];
            }
            

            // Dodanie wydarzenia do tablicy
            $events[] = [
                'IdWydarzenia' => $row['IdWydarzenia'],
                'NazwaWydarzenia' => $row['NazwaWydarzenia'],
                'Miejsce' => $row['Miejsce'],
                'NazwaFirmy' => $row['NazwaFirmy'],
                'DataPoczatek' => $row['DataPoczatek'],
                'DataKoniec' => $row['DataKoniec'],
                'ListaPracownikow' => $employees,
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
} else {
    echo json_encode(['error' => 'Brak parametru ID']);
}
?>