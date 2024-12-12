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
}