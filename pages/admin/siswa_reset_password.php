<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

if (!isset($_GET['id'])) {
    $_SESSION['pesan_error'] = "Error: ID Siswa tidak ditemukan.";
    header("Location: index.php?page=admin/siswa_data");
    exit;
}
$id_siswa = $_GET['id'];

try {
    $sql_cek = "SELECT nis FROM siswa WHERE id_siswa = :id_siswa";
    $stmt_cek = $pdo->prepare($sql_cek);
    $stmt_cek->execute(['id_siswa' => $id_siswa]);
    $siswa = $stmt_cek->fetch(PDO::FETCH_ASSOC);

   if ($siswa) {
        $password_default = $siswa['nis'];
        
        $password_hash_baru = password_hash($password_default, PASSWORD_DEFAULT);

        $sql_update = "UPDATE users SET password = :password 
                       WHERE id_ref = :id_siswa AND role = 'siswa'";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            'password' => $password_hash_baru, 
            'id_siswa' => $id_siswa
        ]);

        $_SESSION['pesan_sukses'] = "Password siswa berhasil di-reset ke NIS default.";
        
    } else {
        $_SESSION['pesan_error'] = "Data siswa tidak ditemukan.";
    }

} catch (PDOException $e) {
    $_SESSION['pesan_error'] = "Gagal me-reset password: " . $e->getMessage();
}

header("Location: index.php?page=admin/siswa_data");
exit;
?>