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
        $emailCheckQuery = "SELECT COUNT(*) as count FROM osoby WHERE email = :email";
        $stmt = $this->connection->prepare($emailCheckQuery);

        if (!$stmt) {
            error_log('Błąd podczas przygotowywania zapytania email');
            return false;
        }

        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && $result['count'] > 0) {
            error_log('email już istnieje w bazie danych');
            return false;
        }

        $hashedPassword = password_hash($haslo, PASSWORD_DEFAULT);
        if (!$hashedPassword) {
            error_log('Błąd podczas haszowania hasła');
            return false;
        }
        $insertQuery = "INSERT INTO osoby (imie, nazwisko, email, haslo, status) VALUES (:imie, :nazwisko, :email, :haslo, 'none')";
        $stmt = $this->connection->prepare($insertQuery);

        if (!$stmt) {
            error_log('Błąd podczas przygotowywania zapytania o dodanie użytkownika');
            return false;
        }
        $stmt->bindParam(':imie', $imie, PDO::PARAM_STR);
        $stmt->bindParam(':nazwisko', $nazwisko, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':haslo', $hashedPassword, PDO::PARAM_STR);
        $success = $stmt->execute();

        if (!$success) {
            error_log('Błąd podczas dodawania użytkownika: ' . implode(", ", $stmt->errorInfo()));
        }
        return $success;
    }

    public function getAllEmployees(): array
    {
        $query = "
            SELECT idosoba, imie, nazwisko, numertelefonu, kolor
            FROM osoby
        ";

        $result = $this->connection->query($query);
        if (!$result) {
            return [];
        }

        $employees = $result->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($row) {
            return [
                'idosoba' => $row['idosoba'],
                'imie' => $row['imie'],
                'nazwisko' => $row['nazwisko'],
                'numertelefonu' => $row['numertelefonu'],
                'kolor' => $row['kolor']
            ];
        }, $employees);
    }
    public function getAllDetailedEmployees(): array
    {
        $query = "
            SELECT idosoba, imie, nazwisko, numertelefonu, adreszamieszkania, email, status, kolor
            FROM osoby
        ";

        $result = $this->connection->query($query);
        if (!$result || $result->num_rows === 0) {
            return [];
        }

        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = [
                'idosoba' => $row['idosoba'],
                'imie' => $row['imie'],
                'nazwisko' => $row['nazwisko'],
                'numertelefonu' => $row['numertelefonu'],
                'adreszamieszkania' => $row['adreszamieszkania'],
                'email' => $row['email'],
                'status' => $row['status'],
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
                st.nazwastanowiska, 
                so.stawka,
                st.idstanowiska
            FROM osoby o
            LEFT JOIN stanowiskoosoba so ON so.idosoba = o.idosoba
            LEFT JOIN stanowiska st ON st.idstanowiska = so.idstanowiska
            WHERE o.idosoba = :employeeId
        ";

        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            return ['message' => 'Błąd podczas przygotowywania zapytania'];
        }

        $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($result)) {
            return ['message' => 'Pracownik nie znaleziony'];
        }

        $employee = [];
        foreach ($result as $row) {
            if (empty($employee)) {
                $employee = [
                    'imie' => $row['imie'],
                    'nazwisko' => $row['nazwisko'],
                    'numertelefonu' => $row['numertelefonu'],
                    'email' => $row['email'],
                    'adreszamieszkania' => $row['adreszamieszkania'],
                    'status' => $row['status'],
                    'kolor' => $row['kolor'],
                    'stanowiska' => []
                ];
            }

            if ($row['nazwastanowiska']) {
                $employee['stanowiska'][] = [
                    'nazwastanowiska' => $row['nazwastanowiska'],
                    'stawka' => $row['stawka'],
                    'idstanowiska' => $row['idstanowiska']
                ];
            }
        }

        return $employee;
    }


    public function deleteEmployee(int $employeeId): bool
    {
        $this->connection->beginTransaction();

        try {
            $stmt = $this->connection->prepare("DELETE FROM stanowiskoosoba WHERE idosoba = :employeeId");
            if (!$stmt) {
                throw new Exception("Błąd podczas przygotowywania zapytania: " . implode(", ", $this->connection->errorInfo()));
            }
            $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception("Błąd usuwania stanowisk: " . implode(", ", $stmt->errorInfo()));
            }

            $stmt = $this->connection->prepare("DELETE FROM osoby WHERE idosoba = :employeeId");
            if (!$stmt) {
                throw new Exception("Błąd podczas przygotowywania zapytania: " . implode(", ", $this->connection->errorInfo()));
            }
            $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception("Błąd usuwania pracownika: " . implode(", ", $stmt->errorInfo()));
            }

            $this->connection->commit();
            return true;
        } catch (Exception $e) {
            $this->connection->rollBack();
            error_log("Nie udało się usunąć pracownika: " . $e->getMessage());
            return false;
        }
    }

    public function getEmployeeEvents(int $employeeId): array
    {
        $today = date('Y-m-d');
    
        $query = "
            SELECT 
                w.idwydarzenia, 
                w.nazwawydarzenia, 
                w.datapoczatek, 
                w.datakoniec, 
                w.miejsce, 
                f.nazwafirmy,
                wp.dzien, 
                wp.stawkadzienna, 
                wp.nadgodziny, 
                wp.idstanowiska
            FROM wydarzeniapracownicy wp
            JOIN wydarzenia w ON wp.idwydarzenia = w.idwydarzenia
            JOIN firma f ON w.idfirma = f.idfirma
            WHERE wp.idosoba = :employeeId 
              AND wp.dzien <= :today 
              AND wp.dzien != '0'
            ORDER BY w.datakoniec DESC, w.datapoczatek DESC, wp.dzien DESC
        ";
    
        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            error_log("Błąd przygotowania zapytania: " . implode(" ", $this->connection->errorInfo()));
            return [];
        }
    
        $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':today', $today, PDO::PARAM_STR);
        $stmt->execute();
    
        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $eventId = $row['idwydarzenia'];
    
            if (!isset($events[$eventId])) {
                $events[$eventId] = [
                    'idwydarzenia' => $row['idwydarzenia'],
                    'nazwawydarzenia' => $row['nazwawydarzenia'],
                    'miejsce' => $row['miejsce'],
                    'nazwafirmy' => $row['nazwafirmy'],
                    'datapoczatek' => $row['datapoczatek'],
                    'datakoniec' => $row['datakoniec'],
                    'dnipracy' => []
                ];
            }
    
            $events[$eventId]['dnipracy'][] = [
                'dzien' => $row['dzien'],
                'stawkadzienna' => $row['stawkadzienna'],
                'nadgodziny' => $row['nadgodziny'],
                'idstanowiska' => $row['idstanowiska']
            ];
        }
    
        return array_values($events);
    }
    
    

    public function getEmployeePositions(int $employeeId): array
    {
        $query = "
            SELECT 
                s.idstanowiska, 
                s.nazwastanowiska, 
                so.stawka
            FROM stanowiskoosoba so
            JOIN stanowiska s ON so.idstanowiska = s.idstanowiska
            WHERE so.idosoba = :employeeId
        ";
    
        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            error_log("Błąd przygotowania zapytania: " . implode(" ", $this->connection->errorInfo()));
            return [];
        }
    
        $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
    
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if (empty($positions)) {
            return ['message' => 'Brak przypisanych stanowisk dla pracownika'];
        }
    
        return $positions;
    }
    
    
    public function getEmployeesPositions(): array
    {
        $queryPracownicy = "
            SELECT o.idosoba, o.imie, o.nazwisko 
            FROM osoby o
        ";
        $resultPracownicy = $this->connection->query($queryPracownicy);
        if (!$resultPracownicy) {
            throw new Exception("Błąd podczas pobierania pracowników: " . $this->connection->error);
        }
        $pracownicy = $resultPracownicy->fetchAll(PDO::FETCH_ASSOC);
    
        $queryStanowiska = "
            SELECT idstanowiska, nazwastanowiska 
            FROM stanowiska
        ";
        $resultStanowiska = $this->connection->query($queryStanowiska);
        if (!$resultStanowiska) {
            throw new Exception("Błąd podczas pobierania stanowisk: " . $this->connection->error);
        }
        $stanowiska = $resultStanowiska->fetchAll(PDO::FETCH_ASSOC);
    
        $queryPowiazania = "
            SELECT idosoba, idstanowiska 
            FROM stanowiskoosoba
        ";
        $resultPowiazania = $this->connection->query($queryPowiazania);
        if (!$resultPowiazania) {
            throw new Exception("Błąd podczas pobierania powiązań: " . $this->connection->error);
        }
        $powiazania = $resultPowiazania->fetchAll(PDO::FETCH_ASSOC);
    
        return [
            'pracownicy' => $pracownicy,
            'stanowiska' => $stanowiska,
            'powiazania' => $powiazania,
        ];
    }
    
    public function updateEmployeesPositions(array $data): array
    {
        $response = [];
    
        $this->connection->beginTransaction();
    
        try {
            if (isset($data['powiazania'])) {
                foreach ($data['powiazania'] as $powiazanie) {
                    $idosoba = intval($powiazanie['idosoba']);
                    $idstanowiska = intval($powiazanie['idstanowiska']);
    
                    $checkQuery = "
                        SELECT 1 
                        FROM stanowiskoosoba 
                        WHERE idosoba = :idosoba AND idstanowiska = :idstanowiska
                    ";
                    $checkStmt = $this->connection->prepare($checkQuery);
                    $checkStmt->bindParam(':idosoba', $idosoba, PDO::PARAM_INT);
                    $checkStmt->bindParam(':idstanowiska', $idstanowiska, PDO::PARAM_INT);
                    $checkStmt->execute();
    
                    if ($checkStmt->rowCount() === 0) {
                        $insertQuery = "
                            INSERT INTO stanowiskoosoba (idosoba, idstanowiska) 
                            VALUES (:idosoba, :idstanowiska)
                        ";
                        $insertStmt = $this->connection->prepare($insertQuery);
                        $insertStmt->bindParam(':idosoba', $idosoba, PDO::PARAM_INT);
                        $insertStmt->bindParam(':idstanowiska', $idstanowiska, PDO::PARAM_INT);
                        if ($insertStmt->execute()) {
                            $response[] = "Dodano powiązanie: Osoba $idosoba -> Stanowisko $idstanowiska.";
                        } else {
                            throw new Exception("Błąd przy dodawaniu powiązania: " . implode(" ", $this->connection->errorInfo()));
                        }
                    }
                }
            }
    
            if (isset($data['usunPowiazania'])) {
                foreach ($data['usunPowiazania'] as $powiazanie) {
                    $idosoba = intval($powiazanie['idosoba']);
                    $idstanowiska = intval($powiazanie['idstanowiska']);
    
                    $deleteQuery = "
                        DELETE FROM stanowiskoosoba 
                        WHERE idosoba = :idosoba AND idstanowiska = :idstanowiska
                    ";
                    $deleteStmt = $this->connection->prepare($deleteQuery);
                    $deleteStmt->bindParam(':idosoba', $idosoba, PDO::PARAM_INT);
                    $deleteStmt->bindParam(':idstanowiska', $idstanowiska, PDO::PARAM_INT);
                    if ($deleteStmt->execute()) {
                        $response[] = "Usunięto powiązanie: Osoba $idosoba -> Stanowisko $idstanowiska.";
                    } else {
                        throw new Exception("Błąd przy usuwaniu powiązania: " . implode(" ", $this->connection->errorInfo()));
                    }
                }
            }
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
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
        $requiredFields = ['imie', 'nazwisko', 'numertelefonu', 'email', 'adreszamieszkania', 'stanowiska', 'kolor'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Pole $field jest wymagane");
            }
        }

        $imie = $data['imie'];
        $nazwisko = $data['nazwisko'];
        $telefon = $data['numertelefonu'];
        $email = $data['email'];
        $adres = $data['adreszamieszkania'];
        $stanowiska = $data['stanowiska'];
        $kolor = $data['kolor'];

        $checkQuery = "SELECT * FROM osoby WHERE idosoba = :employeeId";
        $stmt = $this->connection->prepare($checkQuery);
        $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            throw new Exception("Pracownik o ID $employeeId nie istnieje");
        }

        $updateQuery = "
            UPDATE osoby 
            SET 
                imie = :imie, 
                nazwisko = :nazwisko, 
                numertelefonu = :telefon, 
                email = :email, 
                adreszamieszkania = :adres, 
                kolor = :kolor
            WHERE idosoba = :employeeId
        ";
        $stmt = $this->connection->prepare($updateQuery);
        $stmt->bindParam(':imie', $imie, PDO::PARAM_STR);
        $stmt->bindParam(':nazwisko', $nazwisko, PDO::PARAM_STR);
        $stmt->bindParam(':telefon', $telefon, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':adres', $adres, PDO::PARAM_STR);
        $stmt->bindParam(':kolor', $kolor, PDO::PARAM_STR);
        $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new Exception("Błąd podczas aktualizacji danych osobowych: " . implode(", ", $stmt->errorInfo()));
        }

        foreach ($stanowiska as $stanowisko) {
            $idstanowiska = $stanowisko['idstanowiska'];
            $stawka = $stanowisko['stawka'];

            $updateStanowiskoQuery = "
                UPDATE stanowiskoosoba 
                SET stawka = :stawka 
                WHERE idosoba = :employeeId AND idstanowiska = :idstanowiska
            ";
            $stmt = $this->connection->prepare($updateStanowiskoQuery);
            $stmt->bindParam(':stawka', $stawka, PDO::PARAM_STR);
            $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
            $stmt->bindParam(':idstanowiska', $idstanowiska, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Błąd aktualizacji stawki stanowiska: " . implode(", ", $stmt->errorInfo()));
            }
        }

        return true;
    }

    public function getEmployeeProfile(int $employeeId): array
    {
        $query = "
            SELECT 
                o.imie, 
                o.nazwisko, 
                o.numertelefonu, 
                o.email, 
                o.adreszamieszkania, 
                o.kolor
            FROM osoby o
            WHERE o.idosoba = :employeeId
        ";
    
        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            return ['message' => 'Błąd podczas przygotowywania zapytania'];
        }
    
        $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
    
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$result) {
            return ['message' => 'Pracownik nie znaleziony'];
        }
    
        return [
            'imie' => $result['imie'],
            'nazwisko' => $result['nazwisko'],
            'numertelefonu' => $result['numertelefonu'],
            'email' => $result['email'],
            'adreszamieszkania' => $result['adreszamieszkania'],
            'kolor' => $result['kolor'],
        ];
    }

    
    public function updateEmployeeProfile(int $employeeId, array $data): bool
    {
        $requiredFields = ['imie', 'nazwisko', 'numertelefonu', 'email', 'adreszamieszkania', 'kolor'];
    
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Pole $field jest wymagane");
            }
        }
    
        $imie = $data['imie'];
        $nazwisko = $data['nazwisko'];
        $telefon = $data['numertelefonu'];
        $email = $data['email'];
        $adres = $data['adreszamieszkania'];
        $kolor = $data['kolor'];
    
        $updateQuery = "
            UPDATE osoby 
            SET 
                imie = :imie, 
                nazwisko = :nazwisko, 
                numertelefonu = :telefon, 
                email = :email, 
                adreszamieszkania = :adres, 
                kolor = :kolor
            WHERE idosoba = :employeeId
        ";
    
        $stmt = $this->connection->prepare($updateQuery);
        $stmt->bindParam(':imie', $imie, PDO::PARAM_STR);
        $stmt->bindParam(':nazwisko', $nazwisko, PDO::PARAM_STR);
        $stmt->bindParam(':telefon', $telefon, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':adres', $adres, PDO::PARAM_STR);
        $stmt->bindParam(':kolor', $kolor, PDO::PARAM_STR);
        $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
    
        if (!$stmt->execute()) {
            throw new Exception("Błąd podczas aktualizacji profilu: " . implode(", ", $stmt->errorInfo()));
        }
    
        return true;
    }
    
    
    public function getEmployeesSummary(int $month, int $year): array
    {
        $query = "
        SELECT 
            CONCAT(o.imie, ' ', o.nazwisko) AS pracownik,
            SUM(
                CASE 
                    WHEN wp.stawkadzienna = true THEN (so.stawka + wp.nadgodziny * so.stawka * 0.1)
                    ELSE 0
                END
            ) AS suma
        FROM wydarzeniapracownicy wp
        LEFT JOIN osoby o ON wp.idosoba = o.idosoba
        LEFT JOIN stanowiskoosoba so ON so.idstanowiska = wp.idstanowiska AND so.idosoba = wp.idosoba
        WHERE EXTRACT(MONTH FROM TO_DATE(wp.dzien, 'YYYY-MM-DD')) = :month 
        AND EXTRACT(YEAR FROM TO_DATE(wp.dzien, 'YYYY-MM-DD')) = :year
        GROUP BY wp.idosoba, o.imie, o.nazwisko
        ORDER BY suma DESC;
        ";
    
        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            throw new Exception("Błąd w przygotowaniu zapytania: " . implode(", ", $this->connection->errorInfo()));
        }
    
        $stmt->bindParam(':month', $month, PDO::PARAM_INT);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($result === false) {
            throw new Exception("Błąd podczas wykonywania zapytania: " . implode(", ", $stmt->errorInfo()));
        }
    
        if (empty($result)) {
            return [];
        }
    
        $employees = [];
        foreach ($result as $row) {
            $employees[] = [
                'pracownik' => $row['pracownik'],
                'suma' => $row['suma']
            ];
        }
    
        return $employees;
    }
    
}