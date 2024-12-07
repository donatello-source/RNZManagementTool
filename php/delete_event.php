<?php
header('Content-Type: application/json');
$mysqli = new mysqli('localhost', 'root', '', 'rnzmanago');
if ($mysqli->connect_error) {
    die(json_encode(['error' => 'Błąd połączenia']));
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];  // Rzutowanie na int dla bezpieczeństwa

    // Przygotowane zapytanie do usunięcia wydarzenia
    $query = $mysqli->prepare("DELETE FROM wydarzenia WHERE IdWydarzenia = ?");
    $query->bind_param("i", $id);  // "i" oznacza, że parametrem jest liczba całkowita
    if (!$query->execute()) {
        echo json_encode(['error' => 'Błąd podczas usuwania wydarzenia']);
        $mysqli->close();
        exit;
    }

    // Usuwanie powiązanych rekordów z tabeli wydarzeniapracownicy
    $query2 = $mysqli->prepare("DELETE FROM wydarzeniapracownicy WHERE IdWydarzenia = ?");
    $query2->bind_param("i", $id);
    if (!$query2->execute()) {
        echo json_encode(['error' => 'Błąd podczas usuwania powiązanych pracowników']);
        $mysqli->close();
        exit;
    }

    echo json_encode(['message' => 'Wydarzenie zostało usunięte pomyślnie']);
} else {
    echo json_encode(['error' => 'Brak ID wydarzenia']);
}

$mysqli->close();
?>