<?php

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'siswa') {
    die("Akses tidak sah!");
}
$id_siswa = $_SESSION['id_ref'];

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
    $sql = "SELECT * FROM jurnal_harian WHERE id_siswa = :id_siswa ORDER BY tanggal DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_siswa', $id_siswa);
    $stmt->execute();
    $jurnal_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<h2 class="mb-4">Jurnal Harian PKL</h2>

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

<a href="index.php?page=siswa/jurnal_isi" class="btn btn-success mb-3">Isi Jurnal Harian Baru</a>

<div class="table-responsive">
    <table id="jurnalSiswaTable" class="table table-striped table-hover table-bordered <?php echo (!empty($jurnal_list) ? 'datatable' : ''); ?>">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Kegiatan</th>
                <th>Foto</th>
                <th>Status Validasi</th>
                <th>Catatan Pembimbing</th>
                <th style="min-width: 100px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($jurnal_list as $jurnal): ?>
                <tr>
                    <td class="text-start"><?php echo $no++; ?></td>
                    <td class="text-start"><?php echo date('d M Y', strtotime($jurnal['tanggal'])); ?></td>
                    <td class="text-start"><?php echo nl2br(htmlspecialchars($jurnal['kegiatan'])); ?></td>
                    <td class="text-start">
                        <?php if (!empty($jurnal['foto_kegiatan'])): ?>
                            <a href="assets/uploads/<?php echo htmlspecialchars($jurnal['foto_kegiatan']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat Foto</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-start">
                        <?php 
                            $status = $jurnal['status_validasi'];
                            $class_badge = 'bg-warning'; 
                            if ($status == 'Disetujui') $class_badge = 'bg-success';
                            if ($status == 'Ditolak') $class_badge = 'bg-danger';
                        ?>
                        <span class="badge <?php echo $class_badge; ?>"><?php echo htmlspecialchars($status); ?></span>
                    </td>
                    <td class="text-start"><?php echo nl2br(htmlspecialchars(isset($jurnal['catatan_pembimbing']) ? $jurnal['catatan_pembimbing'] : '-')); ?></td>
                    <td class="text-start">
                        <?php if ($jurnal['status_validasi'] == 'Pending'): ?>
                            <a href="index.php?page=siswa/jurnal_hapus&id=<?php echo $jurnal['id_jurnal']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Yakin ingin menghapus jurnal ini?')">Hapus</a>
                        <?php else: ?>
                            <span class="text-muted">Terkunci</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($jurnal_list)): ?>
                <tr>
                    <td colspan="7" class="text-center">Anda belum mengisi jurnal harian.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($jurnal_list)): ?>
<script>
$(document).ready(function() {
    // Re-inisiasi DataTables untuk tabel ini (khusus saat ada data)
    // Nonaktifkan sorting pada kolom 2, 3, 4, 5 (Kegiatan, Foto, Status, Catatan, Aksi)
    $('#jurnalSiswaTable').DataTable({
        "columnDefs": [ 
            { "targets": [2, 3, 4, 5, 6], "orderable": false } 
        ]
    });
});
</script>
<?php endif; ?>