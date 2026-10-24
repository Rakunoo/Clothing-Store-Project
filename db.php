<?php
// Keep PHP dates aligned with the store's local time and MySQL's system clock.
date_default_timezone_set('Asia/Manila');

function getConnection() {
    $host = "localhost";
    $dbname = "clothing_store_recovery"; 
    $user = "root";
    $password = ""  ; 

    try {
        $conn = new PDO("mysql:host=$host;port=3307;dbname=$dbname;charset=utf8", $user, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
