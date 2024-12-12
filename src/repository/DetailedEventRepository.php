<?php

class DetailedEventRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = (new Database())->connect();
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
}