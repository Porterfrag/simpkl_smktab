<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Akses dilarang!");
}
$id_admin = $_SESSION['user_id']; 
$hari_ini = date('Y-m-d');
$pesan_sukses = '';
$pesan_error = '';


if (isset($_GET['aksi']) && $_GET['aksi'] == 'tandai_alpha' && isset($_GET['id_siswa'])) {
    $id_siswa_alpha = $_GET['id_siswa'];
    
    try {
        $sql_insert_alpha = "INSERT INTO absensi (id_siswa, tanggal, status, dicatat_oleh)
                             VALUES (:id_siswa, :tanggal, 'Alpha', 'Admin')";
        $stmt_insert = $pdo->prepare($sql_insert_alpha);
        $stmt_insert->execute([
            ':id_siswa' => $id_siswa_alpha,
            ':tanggal' => $hari_ini
        ]);
        $pesan_sukses = "Siswa berhasil ditandai sebagai Alpha.";
        
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) { 
            $pesan_error = "Gagal: Siswa sudah melakukan absensi (Hadir/Izin/Sakit).";
        } else {
            $pesan_error = "Gagal menandai Alpha: " . $e->getMessage();
        }
    }
}


$rekap_absensi = [];
try {
    $sql_siswa = "SELECT 
                    siswa.id_siswa, siswa.nama_lengkap, siswa.nis,
                    pembimbing.nama_guru 
                  FROM siswa 
                  LEFT JOIN pembimbing ON siswa.id_pembimbing = pembimbing.id_pembimbing
                  ORDER BY siswa.nama_lengkap ASC";
    $stmt_siswa = $pdo->prepare($sql_siswa);
    $stmt_siswa->execute();
    $siswa_list = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);
    
    $sql_absensi = "SELECT id_siswa, status, jam_absen, keterangan 
                    FROM absensi 
                    WHERE tanggal = :tanggal";
    $stmt_absensi = $pdo->prepare($sql_absensi);
    $stmt_absensi->execute([':tanggal' => $hari_ini]);
    
    $absensi_hari_ini = [];
    while ($row = $stmt_absensi->fetch(PDO::FETCH_ASSOC)) {
        $absensi_hari_ini[$row['id_siswa']] = $row;
    }
    
    foreach ($siswa_list as $siswa) {
        $id_siswa = $siswa['id_siswa'];
        if (isset($absensi_hari_ini[$id_siswa])) {
            $siswa['status_absen'] = $absensi_hari_ini[$id_siswa]['status'];
            $siswa['jam_absen'] = $absensi_hari_ini[$id_siswa]['jam_absen'];
            $siswa['keterangan'] = $absensi_hari_ini[$id_siswa]['keterangan'];
        } else {
            $siswa['status_absen'] = 'Belum Absen';
            $siswa['jam_absen'] = '-';
            $siswa['keterangan'] = '-';
        }
        $rekap_absensi[] = $siswa;
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Gagal mengambil data: " . $e->getMessage() . "</div>";
}
?>

<h2 class="mb-4">Rekap Absensi Harian (Semua Siswa)</h2>
<p class="mb-3">Halaman ini menampilkan status kehadiran semua siswa untuk hari ini, <strong><?php echo date('d F Y', strtotime($hari_ini)); ?></strong>.</p>
<a href="pages/admin/export_absensi_excel.php" target="_blank" class="btn btn-success mb-3">
    <i class="fas fa-file-excel me-2"></i> Export Rekap Total (Semester) ke Excel
</a>
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
    <table class="table table-striped table-hover table-bordered">
        <thead class="table-light">
            <tr>
                <th>No</th>
                <th>Nama Siswa (NIS)</th>
                <th>Pembimbing</th>
                <th>Status Hari Ini</th>
                <th>Jam Absen</th>
                <th>Keterangan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php foreach ($rekap_absensi as $rekap): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td>
                        <?php echo htmlspecialchars($rekap['nama_lengkap']); ?>
                        <br><small>(NIS: <?php echo htmlspecialchars($rekap['nis']); ?>)</small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars(isset($rekap['nama_guru']) ? $rekap['nama_guru'] : '-'); ?>
                    </td>
                    
                    <td>
                        <?php
                            $status = $rekap['status_absen'];
                            $warna = 'black';
                            if ($status == 'Izin') $warna = 'blue';
                            if ($status == 'Sakit') $warna = 'red';
                            if ($status == 'Alpha') $warna = 'darkred';
                            if ($status == 'Belum Absen') $warna = 'grey';
                            if ($status == 'Hadir') $warna = 'green';
                        ?>
                        <strong style="color: <?php echo $warna; ?>;"><?php echo htmlspecialchars($status); ?></strong>
                    </td>

                    <td><?php echo htmlspecialchars($rekap['jam_absen']); ?></td>
                    <td><?php echo htmlspecialchars($rekap['keterangan']); ?></td>
                    
                    <td>
                        <?php if ($rekap['status_absen'] == 'Belum Absen'): ?>
                            <a href="index.php?page=admin/rekap_absensi_harian&aksi=tandai_alpha&id_siswa=<?php echo $rekap['id_siswa']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Yakin menandai siswa ini sebagai ALPHA?')"
                               style="font-size: 0.85em;">
                               Tandai Alpha
                            </a>
                        <?php else: ?>
                            <span class="text-muted">(Sudah Terekam)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($rekap_absensi)): ?>
                <tr>
                    <td colspan="7" class="text-center">Data siswa masih kosong.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
