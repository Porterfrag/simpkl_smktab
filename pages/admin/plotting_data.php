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
    $sql = "SELECT 
                siswa.id_siswa, 
                siswa.nis, 
                siswa.nama_lengkap, 
                siswa.jurusan, 
                siswa.kelas,
                perusahaan.nama_perusahaan,
                pembimbing.nama_guru
            FROM 
                siswa
            LEFT JOIN 
                perusahaan ON siswa.id_perusahaan = perusahaan.id_perusahaan
            LEFT JOIN 
                pembimbing ON siswa.id_pembimbing = pembimbing.id_pembimbing
            ORDER BY 
                siswa.nama_lengkap ASC";
            
    $stmt = $pdo->query($sql);
    $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $siswa_list = [];
}
?>

<h2 class="mb-4">Plotting Penempatan Siswa</h2>

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

<a href="index.php?page=admin/siswa_data" class="btn btn-secondary mb-3">Lihat Data Siswa (CRUD)</a>

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered datatable">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Siswa (Kelas/Jurusan)</th>
                <th>Perusahaan (DUDI)</th>
                <th>Guru Pembimbing</th>
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
                        <br><small class="text-muted">(<?php echo htmlspecialchars($siswa['kelas']); ?> / <?php echo htmlspecialchars($siswa['jurusan']); ?>)</small>
                    </td>
                    
                    <td class="text-start">
                        <?php if (!empty($siswa['nama_perusahaan'])): ?>
                            <span class="badge bg-primary"><?php echo htmlspecialchars($siswa['nama_perusahaan']); ?></span>
                        <?php else: ?>
                            <span class="badge bg-danger">Belum Ditempatkan</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="text-start">
                        <?php if (!empty($siswa['nama_guru'])): ?>
                            <?php echo htmlspecialchars($siswa['nama_guru']); ?>
                        <?php else: ?>
                            <span class="badge bg-danger">Belum Diatur</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-start">
                        <a href="index.php?page=admin/plotting_edit&id_siswa=<?php echo $siswa['id_siswa']; ?>" class="btn btn-sm btn-info">Atur / Edit Plotting</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($siswa_list)): ?>
                <tr>
                    <td colspan="5" class="text-center">Data siswa masih kosong.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>