<?php
// File: baca_notif.php (Di folder utama, SEJAJAR dengan index.php)
ob_start();
session_start();
require 'config/koneksi.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_notif = $_GET['id'];
// Ambil link tujuan, jika kosong kembali ke dashboard
$link_tujuan = (isset($_GET['link']) && !empty($_GET['link']) && $_GET['link'] != '#') ? $_GET['link'] : 'index.php?page=dashboard';

try {
    // Tandai notifikasi ini sebagai 'read'
    $stmt = $pdo->prepare("UPDATE notifikasi SET status = 'read' WHERE id_notif = :id AND id_user = :uid");
    $stmt->execute([':id' => $id_notif, ':uid' => $_SESSION['user_id']]);
} catch (PDOException $e) {
    // Silent error
}

// Redirect pengguna ke halaman tujuan (misal: detail jurnal)
header("Location: " . $link_tujuan);
exit;
?>