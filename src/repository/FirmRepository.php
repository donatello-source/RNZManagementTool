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
            SELECT idfirma, nazwafirmy, adresfirmy, nip, telefon
            FROM firma
        ";

        $stmt = $this->connection->prepare($query);

        if (!$stmt) {
            return [];
        }
    
        $stmt->execute();
    
        $firms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        return array_map(function ($row) {
            return [
                'idfirma' => $row['idfirma'],
                'nazwafirmy' => $row['nazwafirmy'],
                'adresfirmy' => $row['adresfirmy'],
                'nip' => $row['nip'],
                'telefon' => $row['telefon']
            ];
        }, $firms);
    }
    
        public function getFirm(int $firmId): array
    {
        $query = "
            SELECT * FROM firma WHERE idfirma = :firmId
        ";

        $stmt = $this->connection->prepare($query);
        if (!$stmt) {
            return ['message' => 'Błąd podczas przygotowywania zapytania'];
        }

        $stmt->bindParam(':firmId', $firmId, PDO::PARAM_INT);
        $stmt->execute();

        $firm = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$firm) {
            return ['message' => 'Firma nie znaleziona'];
        }

        return [
            'nazwafirmy' => $firm['nazwafirmy'],
            'adresfirmy' => $firm['adresfirmy'],
            'nip' => $firm['nip'],
            'telefon' => $firm['telefon'],
            'kolor' => $firm['kolor']
        ];
    }

    public function deleteFirm(int $firmId): bool
    {
        try {
            $stmt = $this->connection->prepare("DELETE FROM firma WHERE idfirma = :firmId");
            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania: " . implode(", ", $this->connection->errorInfo()));
            }

            $stmt->bindParam(':firmId', $firmId, PDO::PARAM_INT);
            if (!$stmt->execute()) {
                throw new Exception("Błąd podczas usuwania firmy: " . implode(", ", $stmt->errorInfo()));
            }

            return true;
        } catch (Exception $e) {
            error_log("Nie udało się usunąć firmy: " . $e->getMessage());
            return false;
        }
    }

    public function updateFirm(int $firmId, array $data): bool
    {
        $requiredFields = ['nazwafirmy', 'nip'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new Exception("Pole $field jest wymagane");
            }
        }

        try {
            $stmt = $this->connection->prepare("
                UPDATE firma 
                SET nazwafirmy = :nazwafirmy, 
                    adresfirmy = :adresfirmy, 
                    telefon = :telefon, 
                    nip = :nip, 
                    kolor = :kolor 
                WHERE idfirma = :firmId
            ");

            if (!$stmt) {
                throw new Exception("Błąd przygotowania zapytania: " . implode(", ", $this->connection->errorInfo()));
            }

            $stmt->bindParam(':nazwafirmy', $data['nazwafirmy']);
            $stmt->bindParam(':adresfirmy', $data['adresfirmy']);
            $stmt->bindParam(':telefon', $data['telefon']);
            $stmt->bindParam(':nip', $data['nip']);
            $stmt->bindParam(':kolor', $data['kolor']);
            $stmt->bindParam(':firmId', $firmId, PDO::PARAM_INT);

            if (!$stmt->execute()) {
                throw new Exception("Błąd podczas aktualizacji danych firmy: " . implode(", ", $stmt->errorInfo()));
            }

            return true;
        } catch (Exception $e) {
            error_log("Nie udało się zaktualizować danych firmy: " . $e->getMessage());
            return false;
        }
    }



    public function getFirmsSummary(int $month, int $year): array
    {
        $query = "
        SELECT 
            f.nazwafirmy,
            w.nazwawydarzenia,
            SUM(
                CASE 
                    WHEN wp.stawkadzienna = true THEN (so.stawka * 12 + wp.nadgodziny * so.stawka * 1.25)
                    ELSE 0
                END
            ) AS sumapracownikow,
            w.dodatkowekoszta
        FROM wydarzenia w
        JOIN firma f ON w.idfirma = f.idfirma
        LEFT JOIN wydarzeniapracownicy wp ON w.idwydarzenia = wp.idwydarzenia
        LEFT JOIN stanowiskoosoba so ON so.idstanowiska = wp.idstanowiska AND so.idosoba = wp.idosoba
        WHERE EXTRACT(MONTH FROM w.datakoniec) = :month AND EXTRACT(YEAR FROM w.datakoniec) = :year
        GROUP BY f.nazwafirmy, w.nazwawydarzenia, w.dodatkowekoszta
        ORDER BY f.nazwafirmy, w.nazwawydarzenia;
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
    
        $firms = [];
        foreach ($result as $row) {
            $firmName = $row['nazwafirmy'];
            if (!isset($firms[$firmName])) {
                $firms[$firmName] = [
                    'wydarzenia' => [],
                    'suma' => 0
                ];
            }
    
            $sumaWydarzenia = $row['sumapracownikow'] + $row['dodatkowekoszta'];
            $firms[$firmName]['wydarzenia'][] = [
                'nazwa' => $row['nazwawydarzenia'],
                'suma' => $sumaWydarzenia
            ];
            $firms[$firmName]['suma'] += $sumaWydarzenia;
        }
    
        return $firms;
    }
    
}