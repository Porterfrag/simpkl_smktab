<?php

if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'pembimbing') {
    die("Akses tidak sah!");
}
$id_pembimbing = $_SESSION['id_ref'];

if (!isset($_GET['id_siswa'])) {
    echo "<div class='alert alert-danger' role='alert'>Error: ID Siswa tidak ditemukan.</div>";
    exit;
}
$id_siswa = $_GET['id_siswa'];


try {
    $sql_cek = "SELECT nama_lengkap FROM siswa WHERE id_siswa = :id_siswa AND id_pembimbing = :id_pembimbing";
    $stmt_cek = $pdo->prepare($sql_cek);
    $stmt_cek->bindParam(':id_siswa', $id_siswa);
    $stmt_cek->bindParam(':id_pembimbing', $id_pembimbing);
    $stmt_cek->execute();
    
    $siswa = $stmt_cek->fetch(PDO::FETCH_ASSOC);
    if (!$siswa) {
        die("Error 403: Anda tidak memiliki akses ke data siswa ini.");
    }
    $nama_siswa = $siswa['nama_lengkap'];
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}


$rekap = [
    'Hadir' => 0,
    'Izin' => 0,
    'Sakit' => 0,
    'Alpha' => 0
];
$total_hari = 0;
$detail_list = []; 

try {
    $sql_count = "SELECT status, COUNT(*) as total 
                  FROM absensi 
                  WHERE id_siswa = :id_siswa 
                  GROUP BY status";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute(['id_siswa' => $id_siswa]);
    
    while ($row = $stmt_count->fetch(PDO::FETCH_ASSOC)) {
        if (isset($rekap[$row['status']])) {
            $rekap[$row['status']] = $row['total'];
            $total_hari += $row['total'];
        }
    }
    
    $sql_detail = "SELECT tanggal, status, keterangan, dicatat_oleh, bukti_foto, id_absensi 
                   FROM absensi 
                   WHERE id_siswa = :id_siswa AND (status = 'Izin' OR status = 'Sakit' OR status = 'Alpha')
                   ORDER BY tanggal ASC";
    $stmt_detail = $pdo->prepare($sql_detail);
    $stmt_detail->execute(['id_siswa' => $id_siswa]);
    $detail_list = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Gagal mengambil data rekap: " . $e->getMessage() . "</div>";
}
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <a href="index.php?page=pembimbing/validasi_daftar_siswa" class="btn btn-sm btn-secondary mb-2">&larr; Kembali ke Daftar</a>
        <h2 class="mb-0">Rekap Absensi: <?php echo htmlspecialchars($nama_siswa); ?></h2>
        <p class="text-muted mt-1">Total hari terdata: <strong><?php echo $total_hari; ?></strong> hari.</p>
    </div>
    <div>
        <a href="pages/pembimbing/export_absensi_detail.php?id_siswa=<?php echo $id_siswa; ?>" target="_blank" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i> Export Excel
        </a>
    </div>
</div>

<hr>
<h3 class="mt-4 mb-3">Ringkasan Kehadiran</h3>
<div class="table-responsive mb-5">
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Status</th>
                <th>Jumlah Hari</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-start">Hadir</td>
                <td class="text-end"><?php echo $rekap['Hadir']; ?> hari</td>
            </tr>
            <tr>
                <td class="text-start" style="color: blue;">Izin</td>
                <td class="text-end" style="color: blue;"><?php echo $rekap['Izin']; ?> hari</td>
            </tr>
            <tr>
                <td class="text-start" style="color: red;">Sakit</td>
                <td class="text-end" style="color: red;"><?php echo $rekap['Sakit']; ?> hari</td>
            </tr>
            <tr>
                <td class="text-start" style="color: darkred;">Alpha (Tanpa Keterangan)</td>
                <td class="text-end" style="color: darkred;"><?php echo $rekap['Alpha']; ?> hari</td>
            </tr>
        </tbody>
    </table>
</div>

<h3 class="mt-4 mb-3">Rincian Tanggal (Izin / Sakit / Alpha)</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered <?php echo (!empty($detail_list) ? 'datatable' : ''); ?>">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Status</th> <th>Bukti Foto / Keterangan</th> <th>Dicatat Oleh</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($detail_list as $item): ?>
                <tr>
                    <td class="text-start" width="5%"><?php echo $no++; ?></td>
                    <td class="text-start" width="15%"><?php echo date('d F Y', strtotime($item['tanggal'])); ?></td>
                    
                    <?php
                        $status = htmlspecialchars($item['status']);
                        $class_badge = 'bg-dark';
                        if ($status == 'Izin') $class_badge = 'bg-primary';
                        if ($status == 'Sakit') $class_badge = 'bg-warning text-dark';
                        if ($status == 'Alpha') $class_badge = 'bg-danger';
                    ?>
                    <td class="text-start" width="10%"><span class="badge <?php echo $class_badge; ?>"><?php echo $status; ?></span></td>
                    
                    <td class="text-start" width="40%">
                        <?php if ($item['bukti_foto']): ?>
                            <a href="assets/uploads/<?php echo htmlspecialchars($item['bukti_foto']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Lihat Foto</a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($item['keterangan']); ?>
                        <?php endif; ?>
                    </td>

                    <td class="text-start" width="15%"><?php echo htmlspecialchars($item['dicatat_oleh']); ?></td>
                    <td class="text-start" width="15%">
                        <a href="index.php?page=pembimbing/absensi_edit&id=<?php echo $item['id_absensi']; ?>" class="btn btn-sm btn-info">Koreksi</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($detail_list)): ?>
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data izin, sakit, atau alpha yang tercatat.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>