<?php

class EventRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = (new Database())->connect();
    }

    public function getEvents(): array
    {
        $query = "
            SELECT w.IdWydarzenia, w.NazwaWydarzenia, w.IdFirma, w.DataPoczatek, w.DataKoniec, w.Miejsce, w.Komentarz, f.NazwaFirmy, w.Hotel
            FROM wydarzenia w
            JOIN firma f ON w.IdFirma = f.IdFirma
            ORDER BY w.DataPoczatek
        ";

        $result = $this->connection->query($query);
        if (!$result || $result->num_rows === 0) {
            return [];
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'IdWydarzenia' => $row['IdWydarzenia'],
                'NazwaWydarzenia' => $row['NazwaWydarzenia'],
                'Miejsce' => $row['Miejsce'],
                'NazwaFirmy' => $row['NazwaFirmy'],
                'DataPoczatek' => $row['DataPoczatek'],
                'DataKoniec' => $row['DataKoniec'],
                'Komentarz' => $row['Komentarz']
            ];
        }

        return $events;
    }
    public function getDetailedEvents(): array
    {
        $query = "
            SELECT w.IdWydarzenia, w.NazwaWydarzenia, w.IdFirma, w.DataPoczatek, w.DataKoniec, w.Miejsce, w.Komentarz, f.NazwaFirmy
            FROM wydarzenia w
            JOIN firma f ON w.IdFirma = f.IdFirma
        ";

        $result = $this->connection->query($query);
        if (!$result || $result->num_rows === 0) {
            return [];
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $eventId = $row['IdWydarzenia'];

            $employeeQuery = "
                SELECT DISTINCT o.Imie, o.Nazwisko, o.IdOsoba, o.kolor
                FROM wydarzeniapracownicy wp
                JOIN osoby o ON wp.IdOsoba = o.IdOsoba
                WHERE wp.IdWydarzenia = ?
            ";

            $employeeStmt = $this->connection->prepare($employeeQuery);
            $employeeStmt->bind_param('i', $eventId);
            $employeeStmt->execute();
            $employeeResult = $employeeStmt->get_result();

            $employees = [];
            while ($employeeRow = $employeeResult->fetch_assoc()) {
                $employees[] = [
                    'IdOsoba' => $employeeRow['IdOsoba'],
                    'Imie' => $employeeRow['Imie'],
                    'Nazwisko' => $employeeRow['Nazwisko'],
                    'kolor' => $employeeRow['kolor']
                ];
            }

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

        return $events;
    }
    public function getEvent(int $eventId): array
    {
        $query = "
            SELECT w.IdWydarzenia, w.NazwaWydarzenia, w.IdFirma, w.DataPoczatek, w.DataKoniec, w.Miejsce, w.Komentarz, f.NazwaFirmy, w.Hotel, w.OsobaZarzadzajaca
            FROM wydarzenia w
            JOIN firma f ON w.IdFirma = f.IdFirma
            WHERE w.IdWydarzenia = ?
        ";
    
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows === 0) {
            return ['message' => 'Wydarzenie nie znalezione'];
        }
    
        $event = $result->fetch_assoc();
        $event['ListaPracownikow'] = $this->getEventEmployees($eventId);
    
        return $event;
    }
    
    private function getEventEmployees(int $eventId): array
    {
        $query = "
            SELECT o.Imie, o.Nazwisko, o.IdOsoba, o.kolor, wp.Dzien
            FROM wydarzeniapracownicy wp
            JOIN osoby o ON wp.IdOsoba = o.IdOsoba
            WHERE wp.IdWydarzenia = ?
        ";
    
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    
        return $employees;
    }
    
    
    
    public function updateEvent(int $eventId, array $data): array
{
    if (empty($data['firma']) || empty($data['nazwaWydarzenia']) || empty($data['miejsce']) || empty($data['data-poczatek'])) {
        return ['error' => 'Wypełnij wymagane pola'];
    }

    $firma = $this->connection->real_escape_string($data['firma']);
    $miejsce = $this->connection->real_escape_string($data['miejsce']);
    $nazwawydarzenia = $this->connection->real_escape_string($data['nazwaWydarzenia']);
    $hotel = $this->connection->real_escape_string($data['hotel'] ?? '');
    $osobazarzadzajaca = $this->connection->real_escape_string($data['osoba-zarzadzajaca'] ?? '');
    $dataPoczatek = $data['data-poczatek'];
    $dataKoniec = $data['data-koniec'] ?? $dataPoczatek;
    $komentarz = $this->connection->real_escape_string($data['komentarz'] ?? '');
    $pracownicy = $data['pracownicy'] ?? [];

    $result = $this->connection->query("SELECT * FROM wydarzenia WHERE IdWydarzenia = '$eventId'");
    if ($result->num_rows === 0) {
        return ['error' => 'Wydarzenie o podanym ID nie istnieje'];
    }

    $resultFirma = $this->connection->query("SELECT IdFirma FROM firma WHERE NazwaFirmy = '$firma'");
    if ($resultFirma->num_rows === 0) {
        return ['error' => 'Podana firma nie istnieje'];
    }

    $idFirma = $resultFirma->fetch_assoc()['IdFirma'];

    $query = "UPDATE wydarzenia 
            SET IdFirma = '$idFirma', 
                Miejsce = '$miejsce', 
                NazwaWydarzenia = '$nazwawydarzenia', 
                DataPoczatek = '$dataPoczatek', 
                DataKoniec = '$dataKoniec', 
                Komentarz = '$komentarz',
                Hotel = '$hotel',
                OsobaZarzadzajaca = '$osobazarzadzajaca'
            WHERE IdWydarzenia = '$eventId'";

    if (!$this->connection->query($query)) {
        return ['error' => 'Błąd podczas aktualizacji wydarzenia'];
    }

    $this->connection->query("DELETE FROM wydarzeniapracownicy WHERE IdWydarzenia = '$eventId'");

    foreach ($pracownicy as $pracownik) {
        $dniPracownika = $data['dni'][$pracownik] ?? [];

        if (!empty($dniPracownika)) {
            foreach ($dniPracownika as $dzien) {
                $dzien = $this->connection->real_escape_string($dzien);
                $this->connection->query("INSERT INTO wydarzeniapracownicy (IdWydarzenia, IdOsoba, Dzien) 
                                VALUES ('$eventId', '$pracownik', '$dzien')");
            }
        } else {
            $this->connection->query("INSERT INTO wydarzeniapracownicy (IdWydarzenia, IdOsoba, Dzien) 
                            VALUES ('$eventId', '$pracownik', '0')");
        }
    }

    return ['message' => 'Wydarzenie zostało zaktualizowane pomyślnie'];
}

    public function deleteEvent(int $eventId): bool
    {
        $deleteEmployeesQuery = "DELETE FROM wydarzeniapracownicy WHERE IdWydarzenia = ?";
        $stmt = $this->connection->prepare($deleteEmployeesQuery);
        $stmt->bind_param("i", $eventId);
        $stmt->execute();

        $deleteEventQuery = "DELETE FROM wydarzenia WHERE IdWydarzenia = ?";
        $stmt = $this->connection->prepare($deleteEventQuery);
        $stmt->bind_param("i", $eventId);
        return $stmt->execute();
    }

    public function addEvent(array $eventData): bool
    {
        $this->connection->begin_transaction();

        try {
            $firma = $this->connection->real_escape_string($eventData['firma']);
            $miejsce = $this->connection->real_escape_string($eventData['miejsce']);
            $nazwaWydarzenia = $this->connection->real_escape_string($eventData['nazwaWydarzenia']);
            $dataPoczatek = $eventData['data-poczatek'];
            $dataKoniec = $eventData['data-koniec'] ?? $dataPoczatek;
            $komentarz = $this->connection->real_escape_string($eventData['komentarz'] ?? '');
            $hotel = $this->connection->real_escape_string($eventData['hotel'] ?? '');
            $osobazarzadzajaca = $this->connection->real_escape_string($eventData['osoba-zarzadzajaca'] ?? '');
            $pracownicy = $eventData['pracownicy'] ?? [];

            $stmt = $this->connection->prepare("SELECT IdFirma FROM firma WHERE NazwaFirmy = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania: " . $this->connection->error);
            }

            $stmt->bind_param('s', $firma);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Podana firma nie istnieje");
            }

            $idFirma = $result->fetch_assoc()['IdFirma'];
            $stmt->close();

            $stmt = $this->connection->prepare("
                INSERT INTO wydarzenia (IdFirma, NazwaWydarzenia, Miejsce, DataPoczatek, DataKoniec, Komentarz, Hotel, OsobaZarzadzajaca) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania: " . $this->connection->error);
            }

            $stmt->bind_param('isssssss', $idFirma, $nazwaWydarzenia, $miejsce, $dataPoczatek, $dataKoniec, $komentarz, $hotel, $osobazarzadzajaca);
            if (!$stmt->execute()) {
                throw new Exception("Błąd podczas dodawania wydarzenia: " . $stmt->error);
            }

            $idWydarzenia = $stmt->insert_id;
            $stmt->close();

            foreach ($pracownicy as $pracownik) {
                $dniPracownika = $eventData['dni'][$pracownik] ?? [];

                if (!empty($dniPracownika)) {
                    foreach ($dniPracownika as $dzien) {
                        $stmt = $this->connection->prepare("
                            INSERT INTO wydarzeniapracownicy (IdWydarzenia, IdOsoba, Dzien) 
                            VALUES (?, ?, ?)
                        ");
                        if (!$stmt) {
                            throw new Exception("Błąd przygotowania zapytania: " . $this->connection->error);
                        }

                        $stmt->bind_param('iis', $idWydarzenia, $pracownik, $dzien);
                        if (!$stmt->execute()) {
                            throw new Exception("Błąd podczas przypisywania pracownika: " . $stmt->error);
                        }
                        $stmt->close();
                    }
                } else {
                    $stmt = $this->connection->prepare("
                        INSERT INTO wydarzeniapracownicy (IdWydarzenia, IdOsoba, Dzien) 
                        VALUES (?, ?, '0')
                    ");
                    if (!$stmt) {
                        throw new Exception("Błąd przygotowania zapytania: " . $this->connection->error);
                    }

                    $stmt->bind_param('ii', $idWydarzenia, $pracownik);
                    if (!$stmt->execute()) {
                        throw new Exception("Błąd podczas przypisywania pracownika: " . $stmt->error);
                    }
                    $stmt->close();
                }
            }

            $this->connection->commit();
            return true;
        } catch (Exception $e) {
            $this->connection->rollback();
            error_log("Nie udało się dodać wydarzenia: " . $e->getMessage());
            return false;
        }
    }
    public function saveEmployeeEventDays(int $userId, int $eventId, array $days): bool
    {
        $this->connection->begin_transaction();

        try {
            foreach ($days as $day) {
                $dzien = $this->connection->real_escape_string($day['dzień']);
                $idStanowiska = $day['idStanowiska'] ? $this->connection->real_escape_string($day['idStanowiska']) : 'NULL';
                $stawkaDzienna = $day['obecność'];
                $nadgodziny = $this->connection->real_escape_string($day['nadgodziny']);

                $query = "
                    INSERT INTO wydarzeniapracownicy (IdWydarzenia, IdOsoba, Dzien, IdStanowiska, StawkaDzienna, Nadgodziny)
                    VALUES ('$eventId', '$userId', '$dzien', $idStanowiska, '$stawkaDzienna', '$nadgodziny')
                    ON DUPLICATE KEY UPDATE 
                        IdStanowiska = VALUES(IdStanowiska),
                        StawkaDzienna = VALUES(StawkaDzienna),
                        Nadgodziny = VALUES(Nadgodziny)
                ";

                if (!$this->connection->query($query)) {
                    throw new Exception("Błąd zapisu: " . $this->connection->error);
                }
            }

            $this->connection->commit();
            return true;
        } catch (Exception $e) {
            $this->connection->rollback();
            throw $e;
        }
    }
    public function getEmployeePaymentsByMonth(int $employeeId, int $month, int $year): array {
        $query = "
            SELECT 
                e.IdWydarzenia, 
                e.NazwaWydarzenia, 
                wp.Dzien, 
                wp.IdStanowiska, 
                wp.Nadgodziny, 
                s.StawkaGodzinowa,
                s.NazwaStanowiska
            FROM wydarzeniapracownicy wp
            JOIN wydarzenia e ON wp.IdWydarzenia = e.IdWydarzenia
            JOIN stanowiska s ON wp.IdStanowiska = s.IdStanowiska
            WHERE wp.IdOsoba = ? 
            AND MONTH(wp.Dzien) = ? 
            AND YEAR(wp.Dzien) = ?
            ORDER BY wp.Dzien ASC
        ";
    
        $stmt = $this->db->prepare($query);
        $stmt->execute([$employeeId, $month, $year]);
    
        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $eventId = $row['IdWydarzenia'];
            if (!isset($events[$eventId])) {
                $events[$eventId] = [
                    'nazwa' => $row['NazwaWydarzenia'],
                    'dni' => [],
                    'suma' => 0,
                ];
            }
    
            $dzien = $row['Dzien'];
            $stawka = $row['StawkaGodzinowa'];
            $nadgodziny = $row['Nadgodziny'];
            $stawkaNadgodziny = $stawka * 1.25;
    
            $zarobekDzien = ($stawka * 12) + ($stawkaNadgodziny * $nadgodziny);
            $events[$eventId]['dni'][] = [
                'dzien' => $dzien,
                'stanowisko' => $row['NazwaStanowiska'],
                'stawka' => $stawka,
                'nadgodziny' => $nadgodziny,
                'zarobek' => $zarobekDzien,
            ];
    
            $events[$eventId]['suma'] += $zarobekDzien;
        }
    
        return $events;
    }
}