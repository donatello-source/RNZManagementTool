<?php
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

    // Usunięcie pracownika z tabeli stanowiskoosoba
    $stmt = $mysqli->prepare("DELETE FROM stanowiskoosoba WHERE IdOsoba=?");
    $stmt->bind_param('i', $employeeId);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Błąd usuwania stanowisk']);
        exit;
    }

    // Usunięcie pracownika z tabeli osoby
    $stmt = $mysqli->prepare("DELETE FROM osoby WHERE IdOsoba=?");
    $stmt->bind_param('i', $employeeId);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Błąd usuwania pracownika']);
        exit;
    }

    $mysqli->close();
    echo json_encode(['success' => true, 'message' => 'Pracownik został usunięty']);
} else {
    echo json_encode(['error' => 'Brak parametru ID']);
}
?>