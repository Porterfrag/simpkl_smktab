<?php

$pesan_sukses = '';
$pesan_error = '';
$siswa = null;

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger' role='alert'>Error: ID Siswa tidak ditemukan.</div>";
    exit;
}
$id_siswa = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nis = $_POST['nis'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $jurusan = $_POST['jurusan'];
    $kelas = $_POST['kelas'];

    try {
        $sql = "UPDATE siswa SET nis = :nis, nama_lengkap = :nama_lengkap, jurusan = :jurusan, kelas = :kelas WHERE id_siswa = :id_siswa";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':nis' => $nis,
            ':nama_lengkap' => $nama_lengkap,
            ':jurusan' => $jurusan,
            ':kelas' => $kelas,
            ':id_siswa' => $id_siswa
        ]);
        
        $pesan_sukses = "Data siswa berhasil diperbarui!";


    } catch (PDOException $e) {
        $pesan_error = "Gagal memperbarui data: " . $e->getMessage();
    }
}

try {
    $sql_get = "SELECT * FROM siswa WHERE id_siswa = :id_siswa";
    $stmt_get = $pdo->prepare($sql_get);
    $stmt_get->bindParam(':id_siswa', $id_siswa);
    $stmt_get->execute();
    
    $siswa = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if (!$siswa) {
        echo "<div class='alert alert-danger' role='alert'>Error: Data siswa dengan ID $id_siswa tidak ditemukan.</div>";
        exit;
    }
} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}
?>

<h2 class="mb-4">Edit Data Siswa</h2>

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

<?php if ($siswa): ?>
<form action="index.php?page=admin/siswa_edit&id=<?php echo $id_siswa; ?>" method="POST">
    
    <div class="mb-3">
        <label for="nis" class="form-label">NIS</label>
        <input type="text" class="form-control" id="nis" name="nis" value="<?php echo htmlspecialchars($siswa['nis']); ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($siswa['nama_lengkap']); ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="jurusan" class="form-label">Jurusan</label>
        <input type="text" class="form-control" id="jurusan" name="jurusan" value="<?php echo htmlspecialchars($siswa['jurusan']); ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="kelas" class="form-label">Kelas</label>
        <input type="text" class="form-control" id="kelas" name="kelas" value="<?php echo htmlspecialchars($siswa['kelas']); ?>" required>
    </div>
    
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="index.php?page=admin/siswa_data" class="btn btn-secondary">Batal</a>
</form>
<?php endif; ?>