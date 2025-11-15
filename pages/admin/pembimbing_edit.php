<?php

$pesan_sukses = '';
$pesan_error = '';
$pembimbing = null;

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger' role='alert'>Error: ID Pembimbing tidak ditemukan.</div>";
    exit;
}
$id_pembimbing = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nip = $_POST['nip'];
    $nama_guru = $_POST['nama_guru'];
    $no_telp = $_POST['no_telp'];

    try {
        $pdo->beginTransaction();
        
        $sql_pembimbing = "UPDATE pembimbing SET nip = :nip, nama_guru = :nama, no_telp = :telp WHERE id_pembimbing = :id";
        $stmt_pembimbing = $pdo->prepare($sql_pembimbing);
        
        $stmt_pembimbing->execute([
            ':nip' => $nip,
            ':nama' => $nama_guru,
            ':telp' => $no_telp,
            ':id' => $id_pembimbing
        ]);

        $sql_user = "UPDATE users SET username = :nip WHERE id_ref = :id AND role = 'pembimbing'";
        $stmt_user = $pdo->prepare($sql_user);
        $stmt_user->execute([
            ':nip' => $nip,
            ':id' => $id_pembimbing
        ]);
        
        $pdo->commit();
        
        $pesan_sukses = "Data pembimbing berhasil diperbarui!";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $pesan_error = "Gagal memperbarui data: " . $e->getMessage();
    }
}

try {
    $sql_get = "SELECT * FROM pembimbing WHERE id_pembimbing = :id";
    $stmt_get = $pdo->prepare($sql_get);
    $stmt_get->bindParam(':id', $id_pembimbing);
    $stmt_get->execute();
    
    $pembimbing = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$pembimbing) {
        echo "<div class='alert alert-danger' role='alert'>Error: Data pembimbing dengan ID $id_pembimbing tidak ditemukan.</div>";
        exit;
    }
} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}
?>

<h2 class="mb-4">Edit Data Guru Pembimbing</h2>

<?php if(!empty($pesan_sukses)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $pesan_sukses; ?>
    </div>
<?php endif; ?>
<?php if(!empty($pesan_error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $pesan_error; ?>
    </div>
<?php endif; ?>

<?php if ($pembimbing): ?>
<form action="index.php?page=admin/pembimbing_edit&id=<?php echo $id_pembimbing; ?>" method="POST">
    
    <div class="mb-3">
        <label for="nip" class="form-label">NIP</label>
        <input type="text" class="form-control" id="nip" name="nip" value="<?php echo htmlspecialchars($pembimbing['nip']); ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="nama_guru" class="form-label">Nama Guru</label>
        <input type="text" class="form-control" id="nama_guru" name="nama_guru" value="<?php echo htmlspecialchars($pembimbing['nama_guru']); ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="no_telp" class="form-label">No. Telepon</label>
        <input type="text" class="form-control" id="no_telp" name="no_telp" value="<?php echo htmlspecialchars($pembimbing['no_telp']); ?>">
    </div>
    
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="index.php?page=admin/pembimbing_data" class="btn btn-secondary">Batal</a>
</form>
<?php endif; ?>