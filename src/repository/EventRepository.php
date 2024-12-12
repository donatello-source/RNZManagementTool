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
}