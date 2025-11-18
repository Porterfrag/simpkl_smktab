<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_siswas = isset($_POST['id_siswa']) ? $_POST['id_siswa'] : [];
    $id_perusahaan = !empty($_POST['id_perusahaan']) ? $_POST['id_perusahaan'] : null;
    $id_pembimbing = !empty($_POST['id_pembimbing']) ? $_POST['id_pembimbing'] : null;

    if (empty($id_siswas)) {
        $_SESSION['pesan_error'] = "Tidak ada siswa yang dipilih!";
    } else {
        try {
            $pdo->beginTransaction();
            
            $sql = "UPDATE siswa SET id_perusahaan = :id_p, id_pembimbing = :id_g WHERE id_siswa = :id_s";
            $stmt = $pdo->prepare($sql);

            $count = 0;
            foreach ($id_siswas as $id_s) {
                $stmt->execute([
                    ':id_p' => $id_perusahaan,
                    ':id_g' => $id_pembimbing,
                    ':id_s' => $id_s
                ]);
                $count++;
            }
            
            $pdo->commit();
            $_SESSION['pesan_sukses'] = "Berhasil melakukan plotting untuk $count siswa!";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['pesan_error'] = "Gagal plotting massal: " . $e->getMessage();
        }
    }
}

header("Location: index.php?page=admin/plotting_data");
exit;
?>