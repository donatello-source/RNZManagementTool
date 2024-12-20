<?php

class Database {
    private $host = 'host.docker.internal';
    private $dbname = 'rnzmanago';
    private $user = 'root';
    private $pass = 'root';
    private $conn;

    public function connect() {
        if ($this->conn == null) {
            try {
                $dsn = "pgsql:host=$this->host;dbname=$this->dbname";
                $this->conn = new PDO($dsn, $this->user, $this->pass);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Połączenie nie powiodło się: " . $e->getMessage());
            }
        }
        return $this->conn;
    }
}
?>