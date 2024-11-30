<?php
header('Content-Type: application/json');
$mysqli = new mysqli('localhost', 'root', '', 'rnzmanago');
if ($mysqli->connect_error) die(json_encode(['error' => 'Błąd połączenia']));

$query = "SELECT IdOsoba, Imie, Nazwisko, kolor FROM osoby";
$result = $mysqli->query($query);

$pracownicy = [];
while ($row = $result->fetch_assoc()) {
    $pracownicy[] = $row;
}

echo json_encode($pracownicy);
$mysqli->close();
?>