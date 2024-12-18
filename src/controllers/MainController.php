<?php

require_once 'AppController.php';
require_once __DIR__ . '/../repository/EventRepository.php';
require_once __DIR__ . '/../repository/DetailedEventRepository.php';
require_once __DIR__ . '/../repository/EmployeeRepository.php';
require_once __DIR__ . '/../repository/FirmRepository.php';
require_once __DIR__ . '/../repository/PositionRepository.php';

class MainController extends AppController
{
    private $eventRepository;
    private $detailedEventRepository;
    private $employeeRepository;
    private $firmRepository;
    private $positionRepository;

    public function __construct()
    {
        parent::__construct();
        $this->eventRepository = new EventRepository();
        $this->employeeRepository = new EmployeeRepository();
        $this->firmRepository = new FirmRepository();
        $this->positionRepository = new PositionRepository();
    }

    public function main()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /RNZManagementTool/');
            exit();
        }

        $this->render('main', ['user' => $_SESSION['user']]);
    }

    public function getEvents()
    {
        $events = $this->eventRepository->getEvents();
        echo json_encode($events);
    }
    public function getDetailedEvents()
    {
        $detailedEvents = $this->eventRepository->getDetailedEvents();
        echo json_encode($detailedEvents);
    }
    public function getAllEmployees()
    {
        $employees = $this->employeeRepository->getAllEmployees();
        echo json_encode($employees);
    }
    public function getAllDetailedEmployees()
    {
        $detailedEmployees = $this->employeeRepository->getAllDetailedEmployees();
        echo json_encode($detailedEmployees);
    }
    public function getEmployee()
    {
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'Brak ID pracownika']);
            return;
        }

        $employeeId = (int)$_GET['id'];
        $employee = $this->employeeRepository->getEmployee($employeeId);

        if (!$employee) {
            echo json_encode(['error' => 'Pracownik nie został znaleziony']);
            return;
        }

        echo json_encode($employee);
    }
    public function addUser()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($data['imie'], $data['nazwisko'], $data['email'], $data['haslo'])) {
            echo json_encode(['error' => 'Nieprawidłowe dane wejściowe']);
            return;
        }
        $imie = $data['imie'];
        $nazwisko = $data['nazwisko'];
        $email = $data['email'];
        $haslo = $data['haslo'];

        try {
            $employeeRepository = new EmployeeRepository();
            $success = $employeeRepository->addUser($imie, $nazwisko, $email, $haslo);

            if ($success) {
                echo json_encode(['message' => 'Użytkownik został dodany pomyślnie']);
            } else {
                echo json_encode(['error' => 'Błąd podczas dodawania użytkownika']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }



    public function getAllFirms()
    {
        $firms = $this->firmRepository->getAllFirms();
        echo json_encode($firms);
    }
    public function getFirm()
    {
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'Brak ID firmy']);
            return;
        }

        $firmId = (int)$_GET['id'];
        $firm = $this->firmRepository->getFirm($firmId);

        if (!$firm) {
            echo json_encode(['error' => 'Firma nie została znaleziona']);
            return;
        }

        echo json_encode($firm);
    }
    public function getEvent()
    {
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'Brak ID wydarzenia']);
            return;
        }

        $eventId = (int)$_GET['id'];
        $event = $this->eventRepository->getEvent($eventId);

        if (!$event) {
            echo json_encode(['error' => 'Wydarzenie nie zostało znalezione']);
            return;
        }

        echo json_encode($event);
    }
    public function getEmployeeEvents()
    {
        session_start();
        if (!isset($_SESSION['user'])) {
            echo json_encode(['error' => 'Sesja nie istnieje lub brak użytkownika']);
            exit;
        }

        $employeeId = $_SESSION['user']['id'];
        $employeeRepository = new EmployeeRepository();
        $events = $employeeRepository->getEmployeeEvents($employeeId);

        if (!$events) {
            echo json_encode(['error' => 'Wydarzenia nie zostały znalezione']);
            return;
        }
        echo json_encode($events);
    }
    public function getEmployeePositions()
    {
        session_start();
        if (!isset($_SESSION['user'])) {
            echo json_encode(['error' => 'Sesja nie istnieje lub brak użytkownika']);
            exit;
        }

        $employeeId = $_SESSION['user']['id'];
        $employeeRepository = new EmployeeRepository();
        $positions = $employeeRepository->getEmployeePositions($employeeId);

        if (!$positions) {
            echo json_encode(['error' => 'Wydarzenia nie zostały znalezione']);
            return;
        }
        echo json_encode($positions);
    }

    public function updateEvent()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!$data || !isset($_GET['id'])) {
            echo json_encode(['error' => 'Nieprawidłowe dane wejściowe']);
            return;
        }

        $eventId = (int)$_GET['id'];

        try {
            $success = $this->eventRepository->updateEvent($eventId, $data);
            if ($success) {
                echo json_encode(['message' => 'Wydarzenie zostało zaktualizowane']);
            } else {
                echo json_encode(['error' => 'Błąd podczas aktualizacji wydarzenia']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function deleteEvent()
    {
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'Brak ID wydarzenia']);
            return;
        }

        $eventId = (int)$_GET['id'];

        $success = $this->eventRepository->deleteEvent($eventId);

        if ($success) {
            echo json_encode(['message' => 'Wydarzenie zostało usunięte pomyślnie']);
        } else {
            echo json_encode(['error' => 'Błąd podczas usuwania wydarzenia']);
        }
    }
    public function deleteFirm()
    {
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'Brak ID wydarzenia']);
            return;
        }

        $firmId = (int)$_GET['id'];

        $success = $this->firmRepository->deleteFirm($firmId);

        if ($success) {
            echo json_encode(['message' => 'Firma została usunięta pomyślnie']);
        } else {
            echo json_encode(['error' => 'Błąd podczas usuwania firmy']);
        }
    }
    public function addEvent()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            echo json_encode(['error' => 'Nieprawidłowe dane wejściowe']);
            exit;
        }
        
        $eventRepository = new EventRepository();
        if ($eventRepository->addEvent($data)) {
            echo json_encode(['message' => 'Wydarzenie zostało utworzone pomyślnie']);
        } else {
            echo json_encode(['error' => 'Nie udało się dodać wydarzenia']);
        }
        
    }
    public function deleteEmployee()
    {
        $employeeId = $_GET['id'] ?? null;
        if (!$employeeId) {
            echo json_encode(['error' => 'Brak parametru ID']);
            exit;
        }
        
        $employeeRepository = new EmployeeRepository();
        if ($employeeRepository->deleteEmployee((int)$employeeId)) {
            echo json_encode(['success' => true, 'message' => 'Pracownik został usunięty']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nie udało się usunąć pracownika']);
        }
    }
    public function getEmployeesPositions()
    {
        try {
            $employeeRepository = new EmployeeRepository();
            $positions = $employeeRepository->getEmployeesPositions();

            if ($positions) {
                echo json_encode($positions);
            } else {
                echo json_encode(['error' => 'Brak przypisanych stanowisk dla tego pracownika']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    public function updateEmployeesPositions()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        try {
            $employeeRepository = new EmployeeRepository();
            $success = $employeeRepository->updateEmployeesPositions($data);

            if ($success) {
                echo json_encode(['message' => 'Stanowiska pracownika zostały zaktualizowane']);
            } else {
                echo json_encode(['error' => 'Błąd podczas aktualizacji stanowisk']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    public function updateEmployee()
    {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                echo json_encode(['error' => 'Nieprawidłowe dane wejściowe']);
                return;
            }

            $employeeId = $_GET['id'] ?? null;
            if (!$employeeId) {
                echo json_encode(['error' => 'Brak ID pracownika']);
                return;
            }

            $employeeId = (int) $employeeId;

            $this->employeeRepository->updateEmployee($employeeId, $data);

            echo json_encode(['message' => 'Dane pracownika zostały zaktualizowane pomyślnie']);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    public function updateFirm()
    {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data) {
                echo json_encode(['error' => 'Nieprawidłowe dane wejściowe']);
                return;
            }

            $firmId = $_GET['id'] ?? null;
            if (!$firmId) {
                echo json_encode(['error' => 'Brak ID firmy']);
                return;
            }

            $firmId = (int)$firmId;

            $this->firmRepository->updateFirm($firmId, $data);

            echo json_encode(['message' => 'Dane firmy zostały zaktualizowane pomyślnie']);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    public function saveEmployeeEventDays()
    {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data || !isset($data['dniPracy'], $data['idWydarzenia'])) {
                echo json_encode(['error' => 'Nieprawidłowe dane wejściowe']);
                return;
            }

            session_start();
            if (!isset($_SESSION['user'])) {
                echo json_encode(['error' => 'Sesja nie istnieje lub brak użytkownika']);
                exit;
            }
    
            $employeeId = $_SESSION['user']['id'];
            $eventId = $data['idWydarzenia'];
            $this->eventRepository->saveEmployeeEventDays($employeeId, $eventId, $data['dniPracy']);

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getEmployeePayouts(): void {
        session_start();
        if (!isset($_SESSION['user'])) {
            echo json_encode(['error' => 'Sesja nie istnieje lub brak użytkownika']);
            exit;
        }
    
        $employeeId = $_SESSION['user']['id'];
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
            $payouts = $this->eventRepository->getEmployeePayouts((int)$employeeId, (int)$month, (int)$year);
            if (empty($payouts)) {
            echo json_encode(['error' => 'Brak danych o wypłatach za wybrany miesiąc']);
            exit;
        }
            echo json_encode([
            'payouts' => $payouts,
            'month' => $month,
            'year' => $year,
            'summary' => number_format(array_sum(array_column($payouts, 'suma')), 2)
        ]);
    }
    public function getSummary(): void
    {
        session_start();
        if (!isset($_SESSION['user'])) {
            echo json_encode(['error' => 'Sesja nie istnieje lub brak użytkownika']);
            exit;
        }
    
        $type = $_GET['type'] ?? null;
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
    
        if (!$type) {
            echo json_encode(['error' => 'Nie podano typu podsumowania']);
            exit;
        }
    
        $data = [];
        switch ($type) {
            case 'events':
                $data = $this->eventRepository->getEventsSummary((int)$month, (int)$year);
                if (empty($data)) {
                    echo json_encode(['error' => 'Brak danych o wydarzeniach za wybrany miesiąc']);
                    exit;
                }
                break;
    
            case 'firms':
                $data = $this->firmRepository->getFirmsSummary((int)$month, (int)$year);
                if (empty($data)) {
                    echo json_encode(['error' => 'Brak danych o firmach za wybrany miesiąc']);
                    exit;
                }
                break;
    
            case 'employees':
                $data = $this->employeeRepository->getEmployeesSummary((int)$month, (int)$year);
                if (empty($data)) {
                    echo json_encode(['error' => 'Brak danych o pracownikach za wybrany miesiąc']);
                    exit;
                }
                break;
    
            default:
                echo json_encode(['error' => 'Nieznany typ podsumowania']);
                exit;
        }
    
        echo json_encode([
            'type' => $type,
            'data' => $data,
            'month' => $month,
            'year' => $year,
        ]);
        exit;
    }
}