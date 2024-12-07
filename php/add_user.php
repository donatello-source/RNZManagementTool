<?php
header('Content-Type: application/json');
$mysqli = new mysqli('localhost', 'root', '', 'rnzmanago');
if ($mysqli->connect_error) die(json_encode(['error' => 'Błąd połączenia']));

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['imie'], $data['nazwisko'],$data['email'], $data['haslo'])) {
    echo json_encode(['error' => 'Wypełnij wymagane pola']);
    exit;
}

error_log(json_encode($data));

$imie = $mysqli->real_escape_string($data['imie']);
$nazwisko = $mysqli->real_escape_string($data['nazwisko']);
$email = $mysqli->real_escape_string($data['email']);
$haslo = $mysqli->real_escape_string($data['haslo']);

$emailCheckQuery = "SELECT COUNT(*) as count FROM osoby WHERE Email = '$email'";
$result = $mysqli->query($emailCheckQuery);

if ($result) {
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        echo json_encode(['error' => 'Email jest już zarejestrowany']);
        $mysqli->close();
        exit;
    }
} else {
    echo json_encode(['error' => 'Błąd podczas sprawdzania istniejącego emaila']);
    $mysqli->close();
    exit;
}

$query = "INSERT INTO osoby (Imie, Nazwisko, Email, Haslo, Status) 
          VALUES ('$imie','$nazwisko', '$email', '$haslo', 'none')";

if (!$mysqli->query($query)) {
    echo json_encode(['error' => 'Błąd podczas tworzenia konta']);
    exit;
}

echo json_encode(['message' => 'Konto stworzone pomyślnie']);
$mysqli->close();
?>