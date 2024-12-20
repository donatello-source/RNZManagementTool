<?php

require_once 'AppController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Database.php';

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
        header('Location: /');
        exit();
    }

    public function login()
    {
        session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['email']) && isset($_POST['password'])) {
                $email = $_POST['email'];
                $password = $_POST['password'];

                $user = User::getUserByEmail($email);
                if ($user) {
                    if (password_verify($password, $user['haslo']) && $user['status'] != "none") {
                        $_SESSION['user'] = [
                            'id' => $user['idosoba'],
                            'first_name' => $user['imie'],
                            'last_name' => $user['nazwisko'],
                            'email' => $user['email'],
                            'status' => $user['status']
                        ];
                        header('Location: /public/views/pages/main.php');
                        exit();
                    } else {
                        $messages[] = "Konto nie zostało uwierzytelnione przez administratora";
                    }
                } else {
                    $messages[] = 'Niepoprawne dane logowania';
                }
            } else {
                $messages[] = 'Proszę wypełnić wszystkie pola';
            }
        }
        require_once 'public/views/index.php';
    }

}