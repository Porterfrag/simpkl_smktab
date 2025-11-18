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
    $stmt = $pdo->query("SELECT * FROM pembimbing ORDER BY nama_guru ASC");
    $pembimbing_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $pembimbing_list = [];
}
?>

<h2 class="mb-4">Manajemen Data Guru Pembimbing</h2>

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

<a href="index.php?page=admin/pembimbing_tambah" class="btn btn-success mb-3">Tambah Pembimbing</a>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered datatable">
        <thead class="table-light">
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">No. ID Guru</th>
                <th class="text-center">Nama Guru</th>
                <th class="text-center">No. Telepon</th>
                <th class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($pembimbing_list as $pembimbing): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-start"><?php echo htmlspecialchars(isset($pembimbing['nip']) ? $pembimbing['nip'] : '-'); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($pembimbing['nama_guru']); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($pembimbing['no_telp']); ?></td>
                    <td style="min-width: 250px;">
                        <a href="index.php?page=admin/pembimbing_edit&id=<?php echo $pembimbing['id_pembimbing']; ?>" class="btn btn-warning btn-sm me-1">Edit</a>
                        <a href="index.php?page=admin/pembimbing_hapus&id=<?php echo $pembimbing['id_pembimbing']; ?>" class="btn btn-danger btn-sm me-1" onclick="return confirm('Yakin ingin menghapus data ini?')">Hapus</a>
                        <a href="index.php?page=admin/pembimbing_reset&id=<?php echo $pembimbing['id_pembimbing']; ?>" class="btn btn-info btn-sm" onclick="return confirm('Yakin ingin me-reset password pembimbing ini ke NIP default?')">Reset Pass</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($pembimbing_list)): ?>
                <tr>
                    <td colspan="5" class="text-center">Data pembimbing masih kosong.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>