<?php

if ($_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

if (!isset($_GET['id'])) {
    $_SESSION['pesan_error'] = "Error: ID Pembimbing tidak ditemukan.";
    header("Location: index.php?page=admin/pembimbing_data");
    exit;
}
$id_pembimbing = $_GET['id'];

if ($id_pembimbing) {
    try {
        
        $sql_cek = "SELECT COUNT(*) FROM siswa WHERE id_pembimbing = :id";
        $stmt_cek = $pdo->prepare($sql_cek);
        $stmt_cek->bindParam(':id', $id_pembimbing);
        $stmt_cek->execute();
        $jumlah_siswa = $stmt_cek->fetchColumn();

        if ($jumlah_siswa > 0) {
            $_SESSION['pesan_error'] = "Gagal menghapus: Masih ada ($jumlah_siswa) siswa yang dibimbing oleh guru ini. Hapus/ubah dulu data siswa terkait.";
        } else {
            
            $pdo->beginTransaction();

            $sql_user = "DELETE FROM users WHERE id_ref = :id AND role = 'pembimbing'";
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->bindParam(':id', $id_pembimbing);
            $stmt_user->execute();

            $sql_pembimbing = "DELETE FROM pembimbing WHERE id_pembimbing = :id";
            $stmt_pembimbing = $pdo->prepare($sql_pembimbing);
            $stmt_pembimbing->bindParam(':id', $id_pembimbing);
            $stmt_pembimbing->execute();

            $pdo->commit();
            
            $_SESSION['pesan_sukses'] = "Data pembimbing dan akun terkait berhasil dihapus.";
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['pesan_error'] = "Gagal menghapus data: " . $e->getMessage();
    }
}

header("Location: index.php?page=admin/pembimbing_data");
exit;
?>