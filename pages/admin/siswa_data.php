<?php

$pesan_sukses = '';
$pesan_error = '';

if (isset($_SESSION['pesan_sukses'])) {
    $pesan_sukses = $_SESSION['pesan_sukses'];
    unset($_SESSION['pesan_sukses']); 
}
if (isset($_SESSION['pesan_error'])) {
    $pesan_error = $_SESSION['pesan_error'];
    unset($_SESSION['pesan_error']); 
}

try {
    $stmt = $pdo->query("SELECT * FROM siswa ORDER BY nama_lengkap ASC");
    $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $siswa_list = []; 
}
?>

<h2 class="mb-4">Manajemen Data Siswa</h2>

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

<a href="index.php?page=admin/siswa_tambah" class="btn btn-success mb-3">Tambah Siswa</a>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered datatable">
        <thead class="table-light">
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">NIS</th>
                <th class="text-center">Nama Lengkap</th>
                <th class="text-center">Jurusan</th>
                <th class="text-center">Kelas</th>
                <th class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($siswa_list as $siswa): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($siswa['nis']); ?></td>
                    
                    <td class="text-start"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($siswa['jurusan']); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($siswa['kelas']); ?></td>
                    
                    <td style="min-width: 200px;">
                        <a href="index.php?page=admin/siswa_edit&id=<?php echo $siswa['id_siswa']; ?>" class="btn btn-warning btn-sm me-1">Edit</a>
                        <a href="index.php?page=admin/siswa_hapus&id=<?php echo $siswa['id_siswa']; ?>" class="btn btn-danger btn-sm me-1" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                        <a href="index.php?page=admin/siswa_reset_password&id=<?php echo $siswa['id_siswa']; ?>" class="btn btn-info btn-sm" onclick="return confirm('Yakin ingin me-reset password siswa ini ke NIS default?')">Reset Pass</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($siswa_list)): ?>
                <tr>
                    <td colspan="6" class="text-center">Data siswa masih kosong.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>