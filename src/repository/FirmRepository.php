<?php

class FirmRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = (new Database())->connect();
    }
    public function getAllFirms(): array
    {
        $query = "
            SELECT IdFirma, NazwaFirmy, AdresFirmy, NIP, Telefon
            FROM firma
        ";

        $result = $this->connection->query($query);
        if (!$result || $result->num_rows === 0) {
            return [];
        }

        $firms = [];
        while ($row = $result->fetch_assoc()) {
            $firms[] = [
                'IdFirma' => $row['IdFirma'],
                'NazwaFirmy' => $row['NazwaFirmy'],
                'AdresFirmy' => $row['AdresFirmy'],
                'NIP' => $row['NIP'],
                'Telefon' => $row['Telefon']
            ];
        }

        return $firms;
    }
    public function getFirm(int $firmId): array
    {
        $query = "
            SELECT * FROM firma WHERE IdFirma = ?
        ";

        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            return ['message' => 'Błąd podczas przygotowywania zapytania'];
        }

        $stmt->bind_param("i", $firmId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['message' => 'Firma nie znaleziona'];
        }

        $firm = [];

        while ($row = $result->fetch_assoc()) {
            if (empty($firm)) {
                $firm = [
                    'NazwaFirmy' => $row['NazwaFirmy'],
                    'AdresFirmy' => $row['AdresFirmy'],
                    'NIP' => $row['NIP'],
                    'Telefon' => $row['Telefon'],
                    'kolor' => $row['kolor']
                ];
            }
        }

        $stmt->close();

        return $firm;
    }
    public function deleteFirm(int $firmId): bool
    {
        try {
            $stmt = $this->connection->prepare("DELETE FROM firma WHERE IdFirma = ?");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania: " . $this->connection->error);
            }

            $stmt->bind_param('i', $firmId);
            if (!$stmt->execute()) {
                throw new Exception("Błąd podczas usuwania firmy: " . $stmt->error);
            }

            $stmt->close();
            return true;
        } catch (Exception $e) {
            error_log("Nie udało się usunąć firmy: " . $e->getMessage());
            return false;
        }
    }

    public function updateFirm(int $firmId, array $data): bool
    {
        $requiredFields = ['NazwaFirmy', 'AdresFirmy', 'Telefon', 'NIP', 'kolor'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Pole $field jest wymagane");
            }
        }

        $nazwaFirmy = $this->connection->real_escape_string($data['NazwaFirmy']);
        $adresFirmy = $this->connection->real_escape_string($data['AdresFirmy']);
        $telefon = $this->connection->real_escape_string($data['Telefon']);
        $nip = $this->connection->real_escape_string($data['NIP']);
        $kolor = $this->connection->real_escape_string($data['kolor']);
        $checkQuery = "SELECT * FROM firma WHERE IdFirma = $firmId";
        $result = $this->connection->query($checkQuery);

        if ($result->num_rows === 0) {
            throw new Exception("Firma o ID $firmId nie istnieje");
        }

        $updateQuery = "UPDATE firma 
                        SET NazwaFirmy = '$nazwaFirmy', 
                            AdresFirmy = '$adresFirmy', 
                            Telefon = '$telefon', 
                            NIP = '$nip', 
                            kolor = '$kolor' 
                        WHERE IdFirma = $firmId";

        if (!$this->connection->query($updateQuery)) {
            throw new Exception("Błąd podczas aktualizacji danych firmy: " . $this->connection->error);
        }

        return true;
    }
    public function getFirmsSummary(int $month, int $year): array
    {
        $query = "
        SELECT 
            f.NazwaFirmy,
            w.NazwaWydarzenia,
            SUM(
                CASE 
                    WHEN wp.StawkaDzienna = 1 THEN (so.Stawka * 12 + wp.Nadgodziny * so.Stawka * 1.25)
                    ELSE 0
                END
            ) AS SumaPracownikow,
            w.DodatkoweKoszta
        FROM wydarzenia w
        JOIN firma f ON w.IdFirma = f.IdFirma
        LEFT JOIN wydarzeniapracownicy wp ON w.IdWydarzenia = wp.IdWydarzenia
        LEFT JOIN stanowiskoosoba so ON so.IdStanowiska = wp.IdStanowiska AND so.IdOsoba = wp.IdOsoba
        WHERE MONTH(w.DataKoniec) = ? AND YEAR(w.DataKoniec) = ?
        GROUP BY f.NazwaFirmy, w.NazwaWydarzenia, w.DodatkoweKoszta
        ORDER BY f.NazwaFirmy, w.NazwaWydarzenia;
        ";

        $stmt = $this->connection->prepare($query);
        $stmt->bind_param('ii', $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();

        $firms = [];
        while ($row = $result->fetch_assoc()) {
            $firmName = $row['NazwaFirmy'];
            if (!isset($firms[$firmName])) {
                $firms[$firmName] = [
                    'wydarzenia' => [],
                    'suma' => 0
                ];
            }

            $sumaWydarzenia = $row['SumaPracownikow'] + $row['DodatkoweKoszta'];
            $firms[$firmName]['wydarzenia'][] = [
                'nazwa' => $row['NazwaWydarzenia'],
                'suma' => $sumaWydarzenia
            ];
            $firms[$firmName]['suma'] += $sumaWydarzenia;
        }

        return $firms;
    }
}