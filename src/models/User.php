<?php

require_once 'Database.php';

class User {
    public static function getUserByEmail($email) {
        $db = (new Database())->connect();
        $query = "SELECT * FROM osoby WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user;
    }
}
?>