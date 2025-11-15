<?php
date_default_timezone_set('Asia/Makassar'); 

$host = 'localhost';
$db_name = 'db_pkl';
$username = 'root';
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("ERROR: Tidak bisa terhubung. " . $e->getMessage());
}



function get_settings($pdo) {
    try {
        $stmt = $pdo->query("SELECT key_setting, value_setting FROM pengaturan");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key_setting']] = $row['value_setting'];
        }
        return $settings;
    } catch (PDOException $e) {
        return ['grading_start_date' => '2025-01-01']; 
    }
}

$SETTINGS = get_settings($pdo);


?>