<?php

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

if (!isset($_GET['id'])) {
    $_SESSION['pesan_error'] = "Error: ID Siswa tidak ditemukan.";
    header("Location: index.php?page=admin/siswa_data");
    exit;
}
$id_siswa = $_GET['id'];

if ($id_siswa) {
    try {
        $pdo->beginTransaction();

        $sql_absensi = "DELETE FROM absensi WHERE id_siswa = :id_siswa";
        $stmt_absensi = $pdo->prepare($sql_absensi);
        $stmt_absensi->execute(['id_siswa' => $id_siswa]);

        $sql_penilaian = "DELETE FROM penilaian WHERE id_siswa = :id_siswa";
        $stmt_penilaian = $pdo->prepare($sql_penilaian);
        $stmt_penilaian->execute(['id_siswa' => $id_siswa]);

        $sql_jurnal = "DELETE FROM jurnal_harian WHERE id_siswa = :id_siswa";
        $stmt_jurnal = $pdo->prepare($sql_jurnal);
        $stmt_jurnal->execute(['id_siswa' => $id_siswa]);
        
        $sql_user = "DELETE FROM users WHERE id_ref = :id_siswa AND role = 'siswa'";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute(['id_siswa' => $id_siswa]);

        $sql_siswa = "DELETE FROM siswa WHERE id_siswa = :id_siswa";
        $stmt_siswa = $pdo->prepare($sql_siswa);
        $stmt_siswa->execute(['id_siswa' => $id_siswa]);
        
        $pdo->commit();

        $_SESSION['pesan_sukses'] = "Data siswa dan semua data terkait berhasil dihapus.";
        header("Location: index.php?page=admin/siswa_data"); 
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        
        $_SESSION['pesan_error'] = "Gagal menghapus data: " . $e->getMessage();
        header("Location: index.php?page=admin/siswa_data");
        exit;
    }
} else {
    header("Location: index.php?page=admin/siswa_data");
    exit;
}
?>