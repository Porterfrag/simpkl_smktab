<?php

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'pembimbing') {
    die("Akses tidak sah!");
}
$id_pembimbing = $_SESSION['id_ref'];

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
    $sql = "SELECT 
                siswa.id_siswa, 
                siswa.nis, 
                siswa.nama_lengkap, 
                siswa.kelas, 
                perusahaan.nama_perusahaan,
                (SELECT COUNT(*) FROM jurnal_harian WHERE id_siswa = siswa.id_siswa AND status_validasi = 'Pending') as jumlah_pending
            FROM 
                siswa
            LEFT JOIN 
                perusahaan ON siswa.id_perusahaan = perusahaan.id_perusahaan
            WHERE 
                siswa.id_pembimbing = :id_pembimbing
            ORDER BY 
                siswa.nama_lengkap ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_pembimbing', $id_pembimbing);
    $stmt->execute();
    $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Error: " . $e->getMessage() . "</div>";
}
?>

<h2 class="mb-4">Daftar Siswa Bimbingan</h2>
<p class="mb-3">Silakan pilih siswa untuk melihat dan memvalidasi jurnal harian mereka.</p>

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

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered datatable">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Siswa (NIS)</th>
                <th>Kelas</th>
                <th>Tempat PKL</th>
                <th>Jurnal Pending</th>
                <th style="min-width: 170px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($siswa_list as $siswa): ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-start">
                        <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>
                        <br><small class="text-muted">(NIS: <?php echo htmlspecialchars($siswa['nis']); ?>)</small>
                    </td>
                    <td class="text-start"><?php echo htmlspecialchars($siswa['kelas']); ?></td>
                    
                    <td class="text-start">
                        <?php $nama_perusahaan = isset($siswa['nama_perusahaan']) ? $siswa['nama_perusahaan'] : 'Belum diatur'; ?>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($nama_perusahaan); ?></span>
                    </td>
                    
                    <td class="text-start">
                        <?php if ($siswa['jumlah_pending'] > 0): ?>
                            <span class="badge bg-danger"><?php echo $siswa['jumlah_pending']; ?> Jurnal</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">0 Jurnal</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="text-start">
                        <a href="index.php?page=pembimbing/validasi_jurnal_siswa&id_siswa=<?php echo $siswa['id_siswa']; ?>" class="btn btn-sm btn-info me-1">
                            Lihat Jurnal & Nilai
                        </a>
                        <br class="d-md-none">
                        <a href="index.php?page=pembimbing/rekap_absensi_siswa&id_siswa=<?php echo $siswa['id_siswa']; ?>" class="btn btn-sm btn-outline-secondary mt-1">
                            Rekap Total Absensi
                        </a>
                        <a href="index.php?page=pembimbing/rekap_kalender_siswa&id_siswa=<?php echo $siswa['id_siswa']; ?>" class="btn btn-sm btn-outline-success mt-1">
                            Kalender
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($siswa_list)): ?>
                <tr>
                    <td colspan="6" class="text-center">Anda belum memiliki siswa bimbingan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>