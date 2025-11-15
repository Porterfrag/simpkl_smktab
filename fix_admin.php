<?php
require 'config/koneksi.php';

$password_baru = 'admin'; 
$hash_baru = password_hash($password_baru, PASSWORD_DEFAULT);

try {
    $sql = "UPDATE users SET password = :pass WHERE username = 'admin'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['pass' => $hash_baru]);
    
    echo "Berhasil! Password Admin telah di-reset ke format baru (Bcrypt).<br>";
    echo "Silakan hapus file ini dan coba login.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>