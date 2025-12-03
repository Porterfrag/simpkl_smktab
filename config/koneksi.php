<?php
date_default_timezone_set('Asia/Makassar'); 

// =========================================================
// --- KONFIGURASI ERROR LOGGING (BARU) ---
// =========================================================

// 1. Matikan tampilan error ke layar (Security)
// Ubah jadi 0 saat sudah online/live. Saat coding boleh 1.
ini_set('display_errors', 0); 
ini_set('display_startup_errors', 0);

// 2. Aktifkan pencatatan ke file
ini_set('log_errors', 1); 

// 3. Tentukan lokasi file log
// File ini akan otomatis dibuat di folder 'logs'
$log_folder = __DIR__ . '/../logs';
if (!file_exists($log_folder)) {
    mkdir($log_folder, 0777, true);
}
ini_set('error_log', $log_folder . '/error_log.txt');

// =========================================================
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