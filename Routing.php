<?php

require_once 'src/controllers/AppController.php';
require_once 'src/controllers/SecurityController.php';

class Router
{
    public static $routes;

    public static function get($url, $view)
    {
        self::$routes['GET'][$url] = $view;
    }

    public static function post($url, $view)
    {
        self::$routes['POST'][$url] = $view;
    }

    public static function run($url)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = explode("/", $url)[0];
    
        var_dump($method, $action, self::$routes); // DEBUG
    
        if (!array_key_exists($action, self::$routes[$method])) {
            http_response_code(404);
            die("Wrong URL!");
        }
    
        $controller = self::$routes[$method][$action];
        $object = new $controller;
        if (empty($action)) {
            $action = 'index'; // lub inna domyślna metoda
        }
        
        if (!method_exists($object, $action)) {
            http_response_code(404);
            die("Action '$action' not found in " . get_class($object));
        }
        
        $object->$action();
    }
}