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

}