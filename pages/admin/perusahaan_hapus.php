<?php
session_start();

if ($_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

if (!isset($_GET['id'])) {
    $_SESSION['pesan_error'] = "Error: ID Perusahaan tidak ditemukan.";
    header("Location: index.php?page=admin/perusahaan_data");
    exit;
}
$id_perusahaan = $_GET['id'];

if ($id_perusahaan) {
    try {
        
        $sql_cek = "SELECT COUNT(*) FROM siswa WHERE id_perusahaan = :id";
        $stmt_cek = $pdo->prepare($sql_cek);
        $stmt_cek->bindParam(':id', $id_perusahaan);
        $stmt_cek->execute();
        $jumlah_siswa = $stmt_cek->fetchColumn();

        if ($jumlah_siswa > 0) {
            $_SESSION['pesan_error'] = "Gagal menghapus: Masih ada ($jumlah_siswa) siswa yang ditempatkan di perusahaan ini. Hapus/ubah dulu data siswa terkait.";
        } else {
            $sql_hapus = "DELETE FROM perusahaan WHERE id_perusahaan = :id";
            $stmt_hapus = $pdo->prepare($sql_hapus);
            $stmt_hapus->bindParam(':id', $id_perusahaan);
            $stmt_hapus->execute();

            $_SESSION['pesan_sukses'] = "Data perusahaan berhasil dihapus.";
        }

    } catch (PDOException $e) {
        $_SESSION['pesan_error'] = "Gagal menghapus data: " . $e->getMessage();
    }
}

header("Location: index.php?page=admin/perusahaan_data");
exit;
?>