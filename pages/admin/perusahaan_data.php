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
    $stmt = $pdo->query("SELECT * FROM perusahaan ORDER BY nama_perusahaan ASC");
    $perusahaan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $perusahaan_list = [];
}
?>

<h2 class="mb-4">Manajemen Data Perusahaan (DUDI)</h2>

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

<a href="index.php?page=admin/perusahaan_tambah" class="btn btn-success mb-3">Tambah Perusahaan</a>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered datatable">
        <thead class="table-light">
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">Nama Perusahaan</th>
                <th class="text-center">Alamat</th>
                <th class="text-center">Kontak Person</th>
                <th class="text-center">No. Telepon</th>
                <th class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($perusahaan_list as $perusahaan): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($perusahaan['nama_perusahaan']); ?></td>
                    <td class="text-start"><?php echo nl2br(htmlspecialchars($perusahaan['alamat'])); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($perusahaan['kontak_person']); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($perusahaan['no_telp']); ?></td>
                    <td style="min-width: 150px;">
                        <a href="index.php?page=admin/perusahaan_edit&id=<?php echo $perusahaan['id_perusahaan']; ?>" class="btn btn-warning btn-sm me-1">Edit</a>
                        <a href="index.php?page=admin/perusahaan_hapus&id=<?php echo $perusahaan['id_perusahaan']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($perusahaan_list)): ?>
                <tr>
                    <td colspan="6" class="text-center">Data perusahaan masih kosong.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>