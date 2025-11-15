<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

if (!isset($_GET['id'])) {
    $_SESSION['pesan_error'] = "Error: ID Pengumuman tidak ditemukan.";
    header("Location: index.php?page=admin/pengumuman_data");
    exit;
}
$id_pengumuman = $_GET['id'];

if ($id_pengumuman) {
    try {
        $sql_hapus = "DELETE FROM pengumuman WHERE id_pengumuman = :id";
        $stmt_hapus = $pdo->prepare($sql_hapus);
        $stmt_hapus->bindParam(':id', $id_pengumuman);
        $stmt_hapus->execute();

        $_SESSION['pesan_sukses'] = "Pengumuman berhasil dihapus.";

    } catch (PDOException $e) {
        $_SESSION['pesan_error'] = "Gagal menghapus data: " . $e->getMessage();
    }
}

header("Location: index.php?page=admin/pengumuman_data");
exit;
?>