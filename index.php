<?php

require_once 'Routing.php';

$baseDir = 'RNZManagementTool'; // Zmień na nazwę folderu, w którym znajduje się projekt
$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

// Usuń bazowy katalog z URL-a, jeśli istnieje
if (strpos($path, $baseDir) === 0) {
    $path = substr($path, strlen($baseDir));
}

// Usuń początkowy ukośnik, jeśli został
$path = ltrim($path, '/');


Router::get('', 'SecurityController'); // Strona logowania
//Router::get('dashboard', 'SecurityController'); // Przykład dashboardu
Router::get('security/logout', 'SecurityController@logout');
Router::post('login', 'SecurityController'); // Obsługa logowania

Router::run($path);