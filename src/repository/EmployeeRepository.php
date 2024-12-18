<?php

class EmployeeRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = (new Database())->connect();
    }
    public function addUser(string $imie, string $nazwisko, string $email, string $haslo): bool
    {
        $emailCheckQuery = "SELECT COUNT(*) as count FROM osoby WHERE Email = ?";
        $stmt = $this->connection->prepare($emailCheckQuery);

        if (!$stmt) {
            error_log('Błąd podczas przygotowywania zapytania o email');
            return false;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['count'] > 0) {
                error_log('Email już istnieje w bazie danych');
                $stmt->close();
                return false;
            }
        }
        $stmt->close();

        $hashedPassword = password_hash($haslo, PASSWORD_DEFAULT);
        if (!$hashedPassword) {
            error_log('Błąd podczas haszowania hasła');
            return false;
        }
        $insertQuery = "INSERT INTO osoby (Imie, Nazwisko, Email, Haslo, Status) VALUES (?, ?, ?, ?, 'none')";
        $stmt = $this->connection->prepare($insertQuery);

        if (!$stmt) {
            error_log('Błąd podczas przygotowywania zapytania o dodanie użytkownika');
            return false;
        }

        $stmt->bind_param("ssss", $imie, $nazwisko, $email, $hashedPassword);
        $success = $stmt->execute();

        if (!$success) {
            error_log('Błąd podczas dodawania użytkownika: ' . $stmt->error);
        }

        $stmt->close();
        return $success;
    }
    public function getAllEmployees(): array
    {
        $query = "
            SELECT IdOsoba, Imie, Nazwisko, NumerTelefonu, kolor
            FROM osoby
        ";

        $result = $this->connection->query($query);
        if (!$result || $result->num_rows === 0) {
            return [];
        }

        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = [
                'IdOsoba' => $row['IdOsoba'],
                'Imie' => $row['Imie'],
                'Nazwisko' => $row['Nazwisko'],
                'NumerTelefonu' => $row['NumerTelefonu'],
                'kolor' => $row['kolor']
            ];
        }

        return $employees;
    }
    public function getAllDetailedEmployees(): array
    {
        $query = "
            SELECT IdOsoba, Imie, Nazwisko, NumerTelefonu, AdresZamieszkania, Email, Status, kolor
            FROM osoby
        ";

        $result = $this->connection->query($query);
        if (!$result || $result->num_rows === 0) {
            return [];
        }

        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = [
                'IdOsoba' => $row['IdOsoba'],
                'Imie' => $row['Imie'],
                'Nazwisko' => $row['Nazwisko'],
                'NumerTelefonu' => $row['NumerTelefonu'],
                'AdresZamieszkania' => $row['AdresZamieszkania'],
                'Email' => $row['Email'],
                'Status' => $row['Status'],
                'kolor' => $row['kolor']
            ];
        }

        return $employees;
    }
    public function getEmployee(int $employeeId): array
    {
        $query = "
            SELECT 
                o.*, 
                st.NazwaStanowiska, 
                so.Stawka,
                st.IdStanowiska
            FROM osoby o
            LEFT JOIN stanowiskoosoba so ON so.IdOsoba = o.IdOsoba
            LEFT JOIN stanowiska st ON st.IdStanowiska = so.IdStanowiska
            WHERE o.IdOsoba = ?
        ";

        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            return ['message' => 'Błąd podczas przygotowywania zapytania'];
        }

        $stmt->bind_param("i", $employeeId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['message' => 'Pracownik nie znaleziony'];
        }

        $employee = [];

        while ($row = $result->fetch_assoc()) {
            if (empty($employee)) {
                $employee = [
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
                $employee['stanowiska'][] = [
                    'NazwaStanowiska' => $row['NazwaStanowiska'],
                    'Stawka' => $row['Stawka'],
                    'IdStanowiska' => $row['IdStanowiska']
                ];
            }
        }

        $stmt->close();

        return $employee;
    }

    public function deleteEmployee(int $employeeId): bool
    {
        $this->connection->begin_transaction();

        try {
            $stmt = $this->connection->prepare("DELETE FROM stanowiskoosoba WHERE IdOsoba = ?");
            if (!$stmt) {
                throw new Exception("Błąd podczas przygotowywania zapytania: " . $this->connection->error);
            }
            $stmt->bind_param('i', $employeeId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd usuwania stanowisk: " . $stmt->error);
            }
            $stmt->close();

            $stmt = $this->connection->prepare("DELETE FROM osoby WHERE IdOsoba = ?");
            if (!$stmt) {
                throw new Exception("Błąd podczas przygotowywania zapytania: " . $this->connection->error);
            }
            $stmt->bind_param('i', $employeeId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd usuwania pracownika: " . $stmt->error);
            }
            $stmt->close();

            $this->connection->commit();
            return true;
        } catch (Exception $e) {
            $this->connection->rollback();
            error_log("Nie udało się usunąć pracownika: " . $e->getMessage());
            return false;
        }
    }
    public function getEmployeeEvents(int $employeeId): array
    {
        $today = date('Y-m-d');

        $query = "
            SELECT 
                w.IdWydarzenia, 
                w.NazwaWydarzenia, 
                w.DataPoczatek, 
                w.DataKoniec, 
                w.Miejsce, 
                f.NazwaFirmy,
                wp.Dzien, 
                wp.StawkaDzienna, 
                wp.Nadgodziny, 
                wp.IdStanowiska
            FROM wydarzeniapracownicy wp
            JOIN wydarzenia w ON wp.IdWydarzenia = w.IdWydarzenia
            JOIN firma f ON w.IdFirma = f.IdFirma
            WHERE wp.IdOsoba = ? AND wp.Dzien <= ? AND wp.Dzien != 0 
            ORDER BY w.DataKoniec DESC, w.DataPoczatek DESC, wp.Dzien DESC
        ";

        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            error_log("Błąd przygotowania zapytania: " . $this->connection->error);
            return [];
        }

        $stmt->bind_param('is', $employeeId, $today);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['message' => 'Brak wydarzeń przypisanych do pracownika'];
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $eventId = $row['IdWydarzenia'];

            if (!isset($events[$eventId])) {
                $events[$eventId] = [
                    'IdWydarzenia' => $row['IdWydarzenia'],
                    'NazwaWydarzenia' => $row['NazwaWydarzenia'],
                    'Miejsce' => $row['Miejsce'],
                    'NazwaFirmy' => $row['NazwaFirmy'],
                    'DataPoczatek' => $row['DataPoczatek'],
                    'DataKoniec' => $row['DataKoniec'],
                    'DniPracy' => []
                ];
            }

            $events[$eventId]['DniPracy'][] = [
                'Dzien' => $row['Dzien'],
                'StawkaDzienna' => $row['StawkaDzienna'],
                'Nadgodziny' => $row['Nadgodziny'],
                'IdStanowiska' => $row['IdStanowiska']
            ];
        }

        $stmt->close();
        return array_values($events);
    }

    public function getEmployeePositions(int $employeeId): array
    {
        $query = "
            SELECT 
                s.IdStanowiska, 
                s.nazwaStanowiska, 
                so.Stawka
            FROM stanowiskoosoba so
            JOIN stanowiska s ON so.IdStanowiska = s.IdStanowiska
            WHERE so.IdOsoba = ?
        ";
    
        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            error_log("Błąd przygotowania zapytania: " . $this->connection->error);
            return [];
        }
    
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows === 0) {
            return ['message' => 'Brak przypisanych stanowisk dla pracownika'];
        }
    
        $positions = [];
        while ($row = $result->fetch_assoc()) {
            $positions[] = [
                'IdStanowiska' => $row['IdStanowiska'],
                'NazwaStanowiska' => $row['nazwaStanowiska'],
                'Stawka' => $row['Stawka']
            ];
        }
    
        $stmt->close();
        return $positions;
    }
    
    public function getEmployeesPositions(): array
    {
        $queryPracownicy = "
            SELECT o.IdOsoba, o.Imie, o.Nazwisko 
            FROM osoby o
        ";
        $resultPracownicy = $this->connection->query($queryPracownicy);
        if (!$resultPracownicy) {
            throw new Exception("Błąd podczas pobierania pracowników: " . $this->connection->error);
        }
        $pracownicy = $resultPracownicy->fetch_all(MYSQLI_ASSOC);

        $queryStanowiska = "
            SELECT IdStanowiska, NazwaStanowiska 
            FROM stanowiska
        ";
        $resultStanowiska = $this->connection->query($queryStanowiska);
        if (!$resultStanowiska) {
            throw new Exception("Błąd podczas pobierania stanowisk: " . $this->connection->error);
        }
        $stanowiska = $resultStanowiska->fetch_all(MYSQLI_ASSOC);

        $queryPowiazania = "
            SELECT IdOsoba, IdStanowiska 
            FROM stanowiskoosoba
        ";
        $resultPowiazania = $this->connection->query($queryPowiazania);
        if (!$resultPowiazania) {
            throw new Exception("Błąd podczas pobierania powiązań: " . $this->connection->error);
        }
        $powiazania = $resultPowiazania->fetch_all(MYSQLI_ASSOC);

        return [
            'pracownicy' => $pracownicy,
            'stanowiska' => $stanowiska,
            'powiazania' => $powiazania,
        ];
    }



    public function updateEmployeesPositions(array $data): array
    {
        $response = [];

        $this->connection->begin_transaction();

        try {
            if (isset($data['powiazania'])) {
                foreach ($data['powiazania'] as $powiazanie) {
                    $idOsoba = intval($powiazanie['IdOsoba']);
                    $idStanowiska = intval($powiazanie['IdStanowiska']);

                    $checkQuery = "
                        SELECT * 
                        FROM stanowiskoosoba 
                        WHERE IdOsoba = ? AND IdStanowiska = ?
                    ";
                    $checkStmt = $this->connection->prepare($checkQuery);
                    $checkStmt->bind_param('ii', $idOsoba, $idStanowiska);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();

                    if ($checkResult->num_rows === 0) {
                        $insertQuery = "
                            INSERT INTO stanowiskoosoba (IdOsoba, IdStanowiska) 
                            VALUES (?, ?)
                        ";
                        $insertStmt = $this->connection->prepare($insertQuery);
                        $insertStmt->bind_param('ii', $idOsoba, $idStanowiska);
                        if ($insertStmt->execute()) {
                            $response[] = "Dodano powiązanie: Osoba $idOsoba -> Stanowisko $idStanowiska.";
                        } else {
                            throw new Exception("Błąd przy dodawaniu powiązania: " . $this->connection->error);
                        }
                    }
                    $checkStmt->close();
                }
            }

            if (isset($data['usunPowiazania'])) {
                foreach ($data['usunPowiazania'] as $powiazanie) {
                    $idOsoba = intval($powiazanie['IdOsoba']);
                    $idStanowiska = intval($powiazanie['IdStanowiska']);

                    $deleteQuery = "
                        DELETE FROM stanowiskoosoba 
                        WHERE IdOsoba = ? AND IdStanowiska = ?
                    ";
                    $deleteStmt = $this->connection->prepare($deleteQuery);
                    $deleteStmt->bind_param('ii', $idOsoba, $idStanowiska);
                    if ($deleteStmt->execute()) {
                        $response[] = "Usunięto powiązanie: Osoba $idOsoba -> Stanowisko $idStanowiska.";
                    } else {
                        throw new Exception("Błąd przy usuwaniu powiązania: " . $this->connection->error);
                    }
                    $deleteStmt->close();
                }
            }
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollback();
            return [
                'error' => 'Wystąpił błąd podczas aktualizacji danych: ' . $e->getMessage()
            ];
        }
        return [
            'message' => 'Dane zaktualizowane pomyślnie',
            'details' => $response
        ];
    }
    public function updateEmployee(int $employeeId, array $data): bool
    {
        $requiredFields = ['Imie', 'Nazwisko', 'NumerTelefonu', 'Email', 'AdresZamieszkania', 'stanowiska'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Pole $field jest wymagane");
            }
        }

        $imie = $this->connection->real_escape_string($data['Imie']);
        $nazwisko = $this->connection->real_escape_string($data['Nazwisko']);
        $telefon = $this->connection->real_escape_string($data['NumerTelefonu']);
        $email = $this->connection->real_escape_string($data['Email']);
        $adres = $this->connection->real_escape_string($data['AdresZamieszkania']);
        $stanowiska = $data['stanowiska'];

        $checkQuery = "SELECT * FROM osoby WHERE IdOsoba = $employeeId";
        $result = $this->connection->query($checkQuery);

        if ($result->num_rows === 0) {
            throw new Exception("Pracownik o ID $employeeId nie istnieje");
        }

        $updateQuery = "UPDATE osoby 
                        SET Imie = '$imie', 
                            Nazwisko = '$nazwisko', 
                            NumerTelefonu = '$telefon', 
                            Email = '$email', 
                            AdresZamieszkania = '$adres' 
                        WHERE IdOsoba = $employeeId";

        if (!$this->connection->query($updateQuery)) {
            throw new Exception("Błąd podczas aktualizacji danych osobowych: " . $this->connection->error);
        }

        foreach ($stanowiska as $stanowisko) {
            $idStanowiska = $this->connection->real_escape_string($stanowisko['IdStanowiska']);
            $stawka = $this->connection->real_escape_string($stanowisko['Stawka']);

            $updateStanowiskoQuery = "UPDATE stanowiskoosoba 
                                      SET Stawka = '$stawka' 
                                      WHERE IdOsoba = $employeeId AND IdStanowiska = $idStanowiska";

            if (!$this->connection->query($updateStanowiskoQuery)) {
                throw new Exception("Błąd aktualizacji stawki stanowiska: " . $this->connection->error);
            }
        }

        return true;
    }
    public function getEmployeesSummary(int $month, int $year): array
    {
        $query = "
        SELECT 
            CONCAT(o.Imie, ' ', o.Nazwisko) AS Pracownik,
            SUM(
                CASE 
                    WHEN wp.StawkaDzienna = 1 THEN (so.Stawka * 12 + wp.Nadgodziny * so.Stawka * 1.25)
                    ELSE 0
                END
            ) AS Suma
        FROM wydarzeniapracownicy wp
        LEFT JOIN osoby o ON wp.IdOsoba = o.IdOsoba
        LEFT JOIN stanowiskoosoba so ON so.IdStanowiska = wp.IdStanowiska AND so.IdOsoba = wp.IdOsoba
        WHERE MONTH(STR_TO_DATE(wp.Dzien, '%Y-%m-%d')) = 12 
        AND YEAR(STR_TO_DATE(wp.Dzien, '%Y-%m-%d')) = 2024
        GROUP BY wp.IdOsoba, o.Imie, o.Nazwisko
        ORDER BY Suma DESC;
        ";

        $stmt = $this->connection->prepare($query);
        $stmt->bind_param('ii', $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();

        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = [
                'pracownik' => $row['Pracownik'],
                'suma' => $row['Suma']
            ];
        }

        return $employees;
    }
}