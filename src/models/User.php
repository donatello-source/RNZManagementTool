<?php

require_once 'Database.php';

class User {
    public static function getUserByEmail($email) {
        $db = (new Database())->connect();
        $stmt = $db->prepare("SELECT * FROM osoby WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>