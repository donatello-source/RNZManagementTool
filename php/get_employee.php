<?php
// Ustawienie nagłówków CORS
header('Content-Type: application/json');
$host = 'localhost';
$dbname = 'rnzmanago';
$user = 'root';
$pass = '';

if (isset($_GET['id'])) {
    $employeeId = intval($_GET['id']); // Zabezpieczenie przed SQL Injection

    $mysqli = new mysqli($host, $user, $pass, $dbname);

    if ($mysqli->connect_error) {
        die(json_encode(['error' => 'Błąd połączenia z bazą danych']));
    }

    // Zapytanie o szczegóły pracownika
    $query = "
        SELECT 
            o.*, 
            st.NazwaStanowiska, 
            so.Stawka,
            st.IdStanowiska
        FROM osoby o
        LEFT JOIN stanowiskoosoba so ON so.IdOsoba = o.IdOsoba
        LEFT JOIN stanowiska st ON st.IdStanowiska = so.IdStanowiska
        WHERE o.IdOsoba = $employeeId
    ";

    $result = $mysqli->query($query);

    if ($result->num_rows > 0) {
        $pracownik = [];
        while ($row = $result->fetch_assoc()) {
            if (empty($pracownik)) {
                $pracownik = [
                    'Imie' => $row['Imie'],
                    'Nazwisko' => $row['Nazwisko'],
                    'NumerTelefonu' => $row['NumerTelefonu'],
                    'Email' => $row['Email'],
                    'AdresZamieszkania' => $row['AdresZamieszkania'],
                    'Status' => $row['Status'],
                    'kolor' => $row['kolor'],
                    'stanowiska' => []
                ];
            }

            if ($row['NazwaStanowiska']) {
                $pracownik['stanowiska'][] = [
                    'NazwaStanowiska' => $row['NazwaStanowiska'],
                    'Stawka' => $row['Stawka'],
                    'IdStanowiska' => $row['IdStanowiska']
                ];
            }
        }
        echo json_encode($pracownik);
    } else {
        echo json_encode(['message' => 'Brak pracownika w bazie']);
    }

    $mysqli->close();
} else {
    echo json_encode(['error' => 'Brak parametru ID']);
}
?>