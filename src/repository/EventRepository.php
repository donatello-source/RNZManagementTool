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
            SELECT w.idwydarzenia, w.nazwawydarzenia, w.idfirma, w.datapoczatek, w.datakoniec, w.miejsce, w.komentarz, f.nazwafirmy, w.hotel
            FROM wydarzenia w
            JOIN firma f ON w.idfirma = f.idfirma
            ORDER BY w.datapoczatek
        ";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            return [];
        }
    
        $stmt->execute();

         $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(function ($row) {
        return [
            'idwydarzenia' => $row['idwydarzenia'],
            'nazwawydarzenia' => $row['nazwawydarzenia'],
            'miejsce' => $row['miejsce'],
            'nazwafirmy' => $row['nazwafirmy'],
            'datapoczatek' => $row['datapoczatek'],
            'datakoniec' => $row['datakoniec'],
            'komentarz' => $row['komentarz']
        ];
    }, $events);
    }
    public function getDetailedEvents(): array
    {
        $query = "
            SELECT w.idwydarzenia, w.nazwawydarzenia, w.idfirma, w.datapoczatek, w.datakoniec, w.miejsce, w.komentarz, f.nazwafirmy
            FROM wydarzenia w
            JOIN firma f ON w.idfirma = f.idfirma
        ";

        $result = $this->connection->query($query);
        if (!$result) {
            return [];
        }

        $events = [];
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $eventId = $row['idwydarzenia'];

            $employeeQuery = "
                SELECT DISTINCT o.imie, o.nazwisko, o.idosoba, o.kolor
                FROM wydarzeniapracownicy wp
                JOIN osoby o ON wp.idosoba = o.idosoba
                WHERE wp.idwydarzenia = :eventId
            ";

            $employeeStmt = $this->connection->prepare($employeeQuery);
            $employeeStmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
            $employeeStmt->execute();
            $employees = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);

            $events[] = [
                'idwydarzenia' => $row['idwydarzenia'],
                'nazwawydarzenia' => $row['nazwawydarzenia'],
                'miejsce' => $row['miejsce'],
                'nazwafirmy' => $row['nazwafirmy'],
                'datapoczatek' => $row['datapoczatek'],
                'datakoniec' => $row['datakoniec'],
                'listapracownikow' => array_map(function ($employeeRow) {
                    return [
                        'idosoba' => $employeeRow['idosoba'],
                        'imie' => $employeeRow['imie'],
                        'nazwisko' => $employeeRow['nazwisko'],
                        'kolor' => $employeeRow['kolor']
                    ];
                }, $employees),
                'komentarz' => $row['komentarz']
            ];
        }
        return $events;
    }
    public function getEvent(int $eventId): array
    {
        $query = "
            SELECT w.idwydarzenia, w.nazwawydarzenia, w.idfirma, w.datapoczatek, w.datakoniec, w.miejsce, w.komentarz, f.nazwafirmy, w.hotel, w.osobazarzadzajaca
            FROM wydarzenia w
            JOIN firma f ON w.idfirma = f.idfirma
            WHERE w.idwydarzenia = ?
        ";
    
        $stmt = $this->connection->prepare($query);
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$event) {
            return ['message' => 'Wydarzenie nie znalezione'];
        }
        
        $event['listapracownikow'] = $this->getEventEmployees($eventId);
        
        return $event;
    }
    
    private function getEventEmployees(int $eventId): array
    {
        $query = "
            SELECT o.imie, o.nazwisko, o.idosoba, o.kolor, wp.dzien
            FROM wydarzeniapracownicy wp
            JOIN osoby o ON wp.idosoba = o.idosoba
            WHERE wp.idwydarzenia = ?
        ";
    
        $stmt = $this->connection->prepare($query);
        $stmt->execute([$eventId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $result ?: [];
    }
    
    
    
    public function updateEvent(int $eventId, array $data): array
    {
        if (empty($data['firma']) || empty($data['nazwaWydarzenia']) || empty($data['miejsce']) || empty($data['data-poczatek'])) {
            return ['error' => 'Wypełnij wymagane pola'];
        }
        $firma = $data['firma'];
        $miejsce = $data['miejsce'];
        $nazwawydarzenia = $data['nazwaWydarzenia'];
        $hotel = $data['hotel'] ?? '';
        $osobazarzadzajaca = $data['osoba-zarzadzajaca'] ?? '';
        $datapoczatek = $data['data-poczatek'];
        $datakoniec = $data['data-koniec'] ?? $datapoczatek;
        $komentarz = $data['komentarz'] ?? '';
        $pracownicy = $data['pracownicy'] ?? [];
    
        $query = "SELECT * FROM wydarzenia WHERE idwydarzenia = :eventId";
        $result = $this->connection->prepare($query);
        $result->bindParam(':eventId', $eventId, PDO::PARAM_INT);
        $result->execute();
    
        if ($result->rowCount() == 0) {
            return ['error' => 'Wydarzenie o podanym ID nie istnieje'];
        }
            $queryFirma = "SELECT idfirma FROM firma WHERE nazwafirmy = :firmaNazwa";
        $resultFirma = $this->connection->prepare($queryFirma);
        $resultFirma->bindParam(':firmaNazwa', $firma, PDO::PARAM_STR);
        $resultFirma->execute();
    
        if ($resultFirma->rowCount() === 0) {
            return ['error' => 'Podana firma nie istnieje'];
        }
    
        $idfirma = $resultFirma->fetch(PDO::FETCH_ASSOC)['idfirma'];
            $queryUpdate = "UPDATE wydarzenia 
                        SET idfirma = :idfirma, 
                            miejsce = :miejsce, 
                            nazwawydarzenia = :nazwawydarzenia, 
                            datapoczatek = :datapoczatek, 
                            datakoniec = :datakoniec, 
                            komentarz = :komentarz,
                            hotel = :hotel,
                            osobazarzadzajaca = :osobazarzadzajaca
                        WHERE idwydarzenia = :eventId";
    
        $stmt = $this->connection->prepare($queryUpdate);
        $stmt->bindParam(':idfirma', $idfirma, PDO::PARAM_INT);
        $stmt->bindParam(':miejsce', $miejsce, PDO::PARAM_STR);
        $stmt->bindParam(':nazwawydarzenia', $nazwawydarzenia, PDO::PARAM_STR);
        $stmt->bindParam(':datapoczatek', $datapoczatek, PDO::PARAM_STR);
        $stmt->bindParam(':datakoniec', $datakoniec, PDO::PARAM_STR);
        $stmt->bindParam(':komentarz', $komentarz, PDO::PARAM_STR);
        $stmt->bindParam(':hotel', $hotel, PDO::PARAM_STR);
        $stmt->bindParam(':osobazarzadzajaca', $osobazarzadzajaca, PDO::PARAM_STR);
        $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            return ['error' => 'Błąd podczas aktualizacji wydarzenia'];
        }
    
        $this->connection->prepare("DELETE FROM wydarzeniapracownicy WHERE idwydarzenia = :eventId")
            ->execute([':eventId' => $eventId]);
            foreach ($pracownicy as $pracownik) {
            $dnipracownika = $data['dni'][$pracownik] ?? [];
    
            if (!empty($dnipracownika)) {
                foreach ($dnipracownika as $dzien) {
                    $queryPracownik = "INSERT INTO wydarzeniapracownicy (idwydarzenia, idosoba, dzien) 
                                    VALUES (:eventId, :pracownik, :dzien)";
                    $stmtPracownik = $this->connection->prepare($queryPracownik);
                    $stmtPracownik->bindParam(':eventId', $eventId, PDO::PARAM_INT);
                    $stmtPracownik->bindParam(':pracownik', $pracownik, PDO::PARAM_INT);
                    $stmtPracownik->bindParam(':dzien', $dzien, PDO::PARAM_STR);
                    $stmtPracownik->execute();
                }
            } else {
                $queryPracownik = "INSERT INTO wydarzeniapracownicy (idwydarzenia, idosoba, dzien) 
                                VALUES (:eventId, :pracownik, '0')";
                $stmtPracownik = $this->connection->prepare($queryPracownik);
                $stmtPracownik->bindParam(':eventId', $eventId, PDO::PARAM_INT);
                $stmtPracownik->bindParam(':pracownik', $pracownik, PDO::PARAM_INT);
                $stmtPracownik->execute();
            }
        }
    
        return ['message' => 'Wydarzenie zostało zaktualizowane pomyślnie'];
    }
    public function deleteEvent(int $eventId): bool
    {
        $deleteEmployeesQuery = "DELETE FROM wydarzeniapracownicy WHERE idwydarzenia = :eventId";
        $stmt = $this->connection->prepare($deleteEmployeesQuery);
        $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
        $stmt->execute();

        $deleteEventQuery = "DELETE FROM wydarzenia WHERE idwydarzenia = :eventId";
        $stmt = $this->connection->prepare($deleteEventQuery);
        $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function addEvent(array $eventData): bool
    {
        $this->connection->beginTransaction();
    
        try {
            $firma = $eventData['firma'];
            $miejsce = $eventData['miejsce'];
            $nazwawydarzenia = $eventData['nazwaWydarzenia'];
            $datapoczatek = $eventData['data-poczatek'];
            $datakoniec = $eventData['data-koniec'] ?? $datapoczatek;
            $komentarz = $eventData['komentarz'] ?? '';
            $hotel = $eventData['hotel'] ?? '';
            $osobazarzadzajaca = $eventData['osoba-zarzadzajaca'] ?? '';
            $pracownicy = $eventData['pracownicy'] ?? [];
    
            $stmt = $this->connection->prepare("SELECT idfirma FROM firma WHERE nazwafirmy = :firma");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania: " . $this->connection->errorInfo());
            }
    
            $stmt->bindParam(':firma', $firma, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$result) {
                throw new Exception("Podana firma nie istnieje");
            }
    
            $idfirma = $result['idfirma'];
    
            $stmt = $this->connection->prepare("
                INSERT INTO wydarzenia (idfirma, nazwawydarzenia, miejsce, datapoczatek, datakoniec, komentarz, hotel, osobazarzadzajaca)
                VALUES (:idfirma, :nazwawydarzenia, :miejsce, :datapoczatek, :datakoniec, :komentarz, :hotel, :osobazarzadzajaca)
            ");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania: " . $this->connection->errorInfo());
            }
    
            $stmt->bindParam(':idfirma', $idfirma, PDO::PARAM_INT);
            $stmt->bindParam(':nazwawydarzenia', $nazwawydarzenia, PDO::PARAM_STR);
            $stmt->bindParam(':miejsce', $miejsce, PDO::PARAM_STR);
            $stmt->bindParam(':datapoczatek', $datapoczatek, PDO::PARAM_STR);
            $stmt->bindParam(':datakoniec', $datakoniec, PDO::PARAM_STR);
            $stmt->bindParam(':komentarz', $komentarz, PDO::PARAM_STR);
            $stmt->bindParam(':hotel', $hotel, PDO::PARAM_STR);
            $stmt->bindParam(':osobazarzadzajaca', $osobazarzadzajaca, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                throw new Exception("Błąd podczas dodawania wydarzenia: " . $stmt->errorInfo());
            }
    
            $idwydarzenia = $this->connection->lastInsertId();
                foreach ($pracownicy as $pracownik) {
                $dnipracownika = $eventData['dni'][$pracownik] ?? [];
    
                if (!empty($dnipracownika)) {
                    foreach ($dnipracownika as $dzien) {
                        $stmt = $this->connection->prepare("
                            INSERT INTO wydarzeniapracownicy (idwydarzenia, idosoba, dzien)
                            VALUES (:idwydarzenia, :pracownik, :dzien)
                        ");
                        if (!$stmt) {
                            throw new Exception("Błąd przygotowania zapytania: " . $this->connection->errorInfo());
                        }
    
                        $stmt->bindParam(':idwydarzenia', $idwydarzenia, PDO::PARAM_INT);
                        $stmt->bindParam(':pracownik', $pracownik, PDO::PARAM_INT);
                        $stmt->bindParam(':dzien', $dzien, PDO::PARAM_STR);
    
                        if (!$stmt->execute()) {
                            throw new Exception("Błąd podczas przypisywania pracownika: " . $stmt->errorInfo());
                        }
                    }
                } else {
                    $stmt = $this->connection->prepare("
                        INSERT INTO wydarzeniapracownicy (idwydarzenia, idosoba, dzien)
                        VALUES (:idwydarzenia, :pracownik, '0')
                    ");
                    if (!$stmt) {
                        throw new Exception("Błąd przygotowania zapytania: " . $this->connection->errorInfo());
                    }
    
                    $stmt->bindParam(':idwydarzenia', $idwydarzenia, PDO::PARAM_INT);
                    $stmt->bindParam(':pracownik', $pracownik, PDO::PARAM_INT);
    
                    if (!$stmt->execute()) {
                        throw new Exception("Błąd podczas przypisywania pracownika: " . $stmt->errorInfo());
                    }
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
        $this->connection->beginTransaction();
    
        try {
            foreach ($days as $day) {
                $query = "
                    INSERT INTO wydarzeniapracownicy (idwydarzenia, idosoba, dzien, idstanowiska, stawkadzienna, nadgodziny)
                    VALUES (:eventId, :userId, :dzien, :idstanowiska, :stawkadzienna, :nadgodziny)
                    ON CONFLICT (idwydarzenia, idosoba, dzien) DO UPDATE SET
                        idstanowiska = EXCLUDED.idstanowiska,
                        stawkadzienna = EXCLUDED.stawkadzienna,
                        nadgodziny = EXCLUDED.nadgodziny;
                ";
    
                $stmt = $this->connection->prepare($query);
    
                if (!$stmt) {
                    throw new Exception("Błąd przygotowania zapytania: " . implode(", ", $this->connection->errorInfo()));
                }
    
                $dzien = $day['dzień'];
                $idstanowiska = $day['idstanowiska'] ?? null;
                $stawkadzienna = $day['obecność'];
                $nadgodziny = $day['nadgodziny'];
    
                $stmt->bindParam(':eventId', $eventId, PDO::PARAM_INT);
                $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                $stmt->bindParam(':dzien', $dzien, PDO::PARAM_STR);
                $stmt->bindParam(':idstanowiska', $idstanowiska, $idstanowiska === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
                $stmt->bindParam(':stawkadzienna', $stawkadzienna, PDO::PARAM_BOOL);
                $stmt->bindParam(':nadgodziny', $nadgodziny, PDO::PARAM_INT);
    
                if (!$stmt->execute()) {
                    throw new Exception("Błąd zapisu: " . implode(", ", $stmt->errorInfo()));
                }
            }
    
            $this->connection->commit();
            return true;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
    
    public function getEmployeePayouts(int $employeeId, int $month, int $year): array
    {
        $query = "
        SELECT
            idwydarzenia,
            nazwawydarzenia,
            idosoba,
            dzien,
            stawkagodzinowa,
            stawkadzienna,
            nadgodziny,
            idstanowiska
        FROM (
            SELECT
                wp.idwydarzenia,
                w.nazwawydarzenia,
                wp.idosoba,
                wp.dzien,
                so.stawka AS stawkagodzinowa,
                wp.stawkadzienna,
                wp.nadgodziny,
                wp.idstanowiska,
                ROW_NUMBER() OVER (
                    PARTITION BY wp.idwydarzenia, wp.dzien
                    ORDER BY so.stawka DESC, wp.stawkadzienna DESC, wp.nadgodziny DESC
                ) AS RowNum
            FROM wydarzeniapracownicy wp
            JOIN wydarzenia w ON wp.idwydarzenia = w.idwydarzenia
            LEFT JOIN stanowiskoosoba so ON so.idstanowiska = wp.idstanowiska AND so.idosoba = wp.idosoba
            WHERE so.stawka > 0
            AND wp.idosoba = :employeeId
            AND EXTRACT(MONTH FROM w.datakoniec) = :month
            AND EXTRACT(YEAR FROM w.datakoniec) = :year
        ) AS DistinctAssignments
        WHERE RowNum = 1
        ORDER BY dzien, idwydarzenia;
        ";
    
        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            throw new Exception("Błąd w przygotowaniu zapytania: " . implode(", ", $this->connection->errorInfo()));
        }
    
        $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':month', $month, PDO::PARAM_INT);
        $stmt->bindParam(':year', $year, PDO::PARAM_INT);
        $stmt->execute();
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($result)) {
            return [];
        }
        if ($result === false) {
                throw new Exception("Błąd podczas wykonywania zapytania: " . implode(", ", $stmt->errorInfo()));
        }
    
        $events = [];
        foreach ($result as $row) {
            $eventId = $row['idwydarzenia'];
            if (!isset($events[$eventId])) {
                $events[$eventId] = [
                    'nazwa' => $row['nazwawydarzenia'],
                    'dni' => [],
                    'suma' => 0,
                ];
            }
    
            $stawka = $row['stawkagodzinowa'];
            $nadgodziny = $row['nadgodziny'];
            $stawkanadgodziny = $stawka * 1.25;
    
            $zarobekdzien = ($stawka * 12) + ($stawkanadgodziny * $nadgodziny);
            $events[$eventId]['dni'][] = [
                'dzien' => $row['dzien'],
                'stanowisko' => $row['idstanowiska'],
                'stawka' => $stawka,
                'nadgodziny' => $nadgodziny,
                'zarobek' => $zarobekdzien,
            ];
    
            $events[$eventId]['suma'] += $zarobekdzien;
        }
    
        return $events;
    }
    
    public function getEventsSummary(int $month, int $year): array
    {
        $query = "
        SELECT 
            w.nazwawydarzenia,
            w.datakoniec,
            wp.idosoba,
            CONCAT(o.imie, ' ', o.nazwisko) AS pracownik,
            SUM(
                CASE 
                    WHEN wp.stawkadzienna = true THEN (so.stawka * 12 + wp.nadgodziny * so.stawka * 1.25)
                    ELSE 0
                END
            ) AS sumapracownikow,
            w.dodatkowekoszta
        FROM wydarzenia w
        LEFT JOIN wydarzeniapracownicy wp ON w.idwydarzenia = wp.idwydarzenia
        LEFT JOIN osoby o ON wp.idosoba = o.idosoba
        LEFT JOIN stanowiskoosoba so ON so.idstanowiska = wp.idstanowiska AND so.idosoba = wp.idosoba
        WHERE EXTRACT(MONTH FROM w.datakoniec) = :month AND EXTRACT(YEAR FROM w.datakoniec) = :year
        GROUP BY w.idwydarzenia, w.nazwawydarzenia, w.datakoniec, wp.idosoba, o.imie, o.nazwisko, w.dodatkowekoszta
        ORDER BY w.nazwawydarzenia, wp.idosoba;
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
    
        $events = [];
        foreach ($result as $row) {
            $eventName = $row['nazwawydarzenia'];
            if (!isset($events[$eventName])) {
                $events[$eventName] = [
                    'data' => $row['datakoniec'],
                    'pracownicy' => [],
                    'suma' => 0
                ];
            }
    
            $events[$eventName]['pracownicy'][] = [
                'pracownik' => $row['pracownik'],
                'suma' => $row['sumapracownikow']
            ];
            $events[$eventName]['suma'] += $row['sumapracownikow'] + $row['dodatkowekoszta'];
        }
    
        return $events;
    }
    
    
}