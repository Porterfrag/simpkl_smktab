<?php
// --- BAGIAN LOGIKA PHP (TIDAK DIUBAH) ---

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

<!-- --- TAMPILAN MOBILE FOCUSED --- -->
<div class="container-fluid px-0">

    <!-- Header Navigasi -->
    <div class="d-flex align-items-center mb-3 bg-white p-3 shadow-sm rounded">
        <a href="index.php?page=pembimbing/validasi_daftar_siswa" class="btn btn-light btn-sm me-3 text-secondary rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
            <small class="text-muted d-block">Rekap Absensi</small>
            <h5 class="mb-0 fw-bold text-dark text-truncate"><?php echo htmlspecialchars($nama_siswa); ?></h5>
        </div>
        <!-- Tombol Export di Header -->
        <a href="pages/pembimbing/export_absensi_detail.php?id_siswa=<?php echo $id_siswa; ?>" target="_blank" class="btn btn-success btn-sm ms-2" title="Export Excel">
            <i class="fas fa-file-excel"></i>
        </a>
    </div>

    <!-- --- BAGIAN DASHBOARD STATISTIK (GRID 2x2) --- -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
            <h6 class="fw-bold text-secondary mb-0">Ringkasan Kehadiran</h6>
            <span class="badge bg-light text-dark border">Total: <?php echo $total_hari; ?> Hari</span>
        </div>
        
        <div class="row g-2">
            <!-- Card Hadir -->
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div class="text-success mb-2"><i class="fas fa-user-check fa-lg"></i></div>
                        <div>
                            <h3 class="mb-0 fw-bold text-success"><?php echo $rekap['Hadir']; ?></h3>
                            <small class="text-success fw-bold opacity-75">Hadir</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Izin -->
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #cce5ff 0%, #b8daff 100%);">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div class="text-primary mb-2"><i class="fas fa-envelope-open-text fa-lg"></i></div>
                        <div>
                            <h3 class="mb-0 fw-bold text-primary"><?php echo $rekap['Izin']; ?></h3>
                            <small class="text-primary fw-bold opacity-75">Izin</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Sakit -->
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div class="text-warning mb-2"><i class="fas fa-procedures fa-lg"></i></div>
                        <div>
                            <h3 class="mb-0 fw-bold text-warning text-dark"><?php echo $rekap['Sakit']; ?></h3>
                            <small class="text-warning text-dark fw-bold opacity-75">Sakit</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Alpha -->
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div class="text-danger mb-2"><i class="fas fa-times-circle fa-lg"></i></div>
                        <div>
                            <h3 class="mb-0 fw-bold text-danger"><?php echo $rekap['Alpha']; ?></h3>
                            <small class="text-danger fw-bold opacity-75">Alpha</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- --- BAGIAN RINCIAN DETAIL (CARD LIST) --- -->
    <h6 class="fw-bold text-secondary border-bottom pb-2 mb-3">
        <i class="fas fa-history me-2"></i>Riwayat Ketidakhadiran
    </h6>

    <div id="attendanceList">
        <?php foreach ($detail_list as $item): ?>
            <?php 
                $status = htmlspecialchars($item['status']);
                
                // Styling berdasarkan status
                $borderClass = 'border-dark';
                $badgeClass = 'bg-dark';
                $icon = 'fa-question';
                
                if ($status == 'Izin') {
                    $borderClass = 'border-primary';
                    $badgeClass = 'bg-primary';
                    $icon = 'fa-envelope';
                } elseif ($status == 'Sakit') {
                    $borderClass = 'border-warning';
                    $badgeClass = 'bg-warning text-dark';
                    $icon = 'fa-medkit';
                } elseif ($status == 'Alpha') {
                    $borderClass = 'border-danger';
                    $badgeClass = 'bg-danger';
                    $icon = 'fa-times';
                }
            ?>

            <div class="card mb-3 shadow-sm border-0 border-start border-4 <?php echo $borderClass; ?>">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="fw-bold text-dark d-block">
                                <?php echo date('d F Y', strtotime($item['tanggal'])); ?>
                            </span>
                            <small class="text-muted" style="font-size: 0.8rem;">
                                <i class="fas fa-user-edit me-1"></i> <?php echo htmlspecialchars($item['dicatat_oleh']); ?>
                            </small>
                        </div>
                        <span class="badge rounded-pill <?php echo $badgeClass; ?>">
                            <i class="fas <?php echo $icon; ?> me-1"></i> <?php echo $status; ?>
                        </span>
                    </div>
                    
                    <div class="bg-light rounded p-2 mb-2">
                        <p class="mb-0 text-secondary small fst-italic">
                            "<?php echo htmlspecialchars($item['keterangan'] ?: '-'); ?>"
                        </p>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <?php if ($item['bukti_foto']): ?>
                            <a href="assets/uploads/<?php echo htmlspecialchars($item['bukti_foto']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-image me-1"></i> Foto
                            </a>
                        <?php endif; ?>
                        <a href="index.php?page=pembimbing/absensi_edit&id=<?php echo $item['id_absensi']; ?>" class="btn btn-sm btn-info text-white">
                            <i class="fas fa-pencil-alt me-1"></i> Koreksi
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($detail_list)): ?>
            <div class="text-center py-5 text-muted bg-light rounded">
                <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                <p class="mb-0 fw-bold">Siswa Rajin!</p>
                <small>Tidak ada riwayat izin, sakit, atau alpha.</small>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer Helper (Optional spacer) -->
    <div class="mb-5"></div>

</div>