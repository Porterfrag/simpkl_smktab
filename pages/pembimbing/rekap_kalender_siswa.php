<?php
// --- PHP LOGIC (UNCHANGED) ---
if (!isset($_SESSION['id_ref']) || $_SESSION['role'] != 'pembimbing') {
    die("Akses tidak sah!");
}
$id_pembimbing = $_SESSION['id_ref'];

if (!isset($_GET['id_siswa'])) {
    echo "<div class='alert alert-danger' role='alert'>Error: ID Siswa tidak ditemukan.</div>";
    exit;
}
$id_siswa = $_GET['id_siswa'];

$bulan_pilihan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilihan = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$nama_bulan_ini = date('F Y', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));

$hari_kerja_perusahaan = []; 
try {
    $sql_info = "SELECT s.nama_lengkap, p.hari_kerja 
                 FROM siswa s
                 LEFT JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                 WHERE s.id_siswa = :id_siswa AND s.id_pembimbing = :id_pembimbing";
                 
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute([':id_siswa' => $id_siswa, ':id_pembimbing' => $id_pembimbing]);
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    
    if (!$info) {
        die("Error: Data siswa tidak ditemukan atau bukan bimbingan Anda.");
    }
    
    $nama_siswa = $info['nama_lengkap'];
    
    if (!empty($info['hari_kerja'])) {
        $hari_kerja_perusahaan = explode(',', $info['hari_kerja']);
    } else {
        $hari_kerja_perusahaan = [1, 2, 3, 4, 5]; 
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$data_absensi_bulan_ini = [];
// Variables for Summary Dashboard
$summary = ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 0];

try {
    $sql_absen = "SELECT DAY(tanggal) as hari, status 
                  FROM absensi 
                  WHERE id_siswa = :id_siswa 
                    AND MONTH(tanggal) = :bulan 
                    AND YEAR(tanggal) = :tahun";
    $stmt_absen = $pdo->prepare($sql_absen);
    $stmt_absen->execute([
        ':id_siswa' => $id_siswa,
        ':bulan' => $bulan_pilihan,
        ':tahun' => $tahun_pilihan
    ]);
    
    while ($row = $stmt_absen->fetch(PDO::FETCH_ASSOC)) {
        $data_absensi_bulan_ini[$row['hari']] = $row['status'];
        // Calculate Summary
        if(isset($summary[$row['status']])) {
            $summary[$row['status']]++;
        }
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger' role='alert'>Gagal mengambil data absensi: " . $e->getMessage() . "</div>";
}

$jumlah_hari_di_bulan = date('t', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_pertama_minggu = date('N', mktime(0, 0, 0, $bulan_pilihan, 1, $tahun_pilihan));
$hari_ini = date('d');
$is_current_month = ($bulan_pilihan == date('m') && $tahun_pilihan == date('Y'));

$pkl_start = isset($SETTINGS['pkl_start_date']) ? $SETTINGS['pkl_start_date'] : '2020-01-01';
$pkl_end   = isset($SETTINGS['pkl_end_date']) ? $SETTINGS['pkl_end_date'] : '2030-12-31';
?>

<style>
    /* Mobile First Grid Calendar */
    .calendar-container {
        max-width: 100%;
        margin: 0 auto;
    }
    
    /* Summary Cards */
    .summary-card {
        background: #fff;
        border-radius: 12px;
        padding: 10px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 10px;
        border: 1px solid #eee;
    }
    .summary-count { font-size: 1.2rem; font-weight: 800; display: block; }
    .summary-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }

    /* The Grid */
    .calendar-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        font-weight: 600;
        font-size: 0.8rem;
        color: #6c757d;
        padding-bottom: 10px;
    }
    
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 6px; /* Space between cells */
    }

    .day-cell {
        aspect-ratio: 1 / 1; /* Keeps cells square */
        background-color: #fff;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        font-size: 0.9rem;
        font-weight: 500;
        color: #333;
        border: 1px solid #f0f0f0;
        transition: transform 0.1s;
    }

    .day-cell.empty { background: transparent; border: none; }

    /* Status Styling - Backgrounds work better than badges on mobile */
    .day-cell.status-hadir { background-color: #d1e7dd; color: #0f5132; border-color: #badbcc; }
    .day-cell.status-izin { background-color: #cfe2ff; color: #084298; border-color: #b6d4fe; }
    .day-cell.status-sakit { background-color: #fff3cd; color: #664d03; border-color: #ffecb5; }
    .day-cell.status-alpha { background-color: #f8d7da; color: #842029; border-color: #f5c6cb; }
    .day-cell.status-libur { background-color: #f8f9fa; color: #adb5bd; }
    
    /* Auto Alpha Indicator */
    .day-cell.status-missing { 
        background-color: #fff; 
        border: 2px dashed #dc3545; 
        color: #dc3545;
    }

    /* Current Day Indicator */
    .day-cell.today {
        box-shadow: 0 0 0 2px #0d6efd;
        z-index: 2;
        font-weight: bold;
    }

    /* Status Label inside cell (small text) */
    .cell-status-text {
        font-size: 0.6rem;
        margin-top: 2px;
        font-weight: normal;
        display: none; /* Hidden on very small screens */
    }
    
    /* Show text on slightly larger screens */
    @media (min-width: 400px) {
        .cell-status-text { display: block; }
    }

    .filter-area {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="index.php?page=pembimbing/validasi_daftar_siswa" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
    <h5 class="m-0 fw-bold text-end"><?php echo htmlspecialchars($nama_siswa); ?></h5>
</div>

<div class="row g-2 mb-3">
    <div class="col-3">
        <div class="summary-card">
            <span class="summary-count text-success"><?php echo $summary['Hadir']; ?></span>
            <span class="summary-label">Hadir</span>
        </div>
    </div>
    <div class="col-3">
        <div class="summary-card">
            <span class="summary-count text-primary"><?php echo $summary['Izin']; ?></span>
            <span class="summary-label">Izin</span>
        </div>
    </div>
    <div class="col-3">
        <div class="summary-card">
            <span class="summary-count text-warning"><?php echo $summary['Sakit']; ?></span>
            <span class="summary-label">Sakit</span>
        </div>
    </div>
    <div class="col-3">
        <div class="summary-card">
            <span class="summary-count text-danger"><?php echo $summary['Alpha']; ?></span>
            <span class="summary-label">Alpha</span>
        </div>
    </div>
</div>

<div class="filter-area">
    <form action="index.php" method="GET" class="row g-2">
        <input type="hidden" name="page" value="pembimbing/rekap_kalender_siswa">
        <input type="hidden" name="id_siswa" value="<?php echo $id_siswa; ?>">
        
        <div class="col-6">
            <label class="small text-muted mb-1">Bulan</label>
            <select name="bulan" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $bulan_pilihan) ? 'selected' : ''; ?>>
                        <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6">
            <label class="small text-muted mb-1">Tahun</label>
            <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for ($i = date('Y')-1; $i <= date('Y')+1; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($i == $tahun_pilihan) ? 'selected' : ''; ?>>
                        <?php echo $i; ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
    </form>
</div>

<div class="calendar-container">
    <h6 class="text-center mb-3 fw-bold text-uppercase text-primary">
        <?php echo $nama_bulan_ini; ?>
    </h6>

    <div class="calendar-header">
        <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div>Min</div>
    </div>

    <div class="calendar-grid">
        <?php
        // Empty cells before start of month
        for ($i = 1; $i < $hari_pertama_minggu; $i++) {
            echo '<div class="day-cell empty"></div>';
        }

        // Date Loop
        for ($hari_ke = 1; $hari_ke <= $jumlah_hari_di_bulan; $hari_ke++) {
            $hari_minggu_ini = date('N', mktime(0, 0, 0, $bulan_pilihan, $hari_ke, $tahun_pilihan));
            $tanggal_loop = "$tahun_pilihan-" . str_pad($bulan_pilihan, 2, '0', STR_PAD_LEFT) . "-" . str_pad($hari_ke, 2, '0', STR_PAD_LEFT);
            $tanggal_hari_ini = date('Y-m-d');
            
            // Determine Classes
            $cell_classes = ['day-cell'];
            $status_label = '';

            // Check is Today
            if ($is_current_month && $hari_ke == $hari_ini) {
                $cell_classes[] = 'today';
            }

            // Check Data
            if (isset($data_absensi_bulan_ini[$hari_ke])) {
                $status = $data_absensi_bulan_ini[$hari_ke];
                $cell_classes[] = 'status-' . strtolower($status);
                $status_label = $status;
            } else {
                // Logic for empty days
                if (in_array($hari_minggu_ini, $hari_kerja_perusahaan)) {
                    $is_within_period = ($tanggal_loop >= $pkl_start && $tanggal_loop <= $pkl_end);
                    
                    if ($tanggal_loop < $tanggal_hari_ini && $is_within_period) {
                        $cell_classes[] = 'status-missing'; // Alpha Otomatis visual style
                        $status_label = '!'; 
                    }
                } else {
                    $cell_classes[] = 'status-libur';
                }
            }

            // Render Cell
            echo '<div class="' . implode(' ', $cell_classes) . '">';
            echo '<span>' . $hari_ke . '</span>';
            if($status_label) {
                echo '<span class="cell-status-text">' . substr($status_label, 0, 3) . '</span>';
            }
            echo '</div>';
        }
        ?>
    </div>
</div>

<div class="mt-4 pt-3 border-top">
    <small class="text-muted d-block mb-2 fw-bold">Keterangan:</small>
    <div class="d-flex flex-wrap gap-2">
        <span class="badge bg-success bg-opacity-25 text-success border border-success">Hadir</span>
        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary">Izin</span>
        <span class="badge bg-warning bg-opacity-25 text-warning border border-warning">Sakit</span>
        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger">Alpha</span>
        <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary">Libur</span>
    </div>
</div>