<?php
session_start();

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'siswa') {
    die("Akses tidak sah!");
}

$id_siswa = $_SESSION['id_ref'];

if (!isset($_GET['id'])) {
    $_SESSION['pesan_error'] = "Error: ID Jurnal tidak ditemukan.";
    header("Location: index.php?page=siswa/jurnal_lihat");
    exit;
}
$id_jurnal = $_GET['id'];

if ($id_jurnal) {
    try {
        $sql_cek = "SELECT foto_kegiatan FROM jurnal_harian 
                    WHERE id_jurnal = :id_jurnal AND id_siswa = :id_siswa AND status_validasi = 'Pending'";
        $stmt_cek = $pdo->prepare($sql_cek);
        $stmt_cek->bindParam(':id_jurnal', $id_jurnal);
        $stmt_cek->bindParam(':id_siswa', $id_siswa);
        $stmt_cek->execute();
        
        $jurnal = $stmt_cek->fetch(PDO::FETCH_ASSOC);

        if ($jurnal) {
            
            if (!empty($jurnal['foto_kegiatan'])) {
                $file_path = "assets/uploads/" . $jurnal['foto_kegiatan'];
                if (file_exists($file_path)) {
                    unlink($file_path); 
                }
            }
            
            $sql_hapus = "DELETE FROM jurnal_harian WHERE id_jurnal = :id_jurnal";
            $stmt_hapus = $pdo->prepare($sql_hapus);
            $stmt_hapus->bindParam(':id_jurnal', $id_jurnal);
            $stmt_hapus->execute();

            $_SESSION['pesan_sukses'] = "Jurnal berhasil dihapus.";

        } else {
            $_SESSION['pesan_error'] = "Gagal menghapus: Jurnal tidak ditemukan atau sudah divalidasi.";
        }

    } catch (PDOException $e) {
        $_SESSION['pesan_error'] = "Gagal menghapus data: " . $e->getMessage();
    }
}

header("Location: index.php?page=siswa/jurnal_lihat");
exit;
?>