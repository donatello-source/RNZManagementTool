<?php

class PositionRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = (new Database())->connect();
    }
    
}