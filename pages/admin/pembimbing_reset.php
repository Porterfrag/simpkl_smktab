<?php

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

if (!isset($_GET['id'])) {
    $_SESSION['pesan_error'] = "Error: ID Pembimbing tidak ditemukan.";
    header("Location: index.php?page=admin/pembimbing_data");
    exit;
}
$id_pembimbing = $_GET['id'];

try {
    $sql_cek = "SELECT nip FROM pembimbing WHERE id_pembimbing = :id_pembimbing";
    $stmt_cek = $pdo->prepare($sql_cek);
    $stmt_cek->execute(['id_pembimbing' => $id_pembimbing]);
    $pembimbing = $stmt_cek->fetch(PDO::FETCH_ASSOC);

    if ($pembimbing) {
        $password_default = $pembimbing['nip'];
        
        $password_hash_baru = password_hash($password_default, PASSWORD_DEFAULT);

        $sql_update = "UPDATE users SET password = :password 
                       WHERE id_ref = :id_pembimbing AND role = 'pembimbing'";
        $stmt_update = $pdo->prepare($sql_update);
        
        $stmt_update->execute([
            'password' => $password_hash_baru, 
            'id_pembimbing' => $id_pembimbing
        ]);

        $_SESSION['pesan_sukses'] = "Password pembimbing berhasil di-reset ke NIP default.";
        
    } else {
        $_SESSION['pesan_error'] = "Data pembimbing tidak ditemukan.";
    }

} catch (PDOException $e) {
    $_SESSION['pesan_error'] = "Gagal me-reset password: " . $e->getMessage();
}

header("Location: index.php?page=admin/pembimbing_data");
exit;
?>