<?php

require_once 'AppController.php';
require_once __DIR__ . '/../models/User.php';

class SecurityController extends AppController
{
    public function index()
    {
        require_once 'public/views/index.php';
    }

    public function logout()
    {
        session_start();
        session_destroy();
        header('Location: /RNZManagementTool/');
        exit();
    }
    public function login()
    {
        session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['email']) && isset($_POST['password'])) {
                $email = $_POST['email'];
                $password = $_POST['password'];
                $mysqli = new mysqli('localhost', 'root', '', 'rnzmanago');
                if ($mysqli->connect_error) {
                    die("Błąd połączenia z bazą danych: " . $mysqli->connect_error);
                }
                $query = "SELECT * FROM osoby WHERE Email = ?";
                $stmt = $mysqli->prepare($query);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    if ($password == $user['Haslo'] && $user['Status'] != null) {
                        $_SESSION['user'] = [
                            'id' => $user['IdOsoba'],
                            'first_name' => $user['Imie'],
                            'last_name' => $user['Nazwisko'],
                            'email' => $user['Email'],
                            'status' => $user['Status']

                        ];
                        header('Location: /RNZManagementTool/public/views/pages/main.php');
                        exit();
                    } else {
                        $messages[] = 'Niepoprawne dane logowania';
                    }
                } else {
                    $messages[] = 'Niepoprawne dane logowania';
                }
                $mysqli->close();
            } else {
                $messages[] = 'Proszę wypełnić wszystkie pola';
            }
        }
        require_once 'public/views/index.php';
    }
}