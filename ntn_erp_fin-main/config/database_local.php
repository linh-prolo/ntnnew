<?php
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $host    = 'localhost';
        $dbname  = 'erp';
        $user    = 'root';
        $pass    = '';  // XAMPP mặc định không có password
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $user, $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}