<?php

class Database {
    private $host = 'localhost';
    private $dbname = 'rnzmanago';
    private $user = 'root';
    private $pass = '';
    private $conn;

    public function connect() {
        if ($this->conn == null) {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
            if ($this->conn->connect_error) {
                die("Connection error: " . $this->conn->connect_error);
            }
        }
        return $this->conn;
    }
}
?>