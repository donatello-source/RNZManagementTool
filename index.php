<?php

require_once 'Routing.php';
require_once 'src/controllers/MainController.php';


$baseDir = 'RNZManagementTool';
$path = trim($_SERVER['REQUEST_URI'], '/');
$path = parse_url($path, PHP_URL_PATH);

if (strpos($path, $baseDir) === 0) {
    $path = substr($path, strlen($baseDir));
}

$path = ltrim($path, '/');


Router::get('', 'SecurityController');
Router::post('logout', 'SecurityController');
Router::post('login', 'SecurityController');
Router::get('main', 'MainController');
Router::get('getEvents', 'MainController');
Router::get('getDetailedEvents', 'MainController');
Router::get('getAllEmployees', 'MainController');
Router::get('getAllDetailedEmployees', 'MainController');
Router::get('getEmployee', 'MainController');
Router::get('getEmployeeEvents', 'MainController');
Router::get('getEmployeePositions', 'MainController');
Router::get('getEmployeesPositions', 'MainController');
Router::get('getEmployeePaymentsByMonth', 'MainController');
Router::post('updateEmployeesPositions', 'MainController');
Router::post('saveEmployeeEventDays', 'MainController');
Router::post('updateEmployee', 'MainController');
Router::post('addUser', 'MainController');
Router::get('getAllFirms', 'MainController');
Router::get('getFirm', 'MainController');
Router::post('updateFirm', 'MainController');
Router::get('getEvent', 'MainController');
Router::post('updateEvent', 'MainController');
Router::post('addEvent', 'MainController');
Router::post('deleteEvent', 'MainController');
Router::post('deleteEmployee', 'MainController');
Router::post('deleteFirm', 'MainController');


Router::run($path);